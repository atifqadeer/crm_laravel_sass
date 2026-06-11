// Shared chrome: Sidebar, BottomTabs, TopBar, Avatar, StatusDot, etc.

const { useState, useEffect, useRef, useMemo, useCallback, useContext, createContext } = React;

// Compact context — explicit "compact frame?" flag, NOT viewport-based.
// Components key responsive layout off this rather than Tailwind md:/lg:
// classes so the simulated 380px window behaves correctly.
const CompactCtx = createContext(false);
function useCompact() { return useContext(CompactCtx); }

const NAV = [
  { id: 'dialer',   label: 'Dialer',   icon: 'phone' },
  { id: 'history',  label: 'History',  icon: 'clock' },
  { id: 'contacts', label: 'Contacts', icon: 'users' },
  { id: 'messages', label: 'Messages', icon: 'message-square' },
  { id: 'settings', label: 'Settings', icon: 'settings' },
];

function cx(...args) {
  return args.filter(Boolean).join(' ');
}

function StatusDot({ status, className = '' }) {
  // status: registered | registering | failed | available | busy | dnd | offline
  const map = {
    registered: 'bg-emerald-500',
    registering: 'bg-amber-500 soft-pulse',
    failed: 'bg-rose-500',
    available: 'bg-emerald-500',
    busy: 'bg-amber-500',
    dnd: 'bg-rose-500',
    offline: 'bg-slate-400 dark:bg-slate-600',
  };
  return (
    <span
      className={cx(
        'inline-block rounded-full ring-2 ring-white dark:ring-slate-900',
        map[status] || 'bg-slate-400',
        className,
      )}
      style={{ width: 8, height: 8 }}
    />
  );
}

