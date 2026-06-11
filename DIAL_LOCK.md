# Dial Lock — how it works

The **Dial Lock** system stops two agents (or the same agent too soon)
from calling the same phone number at the same time, and caps how many
times any one agent may call the same number per day. It also keeps a
running history for every number: how many times it has been called, when
it was last called, and by whom — all shown to the agent in the
click‑to‑dial confirmation dialog.

Everything described here is implemented in:

| Layer | File |
|-------|------|
| Controller / API | `app/Http/Controllers/DialLockController.php` |
| Model / table | `app/Models/DialLock.php` → `dial_locks` |
| Daily history model / table | `app/Models/DialCallLog.php` → `dial_call_logs` |
| Phone normalisation | `app/Support/PhoneNumber.php` |
| Settings UI | `resources/views/settings/list.blade.php` (`#form-dialing`) |
| Settings save | `app/Http/Controllers/SettingController.php::saveDialingSettings()` |
| Click‑to‑dial UI | `resources/views/layouts/partials/xplosip-widget.blade.php` |
| Routes | `routes/web.php` (`dialing/*`) |

---

## 1. The locking mechanism

### 1.1 One row per number — `phone_key`

Every phone number is normalised to a `phone_key` via
`PhoneNumber::lockKey()` so that different formats of the same line
collide on the same `dial_locks` row:

- **10+ digits** (real phone numbers) → last **10 digits** are used.
  `+44 7123 456789`, `07123456789`, and `0044 7123 456789` all map to the
  same key.
- **3–9 digits** (internal extensions) → used as‑is.
- **Fewer than 3 digits** → returns `null`, meaning **no lock is applied**
  (e.g. accidental clicks on tiny numbers).

There is at most **one** `dial_locks` row per `phone_key`. Calling the
same number again updates that row rather than creating a new one.

### 1.2 Two independent timers

Locking behaviour is fully configurable from **Settings → Dial Lock
Settings** and is stored in the `settings` table as `group = 'dialing'`:

| Setting key | Default | Meaning |
|-------------|---------|---------|
| `dialing_lock_enabled` | `true` | Master on/off switch. When `false`, `acquire()` always succeeds and no locking happens at all. |
| `dialing_lock_other_user_minutes` | `5` (min 1) | How long the number is locked **for every other agent** after a call starts. |
| `dialing_lock_same_user_minutes` | `0` (min 0) | How long **the same agent** is blocked from re‑dialling the same number. `0` means the same agent can re‑dial immediately. |

These are read in `DialLockController::dialingSettings()`, which also
supplies the defaults above as fallbacks if the settings rows don't exist
yet (no seeder is required).

### 1.3 Acquiring a lock — `POST /dialing/acquire`

This is the core of the system (`DialLockController::acquire()`):

1. The number is normalised to a `phone_key`. If it's `null` (too short),
   the call is allowed immediately with no lock created.
2. If `dialing_lock_enabled` is `false`, the call is allowed immediately.
3. Otherwise, inside a `DB::transaction()`, the existing `dial_locks` row
   for that `phone_key` is fetched with `lockForUpdate()` (a row‑level DB
   lock, so two simultaneous requests can't both "win"):
   - **If the row is locked by the *same* agent** (`user_id` matches) and
     `dialing_lock_same_user_minutes > 0`: if `locked_at +
     same_user_minutes` is still in the future, the request is rejected
     with **HTTP 423 (Locked)** and a message like *"You already called
     this number 2 minutes ago. Your re‑dial lock expires in 3m. Called
     5× in total."* If `same_user_minutes = 0`, or that window has passed,
     the call is allowed.
   - **If the row is locked by a *different* agent** and `expires_at` is
     still in the future: the request is rejected with **HTTP 423** and a
     message like *"This number is being called by Jane Doe (1 minute
     ago). Locked for another 4m. Called 5× in total."*
   - **Otherwise** (no row, or all relevant locks have expired): the row is
     created/updated (see below) and the call is allowed (`ok: true`).

### 1.4 Releasing a lock — `POST /dialing/release`

When an agent finishes a call (or cancels), the front end calls
`/dialing/release`, which sets `expires_at = now() - 1 second` for that
agent's own lock — i.e. it expires immediately so the number becomes
available again straight away (subject to the *same‑user* timer if one is
configured).

### 1.5 Daily call limit (per agent, per number)

On top of the two timers above, each agent can be capped to a maximum
number of calls **to the same number, per day**:

| Setting key | Default | Meaning |
|-------------|---------|---------|
| `dialing_max_calls_per_day` | `3` (0–20, `0` = unlimited) | Max calls one agent may place to the same number in a calendar day. |
| `dialing_history_days` | `2` (1–14) | How many days of per‑agent call history (`dial_call_logs`) to retain before older rows are purged. |

This is checked first in `acquire()`, **inside the same `DB::transaction()`
+ `lockForUpdate()`** as the existing lock checks:

