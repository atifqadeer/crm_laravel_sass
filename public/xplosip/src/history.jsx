// Call history view with filter pills.

function History({ onCall }) {
  const compact = useCompact();
  const [filter, setFilter] = useState('all');
  const filters = [
    { id: 'all',     label: 'All' },
    { id: 'missed',  label: 'Missed' },
    { id: 'in',      label: 'Incoming' },
    { id: 'out',     label: 'Outgoing' },
  ];

  const list = useMemo(() => {
    return HISTORY.filter(h => {
      if (filter === 'all') return true;
      if (filter === 'missed') return h.status === 'missed';
      return h.direction === filter;
    });
  }, [filter]);

  // Group by day bucket
  const groups = useMemo(() => {
    const out = [];
    let curKey = null;
    for (const h of list) {
      const d = new Date(h.ts);
      const today = new Date();
      const isToday = d.toDateString() === today.toDateString();
      const isYesterday = (today - d) < 2 * 86400e3 && !isToday && (today.getDate() - d.getDate()) === 1;
      const key = isToday ? 'Today' : isYesterday ? 'Yesterday' : d.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' });
      if (key !== curKey) { out.push({ key, items: [] }); curKey = key; }
      out[out.length - 1].items.push(h);
    }
    return out;
  }, [list]);

  return (
    <div className="flex-1 min-h-0 flex flex-col">
      {/* Filter row */}
      <div className="flex items-center gap-1.5 px-4 md:px-5 py-3 border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-thin">
        {filters.map(f => (
          <button
            key={f.id}
            onClick={() => setFilter(f.id)}
            className={cx(
              'px-3 h-7 rounded-full text-[12px] font-medium transition whitespace-nowrap',
              filter === f.id
                ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800',
            )}
          >
            {f.label}
          </button>
        ))}
        <div className={cx('ml-auto text-[11px] text-slate-400 dark:text-slate-500', compact && 'hidden')}>{list.length} calls</div>
      </div>

      <div className="flex-1 min-h-0 overflow-y-auto scrollbar-thin">
        {groups.map(g => (
          <section key={g.key} className="px-4 md:px-5 pt-4">
            <h4 className="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-slate-400 dark:text-slate-500 mb-1">{g.key}</h4>
            <ul className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
              {g.items.map(h => {
                const c = contactFor(h.contactId || h.number);
                const name = c ? c.name : h.number;
                const Icn = h.direction === 'in' ? (h.status === 'missed' ? 'phone-missed' : 'phone-incoming') : 'phone-outgoing';
                const iconColor =
                  h.status === 'missed' ? 'text-rose-500' :
                  h.direction === 'in' ? 'text-emerald-500' : 'text-slate-400';
                return (
                  <li key={h.id} className="group flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                    <Avatar name={name} size={36} />
                    <div className="min-w-0 flex-1">
                      <div className={cx('text-[13.5px] font-medium truncate', h.status === 'missed' && 'text-rose-600 dark:text-rose-400')}>
                        {name}
                      </div>
                      <div className="text-[11.5px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <Icon name={Icn} size={11} className={iconColor} />
                        <span className="truncate">{c ? h.number : (h.direction === 'in' ? 'Incoming' : 'Outgoing')}</span>
                        {h.status === 'answered' && (
                          <>
                            <span className="text-slate-300 dark:text-slate-700">·</span>
                            <span className="tnum">{fmtDuration(h.duration)}</span>
                          </>
                        )}
                        {h.status === 'no-answer' && (
                          <>
                            <span className="text-slate-300 dark:text-slate-700">·</span>
                            <span>No answer</span>
                          </>
                        )}
                      </div>
                    </div>
                    <div className={cx('text-[11px] text-slate-400 dark:text-slate-500 tnum tabular-nums', compact && 'hidden')}>
                      {new Date(h.ts).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}
                    </div>
                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                      <button
                        onClick={() => onCall(h.number)}
                        className="p-1.5 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                        title="Redial"
                      >
                        <Icon name="phone" size={15} />
                      </button>
                      <button className="p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Message">
                        <Icon name="message-square" size={15} />
                      </button>
                      <button className="p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="More">
                        <Icon name="more-horizontal" size={15} />
                      </button>
                    </div>
                  </li>
                );
              })}
            </ul>
          </section>
        ))}
        <div className="h-6" />
      </div>
    </div>
  );
}

window.History = History;
