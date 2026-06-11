// CRM floating softphone widget.
//
// Wraps the xplosip App in a collapsible FAB panel positioned fixed
// bottom-right so it overlays any CRM page without modifying route-level
// templates beyond the one-time inclusion in vertical.blade.php.
//
// Public API (attached to window):
//   window.xplosipDial(number)  — opens the widget and pre-fills / places a call
//   window.xplosipOpen()        — opens the widget panel
//   window.xplosipClose()       — closes the widget panel

// ── Styles injected once at load time ─────────────────────────────────────
(function injectStyles() {
  if (document.getElementById('xplosip-widget-styles')) return;
  const s = document.createElement('style');
  s.id = 'xplosip-widget-styles';
  s.textContent = `
    /* Remove the prototype's FrameToolbar — not needed inside the CRM */
    #xplosip-panel [data-frame-toolbar] { display: none !important; }

    /* Container that the widget mounts into */
    #xplosip-root {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 99990;
      font-family: 'Inter var', 'Inter', ui-sans-serif, system-ui, sans-serif;
      font-feature-settings: 'cv11','ss01','ss03';
    }

    /* Floating phone button (FAB) */
    #xplosip-fab {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: #2746dc;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 16px -4px rgba(39,70,220,0.55), 0 2px 6px rgba(0,0,0,0.18);
      transition: background 0.15s, transform 0.12s, box-shadow 0.15s;
      color: #fff;
      flex-direction: column;
      gap: 0;
    }
    #xplosip-fab:hover {
      background: #3c63ee;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px -4px rgba(39,70,220,0.60), 0 3px 8px rgba(0,0,0,0.20);
    }
    #xplosip-fab:active { transform: scale(0.95); }

    /* Badge for missed-call count */
    #xplosip-badge {
      position: absolute;
      top: -3px;
      right: -3px;
      min-width: 18px;
      height: 18px;
      border-radius: 9px;
      background: #e11d48;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 4px;
      border: 2px solid #fff;
      pointer-events: none;
    }

    /* Slide-up / slide-down animation for the panel */
    @keyframes xplosip-slide-in  { from { opacity:0; transform:translateY(16px) scale(0.97); } to { opacity:1; transform:none; } }
    @keyframes xplosip-slide-out { from { opacity:1; transform:none; } to { opacity:0; transform:translateY(16px) scale(0.97); } }
    #xplosip-panel {
      position: absolute;
      bottom: 64px;
      right: 0;
      /* Responsive: never exceed the viewport on small screens */
      width: min(380px, calc(100vw - 32px));
      height: min(640px, calc(100vh - 110px));
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 24px 48px -12px rgba(15,23,42,0.28), 0 8px 20px -8px rgba(15,23,42,0.18);
      border: 1px solid #e2e8f0;
      background: #fff;
      transform-origin: bottom right;
      animation: xplosip-slide-in 0.2s cubic-bezier(0.34,1.3,0.64,1) both;
    }
    /* On phones: take (almost) the full screen as a bottom sheet */
    @media (max-width: 480px) {
      #xplosip-root { bottom: 16px; right: 16px; }
      #xplosip-panel {
        position: fixed;
        bottom: 12px;
        right: 12px;
        left: 12px;
        width: auto;
        height: min(80vh, calc(100vh - 90px));
        border-radius: 16px;
      }
    }
    #xplosip-panel.closing {
      animation: xplosip-slide-out 0.15s ease-in both;
    }
    /* Dark-mode panel border */
    @media (prefers-color-scheme: dark) {
      #xplosip-panel { border-color: #1e293b; background: #0f172a; }
    }
    /* Override: hide the prototype's frame toolbar inside the panel */
    #xplosip-panel .sticky.top-0.z-20 { display: none !important; }
    /* Override: force the app div to fill the panel */
    #xplosip-app-mount { width: 100%; height: 100%; }
    /* Compact tnum / scrollbar utilities must survive isolation */
    #xplosip-panel .tnum { font-variant-numeric: tabular-nums; }
    #xplosip-panel .scrollbar-thin::-webkit-scrollbar { width:6px; height:6px; }
    #xplosip-panel .scrollbar-thin::-webkit-scrollbar-thumb { background:rgba(148,163,184,.4); border-radius:6px; }
    #xplosip-panel .scrollbar-thin::-webkit-scrollbar-track { background:transparent; }
    @keyframes ring-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.55)} 70%{box-shadow:0 0 0 18px rgba(16,185,129,0)} }
    #xplosip-panel .ring-pulse  { animation: ring-pulse 1.6s ease-out infinite; }
    @keyframes soft-pulse { 0%,100%{opacity:1} 50%{opacity:.45} }
    #xplosip-panel .soft-pulse  { animation: soft-pulse 1.8s ease-in-out infinite; }
    #xplosip-panel .keypad-btn  { transition: transform .06s ease, background-color .12s ease, border-color .12s ease; }
    #xplosip-panel .keypad-btn:active { transform: translateY(1px) scale(0.985); }
  `;
  document.head.appendChild(s);
})();