1. Look up `dial_call_logs` for `(phone_key, user_id, today)`.
2. If `calls >= dialing_max_calls_per_day` (and the limit is `> 0`), reject
   with **HTTP 423** and `reason: 'daily_limit'`, e.g. *"Daily limit reached
   (3/3) for this number. Resets in 8h 12m."* — the reset time is always the
   next midnight (`now()->startOfDay()->addDay()`).
3. Otherwise, after the call is allowed and `dial_locks` is updated, the
   agent's `dial_call_logs` row for today is incremented (created if it
   doesn't exist).
4. Finally, any `dial_call_logs` rows with `call_date` older than
   `dialing_history_days` days ago are deleted — this is the "history
   overwrite": old per‑agent daily counters are purged opportunistically on
   every successful call, so the table only ever holds a rolling window.

Both `GET /dialing/info` and `POST /dialing/acquire` return
`dailyCallCount`, `dailyCallLimit`, `dailyLimitReached`, and (when reached)
`dailyResetSeconds` for the **calling agent only** — this is independent of
`callCount` (the all‑time total for the number across all agents).

The frontend (`xplosip-widget.blade.php`) shows the agent's "X/Y today"
count in the confirm dialog, and if the limit is already reached it disables
the **Call** button up front. If `acquire()` still returns `daily_limit`
(e.g. a stale dialog), the same blue "Daily call limit reached" block screen
with a live countdown to midnight is shown.

---

## 2. Call count — `call_count`

- Column: `dial_locks.call_count` (`unsignedInteger`, default `0`), added by
  `database/migrations/2026_05_28_190000_add_call_count_to_dial_locks.php`.
- On every **successful** `acquire()`, it is incremented:
  ```php
  $newCount = ($row ? (int) $row->call_count : 0) + 1;
  ```
  and saved via `DialLock::updateOrCreate(['phone_key' => $key], [...,
  'call_count' => $newCount, ...])`.
- It is a **cumulative, all‑time counter for that number** — it counts
  every successful dial by any agent and is **never reset**, even after
  the lock expires.
- Exposed to the UI as:
  - `callCount` from `GET /dialing/info`
  - `call_count` per row from `GET /dialing/active-locks`

---

## 3. Last call time — `locked_at`

- Column: `dial_locks.locked_at` (timestamp, `useCurrent()` default).
- Despite the name, this is updated to `now()` on **every** successful
  `acquire()` — so it always reflects the **most recent call time** for
  that number, not just when the row was first created.
- Exposed to the UI as:
  - `lastCalledAgo` from `GET /dialing/info` — a human string via
    `$row->locked_at->diffForHumans()`, e.g. *"5 minutes ago"*.
  - `locked_at` formatted as `H:i:s` in `GET /dialing/active-locks`, shown
    in the Settings → Active Locks table.

---

## 4. Which user called — `user_id` / `user_name`

- Columns: `dial_locks.user_id` (FK → `users`, nullable, `nullOnDelete`)
  and `dial_locks.user_name` (cached display name, `string(255)`,
  nullable).
- Both are set from the authenticated user (`Auth::user()`) on every
  successful `acquire()`. `user_name` is a **cached snapshot** taken at
  call time, so the history still shows the correct name even if the user
  is later renamed or deleted.
