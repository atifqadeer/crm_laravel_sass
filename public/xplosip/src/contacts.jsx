// Contacts view: search + list, with favorites at the top.

function Contacts({ onCall }) {
  const compact = useCompact();
  const [q, setQ] = useState('');
  const [filter, setFilter] = useState('all');

  const filtered = useMemo(() => {
    const norm = q.trim().toLowerCase();
    let list = CONTACTS;
    if (filter === 'favorites') list = list.filter(c => c.favorite);
    if (filter === 'available') list = list.filter(c => c.presence === 'available');
    if (!norm) return list;
    return list.filter(c =>
      c.name.toLowerCase().includes(norm) ||
      c.org.toLowerCase().includes(norm) ||
      c.number.toLowerCase().includes(norm)
    );
  }, [q, filter]);

  // Sort: favorites first, then alpha
  const sorted = useMemo(() => {
    return [...filtered].sort((a, b) => {
      if (a.favorite !== b.favorite) return a.favorite ? -1 : 1;
      return a.name.localeCompare(b.name);
    });
  }, [filtered]);

  return (
    <div className="flex-1 min-h-0 flex flex-col">
      {/* Search */}
      <div className="px-4 md:px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
        <div className="relative flex-1">
          <Icon name="search" size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            value={q}
            onChange={e => setQ(e.target.value)}
            placeholder="Search contacts, numbers, organizations"
            className="w-full h-9 pl-8 pr-3 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-transparent focus:bg-white dark:focus:bg-slate-900 focus:border-slate-300 dark:focus:border-slate-700 outline-none text-[13px]"
          />
        </div>
        <button className={cx('items-center gap-1.5 h-9 px-3 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 text-[12px] font-medium hover:bg-slate-50 dark:hover:bg-slate-800', compact ? 'hidden' : 'inline-flex')}>
          <Icon name="user-plus" size={14} /> Add contact
        </button>
      </div>

      {/* Pills */}
      <div className="flex items-center gap-1.5 px-4 md:px-5 py-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-thin">
        {[
          { id: 'all', label: 'All' },
          { id: 'favorites', label: 'Favorites' },
          { id: 'available', label: 'Available now' },
        ].map(f => (
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
        <div className={cx('ml-auto text-[11px] text-slate-400 dark:text-slate-500', compact && 'hidden')}>{sorted.length} contacts</div>
      </div>

      <div className="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-4 md:px-5 py-3">
        <ul className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden">
          {sorted.map(c => (
            <li key={c.id} className="group flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
              <Avatar name={c.name} size={36} presence={c.presence} />
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-1.5">
                  <span className="text-[13.5px] font-medium truncate">{c.name}</span>
                  {c.favorite && <Icon name="star" size={12} className="text-amber-500" strokeWidth={2} />}
                </div>
                <div className="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">
                  {c.number} · {c.org}
                </div>
              </div>
              <div className="flex items-center gap-1">
                <button
                  onClick={() => onCall(c.number)}
                  className="p-1.5 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                  title="Call"
                >
                  <Icon name="phone" size={15} />
                </button>
                <button className="p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Message">
                  <Icon name="message-square" size={15} />
                </button>
                <button className={cx('p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800', compact ? 'hidden' : 'inline-flex')} title="Details">
                  <Icon name="info" size={15} />
                </button>
              </div>
            </li>
          ))}
          {sorted.length === 0 && (
            <li className="px-4 py-10 text-center text-[13px] text-slate-500 dark:text-slate-400">
              No contacts match "{q}".
            </li>
          )}
        </ul>
      </div>
    </div>
  );
}

window.Contacts = Contacts;
