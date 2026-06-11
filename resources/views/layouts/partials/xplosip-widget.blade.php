{{--
    xplosip click-to-dial bridge (desktop softphone).

    Lightweight, dependency-free. When a phone number is clicked anywhere in
    the CRM it shows a confirmation dialog, then launches the installed
    xplosip desktop softphone via the tel: URL scheme to place the call.

    No React / WebRTC / SIP credentials are used in the CRM — dialing happens
    entirely in the desktop xplosip app (registered to voip.ibstec.com:4060).

    Click-to-dial helper for any Blade view that renders a phone number:

        <a href="javascript:void(0)"
           onclick="xplosipDial('{{ $applicant->applicant_phone }}')"
           class="text-primary text-decoration-none" title="Click to dial">
            {{ $applicant->applicant_phone }}
        </a>
--}}
<script>
(function () {
    // ── Server endpoints + CSRF (for the dial-collision lock) ────────────────
    var XP_CSRF        = '{{ csrf_token() }}';
    var XP_ACQUIRE_URL = '{{ route("dialing.acquire", [], false) }}';
    var XP_RELEASE_URL = '{{ route("dialing.release", [], false) }}';
    var XP_INFO_URL    = '{{ route("dialing.info", [], false) }}';
    console.log('[xplosip] click-to-dial bridge loaded (lock+count active)');

    // ── Confirm-dialog styles (injected once) ────────────────────────────────
    if (!document.getElementById('xplosip-confirm-styles')) {
        var st = document.createElement('style');
        st.id = 'xplosip-confirm-styles';
        st.textContent = [
            '#xplosip-confirm-overlay{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);animation:xpcf .12s ease both;}',
            '@keyframes xpcf{from{opacity:0}to{opacity:1}}',
            '#xplosip-confirm-card{width:min(92vw,380px);background:#fff;border-radius:16px;box-shadow:0 24px 48px -12px rgba(15,23,42,.4);padding:22px 22px 18px;font-family:Inter,system-ui,sans-serif;text-align:center;}',
            '#xplosip-confirm-card .xpc-icn{width:52px;height:52px;border-radius:50%;background:#eef4ff;color:#2746dc;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}',
            '#xplosip-confirm-card h3{font-size:15px;font-weight:600;color:#0f172a;margin:0 0 4px;}',
            '#xplosip-confirm-card .xpc-num{font-size:20px;font-weight:700;color:#2746dc;margin:2px 0 6px;letter-spacing:.02em;word-break:break-all;}',
            '#xplosip-confirm-card .xpc-meta{font-size:12px;color:#64748b;margin:0 0 14px;min-height:16px;}',
            '#xplosip-confirm-card .xpc-meta.warn{color:#dc2626;font-weight:600;}',
            '#xplosip-confirm-card .xpc-meta.self-warn{color:#d97706;font-weight:600;}',
            '#xplosip-confirm-card .xpc-btns{display:flex;gap:10px;}',
            '#xplosip-confirm-card button{flex:1;height:42px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:filter .12s;}',
            '#xplosip-confirm-card button:hover{filter:brightness(.96);}',
            '#xplosip-confirm-card .xpc-cancel{background:#f1f5f9;color:#475569;}',
            '#xplosip-confirm-card .xpc-call{background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;gap:7px;}',
            /* Lock screen */
            '#xplosip-confirm-card .xpc-lock-banner{border-radius:10px;padding:12px 14px;margin:8px 0 16px;text-align:left;}',
            '#xplosip-confirm-card .xpc-lock-banner.by-other{background:#fee2e2;border-left:4px solid #dc2626;}',
            '#xplosip-confirm-card .xpc-lock-banner.by-self{background:#fff7ed;border-left:4px solid #d97706;}',
            '#xplosip-confirm-card .xpc-lock-who{font-size:13px;font-weight:700;margin-bottom:4px;}',
            '#xplosip-confirm-card .xpc-lock-detail{font-size:12px;color:#475569;margin-bottom:8px;}',
            '#xplosip-confirm-card .xpc-lock-bar{height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden;margin-bottom:6px;}',
            '#xplosip-confirm-card .xpc-lock-bar-fill{height:100%;border-radius:3px;transition:width .9s linear;}',
            '#xplosip-confirm-card .xpc-lock-bar-fill.by-other{background:#dc2626;}',
            '#xplosip-confirm-card .xpc-lock-bar-fill.by-self{background:#d97706;}',
            '#xplosip-confirm-card .xpc-lock-bar-fill.by-limit{background:#2563eb;}',
            '#xplosip-confirm-card .xpc-lock-banner.by-limit{background:#eff6ff;border-left:4px solid #2563eb;}',
            '#xplosip-confirm-card .xpc-lock-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:2px;}',
            '#xplosip-confirm-card .xpc-lock-timer{font-size:22px;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:.01em;}',
            '#xplosip-confirm-card .xpc-lock-timer.by-other{color:#dc2626;}',
            '#xplosip-confirm-card .xpc-lock-timer.by-self{color:#d97706;}',
            '#xplosip-confirm-card .xpc-lock-timer.by-limit{color:#2563eb;}',
            '@media (prefers-color-scheme: dark){#xplosip-confirm-card{background:#0f172a;}#xplosip-confirm-card h3{color:#f1f5f9;}#xplosip-confirm-card .xpc-cancel{background:#1e293b;color:#cbd5e1;}}'
        ].join('');
        document.head.appendChild(st);
    }

    var _lockCountdown = null;

    function closeConfirm() {
        var o = document.getElementById('xplosip-confirm-overlay');
        if (o) o.remove();
        document.removeEventListener('keydown', onKey);
        if (_lockCountdown) { clearInterval(_lockCountdown); _lockCountdown = null; }
    }
    function onKey(e) { if (e.key === 'Escape') closeConfirm(); }

    // ── Launch the desktop xplosip softphone ─────────────────────────────────
    function launchDesktop(num, count) {
        var clean  = String(num).replace(/[^0-9+*#]/g, '');
        var suffix = count ? ' (call #' + count + ' for this number)' : '';
        xplosipToast('Opening xplosip for ' + num + ' …' + suffix);
        try { window.location.href = 'tel:' + clean; }
        catch (e) { window.open('tel:' + clean, '_self'); }
    }

    // ── Fetch lock/count info for a number ───────────────────────────────────
    function fetchInfo(num, cb) {
        try {
            fetch(XP_INFO_URL + '?number=' + encodeURIComponent(num), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(cb).catch(function () { cb(null); });
        } catch (e) { cb(null); }
    }

    // ── Acquire a server-side dial lock ──────────────────────────────────────
    function acquireLock(num, cb) {
        try {
            fetch(XP_ACQUIRE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': XP_CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ number: num })
            }).then(function (r) {
                return r.json().then(function (j) { return { status: r.status, body: j }; })
                              .catch(function () { return { status: r.status, body: {} }; });
            }).then(function (res) {
                if (res.status === 200 && res.body && res.body.ok) {
                    cb({
                        ok: true,
                        callCount:      res.body.callCount,
                        dailyCallCount: res.body.dailyCallCount,
                        dailyCallLimit: res.body.dailyCallLimit,
                    });
                } else {
                    cb({
                        ok: false,
                        reason:           (res.body && res.body.reason) || (res.body && res.body.lockedBySelf ? 'self_lock' : 'other_lock'),
                        lockedBySelf:     !!(res.body && res.body.lockedBySelf),
                        lockedBy:         (res.body && res.body.lockedBy) || 'Another agent',
                        remainingSeconds: (res.body && res.body.remainingSeconds) || 0,
                        callCount:        (res.body && res.body.callCount) || 0,
                        dailyCallCount:   (res.body && res.body.dailyCallCount) || 0,
                        dailyCallLimit:   (res.body && res.body.dailyCallLimit) || 0,
                        message:          (res.body && res.body.message) || 'This number is currently in use.',
                    });
                }
            }).catch(function () { cb({ ok: true, degraded: true }); });
        } catch (e) { cb({ ok: true, degraded: true }); }
    }

    // ── Format seconds as h:mm, m:ss, or ss ──────────────────────────────────
    function fmtSeconds(s) {
        s = Math.max(0, s);
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var r = s % 60;
        if (h > 0) return h + 'h ' + String(m).padStart(2, '0') + 'm';
        return m > 0 ? m + 'm ' + String(r).padStart(2, '0') + 's' : r + 's';
    }

    // ── Replace dialog with a live "locked" block screen ─────────────────────
    function showLocked(overlay, info) {
        var card = overlay.querySelector('#xplosip-confirm-card');
        if (!card) return;

        var reason      = info.reason || (info.lockedBySelf ? 'self_lock' : 'other_lock');
        var bySelf      = info.lockedBySelf;
        var who         = info.lockedBy || (bySelf ? 'You' : 'Another agent');
        var totalSec    = Math.max(1, info.remainingSeconds || 1);
        var remaining   = info.remainingSeconds || 0;

        var styleClass, headline, subline, timerLabel, iconBg, iconFg;
        if (reason === 'daily_limit') {
            styleClass = 'by-limit';
            headline   = 'Daily call limit reached';
            subline    = "You've called this number " + info.dailyCallCount + '/' + info.dailyCallLimit + ' times today.';
            timerLabel = 'Resets in';
            iconBg = '#eff6ff'; iconFg = '#2563eb';
        } else if (reason === 'self_lock') {
            styleClass = 'by-self';
            headline   = 'You already called this number';
            subline    = 'Your own re-dial lock is active — wait for it to expire.';
            timerLabel = 'Unlocks in';
            iconBg = '#fff7ed'; iconFg = '#d97706';
        } else {
            styleClass = 'by-other';
            headline   = 'Number in use';
            subline    = who + ' is currently on a call to this number.';
            timerLabel = 'Unlocks in';
            iconBg = '#fee2e2'; iconFg = '#dc2626';
        }

        card.innerHTML =
            '<div class="xpc-icn" style="background:' + iconBg + ';color:' + iconFg + ';">'
            + '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>'
            + '<h3>' + headline + '</h3>'
            + '<div class="xpc-lock-banner ' + styleClass + '">'
            +   '<div class="xpc-lock-who">' + (reason === 'daily_limit' ? 'You' : who) + '</div>'
            +   '<div class="xpc-lock-detail">' + subline + '</div>'
            +   '<div class="xpc-lock-bar"><div class="xpc-lock-bar-fill ' + styleClass + '" id="xpc-bar" style="width:100%"></div></div>'
            +   '<div class="xpc-lock-label">' + timerLabel + '</div>'
            +   '<div class="xpc-lock-timer ' + styleClass + '" id="xpc-timer">' + fmtSeconds(remaining) + '</div>'
            + '</div>'
            + (info.callCount > 0 ? '<div style="font-size:11px;color:#94a3b8;margin-bottom:14px">Called ' + info.callCount + '× in total</div>' : '')
            + '<div class="xpc-btns"><button type="button" class="xpc-cancel">Close</button></div>';

        card.querySelector('.xpc-cancel').addEventListener('click', closeConfirm);

        // Live countdown inside the dialog
        var bar   = card.querySelector('#xpc-bar');
        var timer = card.querySelector('#xpc-timer');

        if (_lockCountdown) clearInterval(_lockCountdown);
        _lockCountdown = setInterval(function () {
            remaining = Math.max(0, remaining - 1);
            if (timer) timer.textContent = fmtSeconds(remaining);
            if (bar)   bar.style.width   = Math.round((remaining / totalSec) * 100) + '%';
            if (remaining <= 0) {
                clearInterval(_lockCountdown);
                _lockCountdown = null;
                if (timer) timer.textContent = 'Unlocked';
                if (bar)   bar.style.width   = '0%';
            }
        }, 1000);
    }

    // ── PUBLIC: called by phone-number links throughout the CRM ──────────────
    window.xplosipDial = function (number) {
        if (!number) return;
        var num = String(number).trim();
        closeConfirm();

        var overlay = document.createElement('div');
        overlay.id = 'xplosip-confirm-overlay';
        overlay.innerHTML =
            '<div id="xplosip-confirm-card" role="dialog" aria-modal="true">' +
              '<div class="xpc-icn"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.01 2.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg></div>' +
              '<h3>Place a call with xplosip?</h3>' +
              '<div class="xpc-num"></div>' +
              '<div class="xpc-meta"></div>' +
              '<div class="xpc-btns">' +
                '<button type="button" class="xpc-cancel">Cancel</button>' +
                '<button type="button" class="xpc-call"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.01 2.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg>Call</button>' +
              '</div>' +
            '</div>';
        overlay.querySelector('.xpc-num').textContent = num;
        document.body.appendChild(overlay);

        var meta    = overlay.querySelector('.xpc-meta');
        var callBtn = overlay.querySelector('.xpc-call');

        // Pre-load lock status for this number
        fetchInfo(num, function (info) {
            if (!info) { meta.textContent = ''; return; }
            if (info.locked) {
                var isSelf = info.lockedBySelf;
                meta.className = 'xpc-meta ' + (isSelf ? 'self-warn' : 'warn');
                meta.textContent = isSelf
                    ? '⏳ Your re-dial lock — free in ' + fmtSeconds(info.remainingSeconds)
                    : '⚠ In use by ' + (info.lockedBy || 'another agent') + ' — free in ' + fmtSeconds(info.remainingSeconds);
                callBtn.disabled = true;
                callBtn.style.opacity = '0.5';
                callBtn.style.cursor  = 'not-allowed';
                callBtn.title = isSelf ? 'Your re-dial lock is active' : 'Locked — being called by another agent';

                // Tick the pre-dialog warning down live
                var preCountdown = setInterval(function () {
                    if (!overlay.parentNode) { clearInterval(preCountdown); return; }
                    info.remainingSeconds = Math.max(0, (info.remainingSeconds || 0) - 1);
                    if (info.remainingSeconds <= 0) {
                        clearInterval(preCountdown);
                        meta.textContent = 'Lock may have expired — try calling now.';
                        meta.className   = 'xpc-meta';
                        callBtn.disabled = false;
                        callBtn.style.opacity = '1';
                        callBtn.style.cursor  = 'pointer';
                    } else {
                        meta.textContent = isSelf
                            ? '⏳ Your re-dial lock — free in ' + fmtSeconds(info.remainingSeconds)
                            : '⚠ In use by ' + (info.lockedBy || 'another agent') + ' — free in ' + fmtSeconds(info.remainingSeconds);
                    }
                }, 1000);
            } else if (info.dailyLimitReached) {
                meta.className = 'xpc-meta warn';
                meta.textContent = '🚫 Daily limit reached (' + info.dailyCallCount + '/' + info.dailyCallLimit + ') for this number — resets in ' + fmtSeconds(info.dailyResetSeconds);
                callBtn.disabled = true;
                callBtn.style.opacity = '0.5';
                callBtn.style.cursor  = 'not-allowed';
                callBtn.title = 'Daily call limit reached for this number';
            } else {
                var parts = [];
                if (info.callCount > 0) parts.push('Called ' + info.callCount + '×');
                if (info.lastCalledAgo) parts.push('last ' + info.lastCalledAgo);
                if (info.lastCalledBy)  parts.push('by ' + info.lastCalledBy);
                if (info.dailyCallLimit > 0) parts.push((info.dailyCallCount || 0) + '/' + info.dailyCallLimit + ' today');
                meta.className   = 'xpc-meta';
                meta.textContent = parts.join(' · ');
            }
        });

        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeConfirm(); });
        overlay.querySelector('.xpc-cancel').addEventListener('click', closeConfirm);

        callBtn.addEventListener('click', function () {
            if (callBtn.disabled) return;
            callBtn.disabled = true;
            callBtn.style.opacity = '0.7';
            callBtn.innerHTML = 'Checking…';
            acquireLock(num, function (res) {
                if (res && res.ok) {
                    closeConfirm();
                    launchDesktop(num, res.callCount);
                } else {
                    showLocked(overlay, res || {
                        lockedBySelf: false,
                        lockedBy: 'Another agent',
                        remainingSeconds: 0,
                        message: 'This number is currently in use.',
                    });
                }
            });
        });
        document.addEventListener('keydown', onKey);
    };

    // ── Lightweight toast feedback ───────────────────────────────────────────
    function xplosipToast(msg) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:100001',
            'background:#1f37b6', 'color:#fff', 'padding:11px 16px', 'border-radius:10px',
            'font:600 13px Inter,system-ui,sans-serif',
            'box-shadow:0 8px 24px -6px rgba(15,23,42,.45)', 'max-width:300px',
            'animation:xpcf .15s ease both'
        ].join(';');
        document.body.appendChild(t);
        setTimeout(function () { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; }, 3000);
        setTimeout(function () { t.remove(); }, 3400);
    }
})();
</script>