function Avatar({ name, size = 36, presence, square = false }) {
  // Deterministic muted tint per name
  const hues = ['slate','stone','zinc','neutral'];
  const tones = ['from-slate-200 to-slate-300 text-slate-700',
                 'from-stone-200 to-stone-300 text-stone-700',
                 'from-zinc-200 to-zinc-300 text-zinc-700',
                 'from-neutral-200 to-neutral-300 text-neutral-700'];
  const darkTones = ['dark:from-slate-700 dark:to-slate-800 dark:text-slate-200',
                     'dark:from-stone-700 dark:to-stone-800 dark:text-stone-200',
                     'dark:from-zinc-700 dark:to-zinc-800 dark:text-zinc-200',
                     'dark:from-neutral-700 dark:to-neutral-800 dark:text-neutral-200'];
  let h = 0;
  for (const ch of (name || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
  const idx = h % tones.length;
  return (
    <div className="relative shrink-0" style={{ width: size, height: size }}>
      <div
        className={cx(
          'flex items-center justify-center font-medium bg-gradient-to-br',
          tones[idx], darkTones[idx],
          square ? 'rounded-md' : 'rounded-full',
        )}
        style={{ width: size, height: size, fontSize: Math.max(11, size * 0.36) }}
      >
        {initials(name)}
      </div>
      {presence && (
        <span className="absolute -bottom-0.5 -right-0.5">
          <StatusDot status={presence} />
        </span>
      )}
    </div>
  );
}

function Sidebar({ view, setView, account, onPickAccount, onToggleTheme, dark, callActive, onOpenCall, dnd, setDnd, autoAnswer, setAutoAnswer }) {
  const [acctOpen, setAcctOpen] = useState(false);
  return (
    <aside className="flex w-[240px] shrink-0 flex-col border-r border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40">
      {/* Brand */}
      <div className="px-4 pt-5 pb-4 flex items-center gap-2.5">
        <div className="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center shadow-sm">
          <Icon name="phone" size={16} strokeWidth={2.2} className="text-white" />
        </div>
        <div>
          <div className="text-[13px] font-semibold tracking-tight">Acme Voice</div>
          <div className="text-[11px] text-slate-500 dark:text-slate-400">Softphone · v2.4</div>
        </div>
      </div>

      {/* Nav */}
      <nav className="px-2 mt-1 flex flex-col gap-0.5">
        {NAV.map(n => {
          const active = view === n.id;
          return (
            <button
              key={n.id}
              onClick={() => setView(n.id)}
              className={cx(
                'group flex items-center gap-2.5 px-3 py-2 rounded-md text-[13px] font-medium transition',
                active
                  ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-50 shadow-sm border border-slate-200/80 dark:border-slate-700/80'
                  : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-slate-800/60',
              )}
            >
              <Icon name={n.icon} size={16} className={active ? 'text-brand-600 dark:text-brand-400' : 'text-slate-500 dark:text-slate-400'} />
              <span>{n.label}</span>
              {n.id === 'messages' && (
                <span className="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-600 text-white">2</span>
              )}
              {n.id === 'history' && (
                <span className="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-600 dark:text-rose-400">3</span>
              )}
            </button>
          );
        })}
      </nav>

      {/* Active call shortcut */}
      {callActive && (
        <button
          onClick={onOpenCall}
          className="mx-3 mt-3 flex items-center gap-2.5 px-3 py-2 rounded-md bg-emerald-600 text-white text-[13px] font-medium shadow-sm hover:bg-emerald-500 transition"
        >
          <Icon name="phone" size={14} />
          <span>Return to call</span>
          <Icon name="chevron-right" size={14} className="ml-auto" />
        </button>
      )}

      <div className="mt-auto px-3 pb-3 flex flex-col gap-2">
        {/* Quick toggles */}
        <div className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-1">
          <ToggleRow
            label="Do Not Disturb"
            icon="bell-off"
            value={dnd}
            onChange={setDnd}
          />
          <ToggleRow
            label="Auto-Answer"
            icon="phone-incoming"
            value={autoAnswer}
            onChange={setAutoAnswer}
          />
        </div>

        {/* Account selector */}
        <div className="relative">
          <button
            onClick={() => setAcctOpen(v => !v)}
            className="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition text-left"
          >
            <StatusDot status={account.status} />
            <div className="min-w-0 flex-1">
              <div className="text-[12px] font-semibold truncate">{account.label}</div>
              <div className="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">{account.sipUri}</div>
            </div>
            <Icon name="chevrons-up-down" size={14} className="text-slate-400" />
          </button>
          {acctOpen && (
            <div className="absolute bottom-full left-0 right-0 mb-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-lg overflow-hidden">
              {ACCOUNTS.map(a => (
                <button
                  key={a.id}
                  onClick={() => { onPickAccount(a); setAcctOpen(false); }}
                  className="w-full flex items-center gap-2 px-2.5 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800/80"
                >
                  <StatusDot status={a.status} />
                  <div className="min-w-0 flex-1">
                    <div className="text-[12px] font-semibold truncate">{a.label}</div>
                    <div className="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">{a.sipUri}</div>
                  </div>
                  {a.id === account.id && <Icon name="check" size={14} className="text-brand-600" />}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Theme + status row */}
        <div className="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 px-1">
          <Icon name="wifi" size={12} />
          <span className="tnum">G.722 · 38ms · MOS 4.3</span>
          <button
            onClick={onToggleTheme}
            className="ml-auto p-1.5 rounded-md hover:bg-slate-200/60 dark:hover:bg-slate-800 transition"
            title={dark ? 'Switch to light mode' : 'Switch to dark mode'}
          >
            <Icon name={dark ? 'sun' : 'moon'} size={13} />
          </button>
        </div>
      </div>
    </aside>
  );
}

function ToggleRow({ label, icon, value, onChange }) {
  return (
    <button
      onClick={() => onChange(!value)}
      className="w-full flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800/60 transition"
    >
      <Icon name={icon} size={13} className={value ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400'} />
      <span className="text-[12px] font-medium text-slate-700 dark:text-slate-200">{label}</span>
      <span
        className={cx(
          'ml-auto relative inline-flex items-center w-7 h-4 rounded-full transition',
          value ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-700',
        )}
      >
        <span
          className={cx(
            'absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow-sm transition-transform',
            value ? 'translate-x-3' : 'translate-x-0',
          )}
        />
      </span>
    </button>
  );
}

function BottomTabs({ view, setView, callActive, onOpenCall }) {
  return (
    <nav className="flex items-stretch border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-1.5 pt-1.5 pb-[calc(env(safe-area-inset-bottom)+6px)]">
      {NAV.map(n => {
        const active = view === n.id;
        return (
          <button
            key={n.id}
            onClick={() => setView(n.id)}
            className={cx(
              'flex-1 flex flex-col items-center justify-center gap-0.5 py-1.5 rounded-md text-[10px] font-medium transition',
              active
                ? 'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950/40'
                : 'text-slate-500 dark:text-slate-400',
            )}
          >
            <div className="relative">
              <Icon name={n.icon} size={18} strokeWidth={active ? 2 : 1.75} />
              {n.id === 'messages' && (
                <span className="absolute -top-1 -right-2 text-[9px] font-semibold px-1 rounded bg-brand-600 text-white leading-tight">2</span>
              )}
              {n.id === 'history' && (
                <span className="absolute -top-1 -right-2 text-[9px] font-semibold px-1 rounded bg-rose-500 text-white leading-tight">3</span>
              )}
            </div>
            <span>{n.label}</span>
          </button>
        );
      })}
      {callActive && (
        <button
          onClick={onOpenCall}
          className="ml-1 px-2.5 rounded-md bg-emerald-600 text-white flex flex-col items-center justify-center"
          aria-label="Return to call"
        >
          <Icon name="phone" size={16} />
        </button>
      )}
    </nav>
  );
}

function TopBar({ title, subtitle, right, view, account, dark, onToggleTheme }) {
  const compact = useCompact();
  return (
    <header className={cx('h-14 shrink-0 flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/40 backdrop-blur-sm', compact ? 'px-4' : 'px-4 md:px-5')}>
      {compact && (
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-md bg-brand-600 flex items-center justify-center">
            <Icon name="phone" size={13} strokeWidth={2.2} className="text-white" />
          </div>
        </div>
      )}
      <div className="min-w-0">
        <div className="text-[13px] font-semibold tracking-tight truncate">{title}</div>
        {subtitle && <div className="text-[11px] text-slate-500 dark:text-slate-400 truncate">{subtitle}</div>}
      </div>
      <div className="ml-auto flex items-center gap-1.5">
        {/* Compact account/status badge — visible only in compact frame; sidebar carries it otherwise */}
        {compact && (
          <>
            <div className="flex items-center gap-1.5 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
              <StatusDot status={account.status} />
              <span className="text-[11px] font-medium">{account.label}</span>
            </div>
            <button
              onClick={onToggleTheme}
              className="p-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
              title="Toggle theme"
            >
              <Icon name={dark ? 'sun' : 'moon'} size={14} />
            </button>
          </>
        )}
        {right}
      </div>
    </header>
  );
}

function SectionCard({ title, action, children, className = '' }) {
  return (
    <section className={cx('rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60', className)}>
      {(title || action) && (
        <header className="flex items-center justify-between px-4 pt-3.5 pb-2">
          <h3 className="text-[11px] font-semibold tracking-[0.08em] uppercase text-slate-500 dark:text-slate-400">{title}</h3>
          {action}
        </header>
      )}
      {children}
    </section>
  );
}

function PrimaryButton({ children, color = 'brand', icon, onClick, className = '', size = 'md', disabled }) {
  const palette = {
    brand:   'bg-brand-600 hover:bg-brand-500 text-white shadow-sm',
    green:   'bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm',
    red:     'bg-rose-600 hover:bg-rose-500 text-white shadow-sm',
    ghost:   'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-800',
  };
  const sz = size === 'sm' ? 'h-8 px-3 text-[12px]' : 'h-10 px-4 text-[13px]';
  return (
    <button
      onClick={onClick}
      disabled={disabled}
      className={cx('inline-flex items-center justify-center gap-1.5 rounded-md font-medium transition disabled:opacity-50', palette[color], sz, className)}
    >
      {icon && <Icon name={icon} size={14} />}
      {children}
    </button>
  );
}

Object.assign(window, {
  cx, NAV, StatusDot, Avatar, Sidebar, BottomTabs, TopBar, SectionCard, PrimaryButton, ToggleRow,
  CompactCtx, useCompact,
});
