// Messages (SIP SIMPLE / IM) — two-pane on desktop, single pane on compact.

function Messages({ onCall }) {
  const compact = useCompact();
  const [activeId, setActiveId] = useState(THREADS[0].id);
  const [draft, setDraft] = useState('');
  const active = THREADS.find(t => t.id === activeId);
  const activeContact = active ? contactFor(active.contactId) : null;

  // On compact width, allow "back to list" navigation
  const [showThreadOnCompact, setShowThreadOnCompact] = useState(false);

  return (
    <div className={cx('flex-1 min-h-0 grid', compact ? 'grid-cols-1' : 'grid-cols-[300px_1fr]')}>
      {/* Thread list */}
      <aside className={cx(
        'border-r border-slate-200 dark:border-slate-800 flex-col min-h-0',
        compact ? (showThreadOnCompact ? 'hidden' : 'flex') : 'flex',
      )}>
        <div className="px-3 py-3 border-b border-slate-200 dark:border-slate-800">
          <div className="relative">
            <Icon name="search" size={13} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              placeholder="Search messages"
              className="w-full h-9 pl-8 pr-3 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-transparent focus:bg-white dark:focus:bg-slate-900 focus:border-slate-300 dark:focus:border-slate-700 outline-none text-[12.5px]"
            />
          </div>
        </div>
        <ul className="flex-1 min-h-0 overflow-y-auto scrollbar-thin">
          {THREADS.map(t => {
            const c = contactFor(t.contactId);
            const isActive = t.id === activeId;
            return (
              <li key={t.id}>
                <button
                  onClick={() => { setActiveId(t.id); setShowThreadOnCompact(true); }}
                  className={cx(
                    'w-full flex items-center gap-2.5 px-3 py-2.5 text-left border-l-2 transition',
                    isActive
                      ? 'bg-slate-100/70 dark:bg-slate-800/60 border-brand-600'
                      : 'border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/40',
                  )}
                >
                  <Avatar name={c?.name} size={36} presence={c?.presence} />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-1.5">
                      <span className="text-[13px] font-medium truncate">{c?.name}</span>
                      {t.unread > 0 && <span className="ml-auto text-[10px] font-semibold px-1.5 rounded bg-brand-600 text-white">{t.unread}</span>}
                    </div>
                    <div className="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">{t.last}</div>
                  </div>
                </button>
              </li>
            );
          })}
        </ul>
      </aside>

      {/* Thread view */}
      <section className={cx(
        'flex-col min-h-0',
        compact ? (showThreadOnCompact ? 'flex' : 'hidden') : 'flex',
      )}>
        {active && activeContact && (
          <>
            <div className="h-14 shrink-0 flex items-center gap-2.5 px-4 border-b border-slate-200 dark:border-slate-800">
              <button
                onClick={() => setShowThreadOnCompact(false)}
                className={cx('p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800', !compact && 'hidden')}
                aria-label="Back"
              >
                <Icon name="chevron-left" size={16} />
              </button>
              <Avatar name={activeContact.name} size={32} presence={activeContact.presence} />
              <div className="min-w-0">
                <div className="text-[13px] font-semibold truncate">{activeContact.name}</div>
                <div className="text-[11px] text-slate-500 dark:text-slate-400 truncate">{activeContact.number}</div>
              </div>
              <div className="ml-auto flex items-center gap-0.5">
                <button
                  onClick={() => onCall(activeContact.number)}
                  className="p-2 rounded-md text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40"
                  title="Call"
                >
                  <Icon name="phone" size={16} />
                </button>
                <button className="p-2 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Details">
                  <Icon name="info" size={16} />
                </button>
              </div>
            </div>

            <div className="flex-1 min-h-0 overflow-y-auto scrollbar-thin px-4 py-4 flex flex-col gap-2">
              {active.messages.map(m => {
                const mine = m.from === 'me';
                return (
                  <div key={m.id} className={cx('flex', mine ? 'justify-end' : 'justify-start')}>
                    <div className={cx(
                      'max-w-[78%] rounded-2xl px-3.5 py-2 text-[13px] leading-relaxed',
                      mine
                        ? 'bg-brand-600 text-white rounded-br-md'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-bl-md',
                    )}>
                      <div>{m.text}</div>
                      <div className={cx('text-[10px] mt-0.5', mine ? 'text-brand-100' : 'text-slate-500 dark:text-slate-400')}>{m.ts}</div>
                    </div>
                  </div>
                );
              })}
              <div className="text-[10.5px] text-slate-400 dark:text-slate-500 text-center pt-2">
                <Icon name="lock" size={10} className="inline mr-1 -mt-0.5" />
                Messages sent via SIP SIMPLE
              </div>
            </div>

            <form
              onSubmit={(e) => { e.preventDefault(); setDraft(''); }}
              className="shrink-0 border-t border-slate-200 dark:border-slate-800 p-3 flex items-end gap-2"
            >
              <button type="button" className="p-2 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Attach">
                <Icon name="paperclip" size={16} />
              </button>
              <textarea
                rows={1}
                value={draft}
                onChange={e => setDraft(e.target.value)}
                placeholder="Type a message"
                className="flex-1 resize-none bg-slate-100 dark:bg-slate-800/80 rounded-lg px-3 py-2 text-[13px] outline-none focus:bg-white dark:focus:bg-slate-900 border border-transparent focus:border-slate-300 dark:focus:border-slate-700 max-h-32"
              />
              <button
                type="submit"
                disabled={!draft.trim()}
                className={cx(
                  'h-9 w-9 rounded-md flex items-center justify-center text-white transition',
                  draft.trim() ? 'bg-brand-600 hover:bg-brand-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed',
                )}
                aria-label="Send"
              >
                <Icon name="send" size={15} />
              </button>
            </form>
          </>
        )}
      </section>
    </div>
  );
}

window.Messages = Messages;