// ── Widget React component ─────────────────────────────────────────────────
function XplosipWidget() {
  const [open, setOpen]           = useState(false);
  const [closing, setClosing]     = useState(false);
  const [dialNumber, setDialNum]  = useState('');
  const appMountRef               = useRef(null);
  const innerRootRef              = useRef(null);

  // Close with slide-out animation
  const closeWidget = useCallback(() => {
    setClosing(true);
    setTimeout(() => { setOpen(false); setClosing(false); }, 140);
  }, []);

  // Open widget (and optionally pre-fill a number)
  const openWidget = useCallback((num) => {
    if (num !== undefined) setDialNum(num);
    setOpen(true);
  }, []);

  // Toggle FAB
  const toggle = useCallback(() => {
    if (open) { closeWidget(); } else { openWidget(); }
  }, [open, openWidget, closeWidget]);

  // Expose global API + listen for the xplosip:open event fired by the
  // vanilla-JS confirm dialog defined in the Blade partial.
  // NOTE: window.xplosipDial is intentionally NOT defined here — the Blade
  // partial owns it (plain JS, shows the confirmation dialog first).
  useEffect(() => {
    function onOpen(e) {
      const num = e && e.detail && e.detail.number;
      if (num) window.__xplosipPendingAutocall = num;
      openWidget(num);
    }
    window.addEventListener('xplosip:open', onOpen);

    window.xplosipOpen  = () => { openWidget(); };
    window.xplosipClose = () => { closeWidget(); };

    // Consume any open request that fired before this widget mounted (race fix)
    if (window.__xplosipPendingAutocall) {
      openWidget(window.__xplosipPendingAutocall);
    }

    return () => {
      window.removeEventListener('xplosip:open', onOpen);
      delete window.xplosipOpen;
      delete window.xplosipClose;
    };
  }, [openWidget, closeWidget]);

  // Mount the inner App into #xplosip-app-mount when the panel opens
  useEffect(() => {
    if (!open || !appMountRef.current) return;

    // Only mount once
    if (innerRootRef.current) return;

    // Re-render with the pre-filled number
    innerRootRef.current = ReactDOM.createRoot(appMountRef.current);
    innerRootRef.current.render(
      <CrmAppShell initialNumber={dialNumber} />
    );
  }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

  // If dialNumber changes after mount:
  //  • Store pending dial/autocall so freshly-mounting App components catch them
  //  • Broadcast dial event (fills input) AND autocall event (triggers placeCall)
  useEffect(() => {
    if (!dialNumber) return;
    window.__xplosipPendingDial     = dialNumber; // fills dialer input on App mount
    window.__xplosipPendingAutocall = dialNumber; // triggers placeCall on App mount
    window.dispatchEvent(new CustomEvent('xplosip:dial',     { detail: { number: dialNumber } }));
    window.dispatchEvent(new CustomEvent('xplosip:autocall', { detail: { number: dialNumber } }));
    setDialNum(''); // reset so next dial fires fresh events
  }, [dialNumber]);

  return (
    <div id="xplosip-root" style={{ position:'fixed', bottom:24, right:24, zIndex:99990 }}>
      {/* Floating action button */}
      <div style={{ position:'relative' }}>
        <button
          id="xplosip-fab"
          onClick={toggle}
          title={open ? 'Close softphone' : 'Open softphone'}
          aria-label="Toggle softphone"
        >
          {open ? (
            /* X / close icon */
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" strokeWidth="2.3" strokeLinecap="round" strokeLinejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          ) : (
            /* Phone icon */
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8 19.79 19.79 0 01.01 2.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/>
            </svg>
          )}
        </button>
      </div>

      {/* Softphone panel */}
      {open && (
        <div id="xplosip-panel" className={closing ? 'closing' : ''}>
          <div id="xplosip-app-mount" ref={appMountRef} style={{ width:'100%', height:'100%' }} />
        </div>
      )}
    </div>
  );
}

// ── Thin CRM-specific shell around the prototype App ──────────────────────
// Forces compact frame mode and listens for xplosip:dial events.
function CrmAppShell({ initialNumber = '' }) {
  useEffect(() => {
    // Force compact frame so the widget fits inside the 380×640 panel.
    try { localStorage.setItem('softphone:frame', JSON.stringify('compact')); } catch (_) {}

    // Inject a phone number into the dialer's text input using React's own
    // internal setter so that onChange fires and state is updated correctly.
    function injectNumber(num) {
      if (!num) return;
      const input = document.querySelector('#xplosip-app-mount input[type="text"]');
      if (!input) return;
      try {
        const nativeSetter = Object.getOwnPropertyDescriptor(
          window.HTMLInputElement.prototype, 'value'
        ).set;
        nativeSetter.call(input, num);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      } catch (_) {
        // Fallback: plain value assignment
        input.value = num;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }

    // Listen for dial events dispatched while this shell is already mounted.
    function onDial(e) {
      injectNumber(e.detail && e.detail.number);
    }
    window.addEventListener('xplosip:dial', onDial);

    // Consume any number that was stored before this shell mounted (race fix).
    // The App renders asynchronously; retry until the input element exists.
    const pending = window.__xplosipPendingDial || initialNumber;
    if (pending) {
      window.__xplosipPendingDial = '';
      let attempts = 0;
      const MAX = 20; // up to 1s (20 × 50ms)
      const tid = setInterval(() => {
        attempts++;
        const input = document.querySelector('#xplosip-app-mount input[type="text"]');
        if (input) {
          clearInterval(tid);
          injectNumber(pending);
        } else if (attempts >= MAX) {
          clearInterval(tid);
        }
      }, 50);
    }

    return () => window.removeEventListener('xplosip:dial', onDial);
  }, []);

  return <App embedded={true} />;
}

// ── Bootstrap ─────────────────────────────────────────────────────────────
(function bootstrap() {
  // Create the widget root element if not already present
  let widgetRoot = document.getElementById('xplosip-widget-root');
  if (!widgetRoot) {
    widgetRoot = document.createElement('div');
    widgetRoot.id = 'xplosip-widget-root';
    document.body.appendChild(widgetRoot);
  }
  const root = ReactDOM.createRoot(widgetRoot);
  root.render(<XplosipWidget />);
})();
