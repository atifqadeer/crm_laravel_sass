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
    // Relative URLs — always same-origin/scheme as the loaded page (avoids
    // cross-origin / http-vs-https fetch failures).
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
            '#xplosip-confirm-card{width:min(92vw,360px);background:#fff;border-radius:16px;box-shadow:0 24px 48px -12px rgba(15,23,42,.4);padding:22px 22px 18px;font-family:Inter,system-ui,sans-serif;text-align:center;}',
            '#xplosip-confirm-card .xpc-icn{width:52px;height:52px;border-radius:50%;background:#eef4ff;color:#2746dc;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;}',
            '#xplosip-confirm-card h3{font-size:15px;font-weight:600;color:#0f172a;margin:0 0 4px;}',
            '#xplosip-confirm-card .xpc-num{font-size:20px;font-weight:700;color:#2746dc;margin:2px 0 6px;letter-spacing:.02em;word-break:break-all;}',
            '#xplosip-confirm-card .xpc-meta{font-size:12px;color:#64748b;margin:0 0 14px;min-height:16px;}',
            '#xplosip-confirm-card .xpc-meta.warn{color:#dc2626;font-weight:600;}',
            '#xplosip-confirm-card .xpc-btns{display:flex;gap:10px;}',
            '#xplosip-confirm-card button{flex:1;height:42px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:filter .12s;}',
            '#xplosip-confirm-card button:hover{filter:brightness(.96);}',
            '#xplosip-confirm-card .xpc-cancel{background:#f1f5f9;color:#475569;}',
            '#xplosip-confirm-card .xpc-call{background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;gap:7px;}',
            '@media (prefers-color-scheme: dark){#xplosip-confirm-card{background:#0f172a;}#xplosip-confirm-card h3{color:#f1f5f9;}#xplosip-confirm-card .xpc-cancel{background:#1e293b;color:#cbd5e1;}}'
        ].join('');
        document.head.appendChild(st);
    }

    function closeConfirm() {
        var o = document.getElementById('xplosip-confirm-overlay');
        if (o) o.remove();
        document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') closeConfirm(); }

    // ── Launch the desktop xplosip softphone to dial ─────────────────────────
    function launchDesktop(num, count) {
        var clean  = String(num).replace(/[^0-9+*#]/g, '');
        var suffix = count ? ' (call #' + count + ' for this number)' : '';
        xplosipToast('Opening xplosip for ' + num + ' …' + suffix);
        try { window.location.href = 'tel:' + clean; }
        catch (e) { window.open('tel:' + clean, '_self'); }
    }

    // ── Read current count / timing / lock status for a number ───────────────
    function fetchInfo(num, cb) {
        try {
            fetch(XP_INFO_URL + '?number=' + encodeURIComponent(num), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(cb).catch(function () { cb(null); });
        } catch (e) { cb(null); }
    }

    // ── Acquire a server-side dial lock so two agents can't call the same
    //    number at once. Fails OPEN (allows the call) if the service is down. ──
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
                if (res.status === 200 && res.body && res.body.ok) cb({ ok: true, callCount: res.body.callCount });
                else cb({ ok: false, message: (res.body && res.body.message) || 'This number is currently being called by another agent.' });
            }).catch(function () {
                cb({ ok: true, degraded: true });   // network error -> allow
            });
        } catch (e) {
            cb({ ok: true, degraded: true });
        }
    }

    // ── Replace the dialog body with a "number in use" block message ─────────
    function showLocked(overlay, msg) {
        var card = overlay.querySelector('#xplosip-confirm-card');
        if (!card) return;
        card.innerHTML =
            '<div class="xpc-icn" style="background:#fee2e2;color:#dc2626;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>' +
            '<h3>Number in use</h3>' +
            '<div class="xpc-lockmsg" style="font-size:13px;color:#64748b;margin:6px 0 16px;"></div>' +
            '<div class="xpc-btns"><button type="button" class="xpc-cancel">OK</button></div>';
        card.querySelector('.xpc-lockmsg').textContent = msg;
        card.querySelector('.xpc-cancel').addEventListener('click', closeConfirm);
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

        // Pre-load count / timing / lock status for this number
        fetchInfo(num, function (info) {
            if (!info) { meta.textContent = ''; return; }
            if (info.locked) {
                meta.className = 'xpc-meta warn';
                meta.textContent = '⚠ In use by ' + (info.lockedBy || 'another agent')
                                 + ' — free in ' + (info.remainingSeconds || 0) + 's';
                callBtn.disabled = true;
                callBtn.style.opacity = '0.5';
                callBtn.style.cursor = 'not-allowed';
                callBtn.title = 'Locked — being called by another agent';
            } else {
                var parts = [];
                if (info.callCount > 0) parts.push('Called ' + info.callCount + '×');
                if (info.lastCalledAgo) parts.push('last ' + info.lastCalledAgo);
                meta.className = 'xpc-meta';
                meta.textContent = parts.join(' · ');
            }
        });

        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeConfirm(); });
        overlay.querySelector('.xpc-cancel').addEventListener('click', closeConfirm);

        callBtn.addEventListener('click', function () {
            if (callBtn.disabled) return;
            // Check the dial lock first — block if the number is in use
            callBtn.disabled = true;
            callBtn.style.opacity = '0.7';
            callBtn.innerHTML = 'Checking…';
            acquireLock(num, function (res) {
                if (res && res.ok) {
                    closeConfirm();
                    launchDesktop(num, res.callCount);
                } else {
                    showLocked(overlay, (res && res.message) || 'This number is being called by another agent.');
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
