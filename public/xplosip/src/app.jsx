// Root app — orchestrates view routing, call state machine, theme, frame.
//
// NOTE: simulateIncoming is intentionally a no-op stub here.
// The Simulate button is hidden via CSS when running inside the CRM widget.
function simulateIncoming() {}  // stub — real incoming calls come from SIP engine

function useDarkMode() {
  const [dark, setDark] = useState(() => {
    if (typeof window === 'undefined') return false;
    const saved = localStorage.getItem('softphone:theme');
    if (saved === 'dark') return true;
    if (saved === 'light') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  });
  useEffect(() => {
    if (dark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('softphone:theme', dark ? 'dark' : 'light');
  }, [dark]);
  return [dark, () => setDark(d => !d)];
}

// Persist the picked frame mode + view across reloads.
function usePersisted(key, initial) {
  const [v, setV] = useState(() => {
    try { return JSON.parse(localStorage.getItem(key)) ?? initial; } catch { return initial; }
  });
  useEffect(() => {
    try { localStorage.setItem(key, JSON.stringify(v)); } catch {}
  }, [key, v]);
  return [v, setV];
}

function App({ embedded = false }) {
  const [dark, toggleDark] = useDarkMode();
  const [view, setView] = usePersisted('softphone:view', 'dialer');
  const [frame, setFrame] = usePersisted('softphone:frame', 'desktop'); // 'desktop' | 'compact'
  const [account, setAccount] = useState(ACCOUNTS[0]);
  const [number, setNumber] = useState('');
  const [dnd, setDnd] = useState(false);
  const [autoAnswer, setAutoAnswer] = useState(false);

  // call.state: 'ringing-out' | 'ringing-in' | 'connected' | 'ended'
  // call.session: JsSIP RTCSession (null when SIP not available)
  const [call, setCall] = useState(null);
  const [callOpen, setCallOpen] = useState(false);

  // ── Helpers ────────────────────────────────────────────────────────────────
  
  // Send call log to the backend API
  const logCall = useCallback((callData) => {
    if (!callData || !callData.number) {
      console.log('[xplosip] logCall: missing data', callData);
      return;
    }

    const payload = {
      number: callData.number,
      name: callData.contact?.name || callData.name || null,
      direction: callData.direction === 'in' ? 'inbound' : callData.direction === 'out' ? 'outbound' : 'missed',
      duration: callData.duration || 0,
      callId: callData.callId || null,
      calledAt: callData.calledAt || new Date().toISOString(),
    };

    // Get CSRF token from meta tag
    const getCsrfToken = () => {
      const token = document.querySelector('meta[name="csrf-token"]');
      return token ? token.getAttribute('content') : '';
    };

    const csrfToken = getCsrfToken();
    console.log('[xplosip] logCall: sending payload', payload, 'with CSRF:', csrfToken ? 'present' : 'missing');

    fetch('/api/calls/log', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    })
      .then(r => {
        console.log('[xplosip] API response status:', r.status);
        if (!r.ok) {
          console.error('[xplosip] API error status:', r.status, r.statusText);
        }
        return r.json();
      })
      .then(d => {
        console.log('[xplosip] API response body:', d);
        if (d.success) {
          console.log('[xplosip] ✅ Call logged successfully, ID:', d.id);
        } else {
          console.warn('[xplosip] ⚠️ Call logging failed:', d.message);
        }
      })
      .catch(e => {
        console.error('[xplosip] ❌ Call logging error:', e.message, e);
      });
  }, []);

  const _resolveEnded = useCallback((callData) => {
    console.log('[xplosip] _resolveEnded called with:', callData);
    // Log the call before clearing it
    if (callData) {
      logCall(callData);
    }
    setTimeout(() => {
      setCall(c => (c && c.state === 'ended') ? null : c);
      setCallOpen(false);
    }, 1800);
  }, [logCall]);

  // ── SIP engine initialisation ──────────────────────────────────────────────
  useEffect(() => {
    if (!window.XplosipSIP || !window.SIP_CONFIG) return;

    // Skip browser registration if no extension/password is configured for this
    // agent — avoids endless WebSocket retry errors when WebRTC isn't set up.
    // Click-to-dial then automatically falls back to the desktop softphone.
    if (!window.SIP_CONFIG.extension || !window.SIP_CONFIG.password) {
      console.info('[xplosip] No SIP extension configured — browser softphone idle; click-to-dial will use the desktop softphone.');
      setAccount(prev => ({ ...prev, status: 'not-configured' }));
      return;
    }

    // Set account status to 'registering' while connecting
    setAccount(prev => ({ ...prev, status: 'registering' }));

    XplosipSIP.init(window.SIP_CONFIG, {

      onRegistered: function (status) {
        setAccount(prev => ({
          ...prev,
          status: status === 'registered' ? 'registered' : 'failed',
        }));
      },

      onRegistrationFailed: function (cause) {
        console.warn('[xplosip] Registration failed:', cause);
        setAccount(prev => ({ ...prev, status: 'failed' }));
      },

      onIncomingCall: function (callerNumber, session) {
        if (dnd) {
          // Do-not-disturb: reject immediately
          try { session.terminate({ status_code: 486, reason_phrase: 'Busy Here' }); } catch (_) {}
          return;
        }
        const contact = contactFor(callerNumber);
        setCall({ number: callerNumber, contact, direction: 'in', state: 'ringing-in', startedAt: null, session });
        setCallOpen(true);
        // Auto-answer if enabled
        if (autoAnswer) {
          setTimeout(function () {
            XplosipSIP.answer(session);
            setCall(c => c ? { ...c, state: 'connected', startedAt: Date.now() } : c);
          }, 800);
        }
      },

      onCallState: function (state, cause, session) {
        console.log('[xplosip] onCallState:', state, 'cause:', cause);
        setCall(c => {
          if (!c) return c;
          if (state === 'connected') return { ...c, state: 'connected', startedAt: Date.now() };
          if (state === 'ended') {
            // Log the call when it ends naturally
            const duration = c.startedAt ? Math.floor((Date.now() - c.startedAt) / 1000) : 0;
            const callDataToLog = {
              number: c.number,
              contact: c.contact,
              name: c.contact?.name,
              direction: c.direction,
              duration,
              startedAt: c.startedAt,
            };
            console.log('[xplosip] onCallState(ended): logging call', callDataToLog);
            _resolveEnded(callDataToLog);
            return { ...c, state: 'ended' };
          }
          return { ...c, state };
        });
      },

      onHoldChange: function (held) {
        // hold state managed in ActiveCall via SIP session directly
      },

      onMuteChange: function (muted) {
        // mute state managed in ActiveCall via SIP session directly
      },
    });

    return function cleanup() {
      XplosipSIP.stop();
    };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Place outbound call ────────────────────────────────────────────────────
  const placeCall = useCallback((num) => {
    const contact = contactFor(num);
    // Update UI immediately so user gets instant feedback
    setCall({ number: num, contact, direction: 'out', state: 'ringing-out', startedAt: null, session: null });
    setCallOpen(true);

    if (window.XplosipSIP && window.SIP_CONFIG) {
      // Real SIP call via JsSIP
      const session = XplosipSIP.call(num);
      if (session) {
        setCall(c => c ? { ...c, session } : c);
      }
    }
    // If XplosipSIP not available, UI stays in 'ringing-out' — developer mode
  }, []);

  // ── Answer incoming call ───────────────────────────────────────────────────
  const answer = useCallback(() => {
    setCall(c => {
      if (!c) return c;
      // "Call back" after ended
      if (c.state === 'ended') {
        placeCall(c.number);
        return null; // placeCall sets new state
      }
      // Answer real incoming call
      if (c.state === 'ringing-in' && window.XplosipSIP) {
        XplosipSIP.answer(c.session);
      }
      return { ...c, state: 'connected', startedAt: Date.now() };
    });
  }, [placeCall]);

  // ── Hang up / decline ─────────────────────────────────────────────────────
  const hangup = useCallback(() => {
    console.log('[xplosip] hangup called');
    setCall(c => {
      if (!c) return c;
      if (window.XplosipSIP && c.session) {
        console.log('[xplosip] Terminating SIP session');
        XplosipSIP.hangup(c.session);
      }
      // Log the call before marking as ended
      const duration = c.startedAt ? Math.floor((Date.now() - c.startedAt) / 1000) : 0;
      const callDataToLog = {
        number: c.number,
        contact: c.contact,
        name: c.contact?.name,
        direction: c.direction,
        duration,
        startedAt: c.startedAt,
      };
      console.log('[xplosip] hangup: logging call', callDataToLog);
      _resolveEnded(callDataToLog);
      return { ...c, state: 'ended' };
    });
  }, [_resolveEnded]);

  const onCallFromAnywhere = useCallback((num) => {
    setNumber(num);
    placeCall(num);
  }, [placeCall]);

  // ── Click-to-dial autocall bridge ──────────────────────────────────────────
  // When the CRM fires xplosip:autocall (from a phone-number link), place
  // the call immediately without requiring the user to press "Call" manually.
  const placeCallRef = useRef(placeCall);
  useEffect(() => { placeCallRef.current = placeCall; }, [placeCall]);

  useEffect(() => {
    function doAutocall(num) {
      if (!num) return;
      setCallOpen(true);
      if (placeCallRef.current) placeCallRef.current(num);
    }

    // Consume a pending autocall that was stored before this App mounted
    const pending = window.__xplosipPendingAutocall;
    if (pending) {
      window.__xplosipPendingAutocall = '';
      setTimeout(() => doAutocall(pending), 120); // small delay for full mount
    }

    function onAutocall(e) { doAutocall(e.detail && e.detail.number); }
    window.addEventListener('xplosip:autocall', onAutocall);
    return () => window.removeEventListener('xplosip:autocall', onAutocall);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Title/subtitle per view
  const header = useMemo(() => {
    switch (view) {
      case 'dialer':   return { title: 'Dialer',    subtitle: 'Place a call or pick from recents' };
      case 'history':  return { title: 'Call history', subtitle: '12 calls in the last 7 days' };
      case 'contacts': return { title: 'Contacts',  subtitle: `${CONTACTS.length} people · ${CONTACTS.filter(c=>c.favorite).length} favorites` };
      case 'messages': return { title: 'Messages',  subtitle: 'SIP SIMPLE · end-to-end' };
      case 'settings': return { title: 'Settings',  subtitle: 'SIP, audio, codecs, ringtones' };
      default: return { title: '', subtitle: '' };
    }
  }, [view]);

  // Embedded (inside the CRM widget panel) always uses the compact layout.
  const compact = embedded || frame === 'compact';

  // Choose visible recents for the dialer rail
  const recent = useMemo(() => HISTORY.slice(0, 12), []);

  // Render the app shell
  const mainContent = (
    <div className={cx(
      'h-full w-full flex',
      compact ? 'flex-col' : 'flex-row',
    )}>
      {!compact && (
        <Sidebar
          view={view}
          setView={setView}
          account={account}
          onPickAccount={setAccount}
          onToggleTheme={toggleDark}
          dark={dark}
          callActive={!!call && callOpen === false}
          onOpenCall={() => setCallOpen(true)}
          dnd={dnd}
          setDnd={setDnd}
          autoAnswer={autoAnswer}
          setAutoAnswer={setAutoAnswer}
        />
      )}
      <main className="flex-1 min-w-0 flex flex-col relative bg-white dark:bg-slate-950">
        <TopBar
          title={header.title}
          subtitle={header.subtitle}
          view={view}
          account={account}
          dark={dark}
          onToggleTheme={toggleDark}
          right={null}
        />

        <div className="flex-1 min-h-0 flex flex-col overflow-hidden">
          {view === 'dialer' && <Dialer number={number} setNumber={setNumber} onCall={placeCall} account={account} recent={recent} />}
          {view === 'history' && <History onCall={onCallFromAnywhere} />}
          {view === 'contacts' && <Contacts onCall={onCallFromAnywhere} />}
          {view === 'messages' && <Messages onCall={onCallFromAnywhere} />}
          {view === 'settings' && (
            <Settings
              account={account}
              accounts={ACCOUNTS}
              dark={dark}
              onToggleTheme={toggleDark}
              dnd={dnd}
              setDnd={setDnd}
              autoAnswer={autoAnswer}
              setAutoAnswer={setAutoAnswer}
            />
          )}
        </div>

        {/* Active call overlay */}
        {call && callOpen && (
          <ActiveCall
            call={call}
            onAnswer={answer}
            onHangup={hangup}
            onClose={() => setCallOpen(false)}
          />
        )}

        {/* Compact bottom tabs */}
        {compact && (
          <BottomTabs
            view={view}
            setView={setView}
            callActive={!!call && !callOpen}
            onOpenCall={() => setCallOpen(true)}
          />
        )}
      </main>
    </div>
  );

  // EMBEDDED (inside the CRM floating widget): render ONLY the inner content,
  // filling 100% of the panel — no full-page frame, no bezel, no toolbar.
  if (embedded) {
    return (
      <CompactCtx.Provider value={true}>
        <div className="h-full w-full bg-white dark:bg-slate-950 overflow-hidden">
          {mainContent}
        </div>
      </CompactCtx.Provider>
    );
  }

  // FRAME: desktop fills viewport; compact uses a small phone-window mockup
  return (
    <CompactCtx.Provider value={compact}>
      <div className="min-h-screen w-full bg-slate-100 dark:bg-slate-950">
        <FrameToolbar frame={frame} setFrame={setFrame} dark={dark} onToggleTheme={toggleDark} onSimulate={simulateIncoming} />
        <div className="px-3 md:px-6 pb-6">
          {frame === 'desktop' ? (
            <div className="mx-auto max-w-[1240px]">
              <div className="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-950 app-bezel" style={{ height: 'min(86vh, 820px)' }}>
                {mainContent}
              </div>
              <p className="text-center text-[11px] text-slate-400 dark:text-slate-500 mt-3">
                Use ← Compact mode to preview the always-on-top mini softphone window.
              </p>
            </div>
          ) : (
            <div className="mx-auto mt-2 flex justify-center">
              <div
                className="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-950 app-bezel"
                style={{ width: 380, height: 680 }}
              >
                {mainContent}
              </div>
            </div>
          )}
        </div>
      </div>
    </CompactCtx.Provider>
  );
}

function FrameToolbar({ frame, setFrame, dark, onToggleTheme, onSimulate }) {
  return (
    <div className="sticky top-0 z-20 backdrop-blur bg-slate-100/80 dark:bg-slate-950/80 border-b border-slate-200/60 dark:border-slate-800/60">
      <div className="mx-auto max-w-[1240px] px-3 md:px-6 h-12 flex items-center gap-2">
        <div className="text-[12px] font-semibold tracking-tight flex items-center gap-1.5">
          <span className="w-5 h-5 rounded-md bg-brand-600 inline-flex items-center justify-center">
            <Icon name="phone" size={11} className="text-white" strokeWidth={2.4} />
          </span>
          Acme Voice — Softphone Prototype
        </div>
        <div className="ml-auto flex items-center gap-1.5">
          <div className="flex rounded-md border border-slate-200 dark:border-slate-800 overflow-hidden text-[11.5px] font-medium bg-white dark:bg-slate-900">
            <button
              onClick={() => setFrame('desktop')}
              className={cx('px-2.5 h-8 flex items-center gap-1.5', frame === 'desktop' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-600 dark:text-slate-300')}
            >
              <Icon name="monitor" size={13} /> Desktop
            </button>
            <button
              onClick={() => setFrame('compact')}
              className={cx('px-2.5 h-8 flex items-center gap-1.5', frame === 'compact' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'text-slate-600 dark:text-slate-300')}
            >
              <Icon name="smartphone" size={13} /> Compact
            </button>
          </div>
          <button
            onClick={onSimulate}
            className="hidden sm:inline-flex items-center gap-1.5 h-8 px-2.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-[11.5px] font-medium hover:bg-slate-50 dark:hover:bg-slate-800"
            title="Simulate an incoming call"
          >
            <span className="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 soft-pulse" />
            Simulate inbound
          </button>
          <button
            onClick={onToggleTheme}
            className="inline-flex items-center justify-center h-8 w-8 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800"
            title="Toggle theme"
          >
            <Icon name={dark ? 'sun' : 'moon'} size={13} />
          </button>
        </div>
      </div>
    </div>
  );
}

// Only self-mount when running as the standalone prototype (page has #root).
// Inside the CRM widget, CrmAppShell mounts <App /> directly — don't double-mount.
(function () {
  const rootEl = document.getElementById('root');
  if (rootEl) {
    ReactDOM.createRoot(rootEl).render(<App />);
  }
})();
