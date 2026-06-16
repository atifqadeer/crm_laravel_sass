// Dialer view — keypad + dial input + recent suggestions.

function Dialer({ number, setNumber, onCall, account, recent }) {
  const inputRef = useRef(null);
  const compact = useCompact();

  // Keyboard input
  useEffect(() => {
    function handler(e) {
      if (e.target && e.target.tagName === 'INPUT') return;
      if (/^[0-9*#+]$/.test(e.key)) {
        setNumber(n => (n + e.key).slice(0, 32));
      } else if (e.key === 'Backspace') {
        setNumber(n => n.slice(0, -1));
      } else if (e.key === 'Enter' && number) {
        onCall(number);
      }
    }
    window.addEventListener('keydown', handler);
    return () => window.removeEventListener('keydown', handler);
  }, [number, setNumber, onCall]);

  const press = (d) => setNumber(n => (n + d).slice(0, 32));
  const back = () => setNumber(n => n.slice(0, -1));
  const clear = () => setNumber('');

  const suggestion = useMemo(() => {
    if (!number || number.length < 2) return null;
    const norm = number.replace(/\D/g, '');
    return CONTACTS.find(c => c.number.replace(/\D/g, '').endsWith(norm)) || null;
  }, [number]);

  return (
    <div className={cx('flex-1 min-h-0 flex gap-4 p-4', compact ? 'flex-col' : 'flex-col lg:flex-row md:p-5')}>
      {/* Left: keypad */}
      <div className="flex-1 min-w-0 max-w-[460px] mx-auto w-full flex flex-col">
        {/* Display */}
        <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 px-4 py-4">
          <div className="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mb-1">
            <Icon name="phone-outgoing" size={11} />
            <span>Outgoing via</span>
            <span className="font-medium text-slate-700 dark:text-slate-200">{account.label}</span>
            <StatusDot status={account.status} className="ml-0.5" />
          </div>
          <div className="flex items-center gap-2 min-h-[44px]">
            <input
              ref={inputRef}
              type="text"
              value={number}
              onChange={e => setNumber(e.target.value)}
              placeholder="Enter number or SIP URI"
              className="flex-1 bg-transparent outline-none text-[26px] md:text-[30px] font-semibold tnum tracking-tight placeholder:text-slate-300 dark:placeholder:text-slate-600"
            />
            {number && (
              <button onClick={back} title="Backspace" className="p-2 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <Icon name="delete" size={18} />
              </button>
            )}
          </div>
          {suggestion ? (
            <div className="mt-2 flex items-center gap-2 text-[12px]">
              <Avatar name={suggestion.name} size={22} />
              <div className="min-w-0">
                <div className="font-medium truncate">{suggestion.name}</div>
                <div className="text-slate-500 dark:text-slate-400 truncate text-[11px]">{suggestion.org}</div>
              </div>
            </div>
          ) : (
            <div className="mt-2 h-[22px] text-[11px] text-slate-400 dark:text-slate-500">
              {number ? 'No matching contact' : 'Tip: type on your keyboard to dial'}
            </div>
          )}
        </div>

        {/* Keypad */}
        <div className="mt-4 grid grid-cols-3 gap-2.5">
          {KEYPAD.map(({ d, s }) => (
            <button
              key={d}
              onClick={() => press(d)}
              className="keypad-btn h-16 sm:h-[68px] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:bg-slate-100 dark:active:bg-slate-800 flex flex-col items-center justify-center"
            >
              <span className="text-[24px] font-semibold tnum leading-none">{d}</span>
              <span className="text-[9.5px] tracking-[0.18em] font-medium text-slate-400 dark:text-slate-500 mt-0.5 h-2">{s}</span>
            </button>
          ))}
        </div>

        {/* Call row */}
        <div className="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
          <button
            onClick={clear}
            className="h-12 rounded-xl text-[12px] font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition disabled:opacity-40"
            disabled={!number}
          >
            Clear
          </button>
          <button
            onClick={() => number && onCall(number)}
            disabled={!number}
            className={cx(
              'h-14 w-14 rounded-full flex items-center justify-center text-white shadow-md transition',
              number ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed',
            )}
            aria-label="Call"
            title="Call (Enter)"
          >
            <Icon name="phone" size={22} strokeWidth={2.2} />
          </button>
          <button
            onClick={back}
            disabled={!number}
            className="h-12 rounded-xl text-[12px] font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition disabled:opacity-40"
          >
            Backspace
          </button>
        </div>
      </div>

      {/* Right rail: recents (hidden in compact mode where History tab exists) */}
      {!compact && (
        <aside className="hidden lg:flex w-[300px] xl:w-[340px] shrink-0 flex-col gap-3">
        <SectionCard
          title="Recent"
          action={<button className="text-[11px] text-brand-600 dark:text-brand-400 font-medium hover:underline">View all</button>}
        >
          <ul className="px-1 pb-2">
            {recent.slice(0, 8).map(h => {
              const c = contactFor(h.contactId || h.number);
              const name = c ? c.name : h.number;
              const Icn = h.direction === 'in' ? (h.status === 'missed' ? 'phone-missed' : 'phone-incoming') : 'phone-outgoing';
              const iconColor =
                h.status === 'missed' ? 'text-rose-500' :
                h.direction === 'in' ? 'text-emerald-500' : 'text-slate-400';
              return (
                <li key={h.id}>
                  <button
                    onClick={() => onCall(h.number)}
                    className="w-full flex items-center gap-2.5 px-3 py-2 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800/60 transition text-left"
                  >
                    <Avatar name={name} size={32} />
                    <div className="min-w-0 flex-1">
                      <div className={cx('text-[13px] font-medium truncate', h.status === 'missed' && 'text-rose-600 dark:text-rose-400')}>
                        {name}
                      </div>
                      <div className="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <Icon name={Icn} size={11} className={iconColor} />
                        <span className="truncate">{h.number}</span>
                      </div>
                    </div>
                    <div className="text-[11px] text-slate-400 dark:text-slate-500 tnum shrink-0">{fmtRelative(h.ts)}</div>
                  </button>
                </li>
              );
            })}
          </ul>
        </SectionCard>

        <SectionCard title="Favorites">
          <ul className="px-1 pb-2">
            {CONTACTS.filter(c => c.favorite).slice(0, 5).map(c => (
              <li key={c.id}>
                <button
                  onClick={() => onCall(c.number)}
                  className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-md hover:bg-slate-50 dark:hover:bg-slate-800/60 transition text-left"
                >
                  <Avatar name={c.name} size={28} presence={c.presence} />
                  <div className="min-w-0 flex-1">
                    <div className="text-[12.5px] font-medium truncate">{c.name}</div>
                    <div className="text-[10.5px] text-slate-500 dark:text-slate-400 truncate">{c.number}</div>
                  </div>
                  <Icon name="phone" size={13} className="text-slate-400" />
                </button>
              </li>
            ))}
          </ul>
        </SectionCard>
      </aside>
      )}
    </div>
  );
}

window.Dialer = Dialer;