- Used to decide *which* timer applies: `info()` and `acquire()` compare
  `$row->user_id === Auth::id()` to determine `isSelf` /
  `lockedBySelf`:
  - **Self** → the *same‑user* timer (`dialing_lock_same_user_minutes`)
    applies (or no lock at all if it's `0`).
  - **Someone else** → the *other‑user* timer
    (`dialing_lock_other_user_minutes`) applies, and `lockedBy` /
    `lastCalledBy` report that agent's `user_name` to the UI.
- The Settings → Active Locks table shows `user_name` for each active
  lock (falling back to `"Unknown"` if it was never set).

---

## 5. Database schema — `dial_locks`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint, PK | |
| `phone_key` | string(20), **unique** | Normalised lock key (last 10 digits, or short extension as‑is) |
| `full_number` | string(30) | The number exactly as the agent clicked it |
| `user_id` | FK → `users`, nullable, `nullOnDelete` | Agent who made the most recent call |
| `user_name` | string(255), nullable | Cached display name of that agent |
| `applicant_id` | FK → `applicants`, nullable, `nullOnDelete` | Matched applicant for this number, if any |
| `call_count` | unsignedInteger, default `0` | Cumulative successful dials for this number |
| `locked_at` | timestamp, `useCurrent()` | Time of the most recent call |
| `expires_at` | timestamp, **indexed** | When the *other‑user* lock expires |
| `created_at` / `updated_at` | timestamps | |

Migrations: `2026_05_28_180000_create_dial_locks_table.php` and
`2026_05_28_190000_add_call_count_to_dial_locks.php`.

### 5.1 `dial_call_logs` — per-agent daily call history

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint, PK | |
| `phone_key` | string(20) | Same normalised key as `dial_locks.phone_key` |
| `user_id` | FK → `users`, `cascadeOnDelete` | The agent who placed the call |
| `call_date` | date | Calendar date (app timezone) the calls were made on |
| `calls` | unsignedInteger, default `0` | Number of calls this agent made to this number on this date |
| `created_at` / `updated_at` | timestamps | |

Unique on `(phone_key, user_id, call_date)`, indexed on `call_date`. One row
per agent, per number, per day — used to enforce
`dialing_max_calls_per_day` and purged after `dialing_history_days`.

Migration: `2026_06_11_120000_create_dial_call_logs_table.php`.
Model: `app/Models/DialCallLog.php`.

---

## 6. API reference

All routes are under the authenticated web group (`routes/web.php`).

| Method | Route | Controller method | Purpose |
|--------|-------|--------------------|---------|
| `GET`  | `/dialing/info?number=...` | `info()` | Status for a number, personalised to the calling agent: `callCount`, `lastCalledAgo`, `lastCalledBy`, `locked`, `lockedBySelf`, `lockedBy`, `remainingSeconds`, plus `dailyCallCount`, `dailyCallLimit`, `dailyLimitReached`, `dailyResetSeconds`. |
| `POST` | `/dialing/acquire` | `acquire()` | Try to start a call. Returns `ok: true` (and updates the lock/count/daily log) or **HTTP 423** with `reason: 'daily_limit' \| 'self_lock' \| 'other_lock'` and details if blocked. |
| `POST` | `/dialing/release` | `release()` | Immediately expire the calling agent's own lock for a number. |
| `GET`  | `/dialing/active-locks` | `activeList()` | All currently active locks plus `stats: { active_count, calls_today }`, for the Settings panel. |
| `POST` | `/dialing/clear-lock` | `clearLock()` | Admin: expire one specific lock by `id`. |
| `POST` | `/dialing/clear-all-locks` | `clearAllLocks()` | Admin: expire every currently active lock. |

`calls_today` (in `activeList()`) is computed as
`DialLock::whereDate('locked_at', today())->count()` — i.e. how many
numbers have had a call logged today (one per `phone_key` per day, since
`locked_at` is overwritten on each call).

---

## 7. Settings UI — Settings → Dial Lock Settings

The `#form-dialing` section (`resources/views/settings/list.blade.php`)
provides:

- **Stats pills** — active lock count and calls made today (from
  `GET /dialing/active-locks`).
- **Master enable toggle** — `dialing_lock_enabled`.
- **Same‑agent timer slider** — `dialing_lock_same_user_minutes` (0–60
  min, default 0).
- **Other‑agents timer slider** — `dialing_lock_other_user_minutes` (1–60
  min, default 5).
- **Daily Call Limit & History card** — `dialing_max_calls_per_day` (0–20
  per agent/day, default 3, `0` = unlimited) and `dialing_history_days`
  (1–14 days, default 2).
- **Live Active Locks table** — auto‑refreshes every 5 seconds, with a
  1‑second countdown ticker per row, plus per‑row **Release** and a global
  **Clear All** button (calling `/dialing/clear-lock` and
  `/dialing/clear-all-locks`).

Saving the form posts to `POST /save-dialing-settings`
(`SettingController::saveDialingSettings()`), which upserts all five
`Setting` rows with `group = 'dialing'`.

---

## 8. Click‑to‑dial flow (frontend)

`resources/views/layouts/partials/xplosip-widget.blade.php`:

1. Agent clicks a phone number → `xplosipDial(number)`.
2. `fetchInfo()` calls `GET /dialing/info` and shows a confirmation dialog
   with the call count, last‑called‑by, last‑called‑ago, and the agent's
   "X/Y today" daily count for that number. If `dailyLimitReached` is
   already `true`, the **Call** button is disabled up front.
3. On **Call**, `acquireLock()` calls `POST /dialing/acquire`:
   - If it returns **423**, `showLocked()` displays a live countdown:
     **amber** (`reason: 'self_lock'`) if it's the agent's own re‑dial lock,
     **red** (`reason: 'other_lock'`) if another agent currently holds the
     number, or **blue** (`reason: 'daily_limit'`) if the agent's daily call
     cap for this number has been reached — counting down to the next
     midnight reset.
   - If it returns `ok: true`, `launchDesktop()` opens the `tel:` link to
     start the call via the desktop softphone, and the dialog shows the
     updated call count and daily count.

---

## Note on the old `DIAL_LOCK_MINUTES` env var

`config/services.php` still defines `services.dialing.lock_minutes` from
the `DIAL_LOCK_MINUTES` env var, but **`DialLockController` no longer reads
it** — lock durations are now configured dynamically via Settings → Dial
Lock Settings as described above (with the hardcoded fallbacks `5` and `0`
minutes if those settings rows don't exist). `DIAL_LOCK_MINUTES` can be
left as-is for now but has no effect on dial locking.
