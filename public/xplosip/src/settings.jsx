// Settings — SIP account, audio, codecs (drag reorder), ringtones.

const SECTIONS = [
  { id: 'account', label: 'SIP Account', icon: 'user' },
  { id: 'audio',   label: 'Audio Devices', icon: 'headphones' },
  { id: 'codecs',  label: 'Codecs', icon: 'sliders-horizontal' },
  { id: 'ringtones', label: 'Ringtones', icon: 'bell' },
  { id: 'advanced', label: 'Advanced', icon: 'wrench' },
];

function Settings({ account, accounts, dark, onToggleTheme, dnd, setDnd, autoAnswer, setAutoAnswer }) {
  const compact = useCompact();
  const [section, setSection] = useState('account');
  return (
    <div className={cx('flex-1 min-h-0 grid', compact ? 'grid-cols-1' : 'grid-cols-[220px_1fr]')}>
      {/* Left rail */}
      {!compact && (
        <aside className="border-r border-slate-200 dark:border-slate-800 p-3">
        <ul className="flex flex-col gap-0.5">
          {SECTIONS.map(s => {
            const active = section === s.id;
            return (
              <li key={s.id}>
                <button
                  onClick={() => setSection(s.id)}
                  className={cx(
                    'w-full flex items-center gap-2 px-2.5 py-2 rounded-md text-[12.5px] font-medium transition',
                    active
                      ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-50'
                      : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100/60 dark:hover:bg-slate-800/60',
                  )}
                >
                  <Icon name={s.icon} size={14} className={active ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400'} />
                  {s.label}
                </button>
              </li>
            );
          })}
        </ul>
        </aside>
      )}

      {/* Compact selector */}
      {compact && (
        <div className="px-4 py-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-thin">
        <div className="flex gap-1.5">
          {SECTIONS.map(s => (
            <button
              key={s.id}
              onClick={() => setSection(s.id)}
              className={cx(
                'px-3 h-7 rounded-full text-[12px] font-medium transition whitespace-nowrap',
                section === s.id
                  ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                  : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800',
              )}
            >
              {s.label}
            </button>
          ))}
        </div>
        </div>
      )}

      <div className={cx('overflow-y-auto scrollbar-thin py-5', compact ? 'px-4' : 'px-6')}>
        {section === 'account' && <AccountSection account={account} accounts={accounts} />}
        {section === 'audio' && <AudioSection />}
        {section === 'codecs' && <CodecsSection />}
        {section === 'ringtones' && <RingtonesSection />}
        {section === 'advanced' && <AdvancedSection dark={dark} onToggleTheme={onToggleTheme} dnd={dnd} setDnd={setDnd} autoAnswer={autoAnswer} setAutoAnswer={setAutoAnswer} />}
      </div>
    </div>
  );
}

function Field({ label, hint, children, span }) {
  return (
    <label className={cx('block', span && 'sm:col-span-2')}>
      <div className="text-[11.5px] font-medium text-slate-600 dark:text-slate-300 mb-1">{label}</div>
      {children}
      {hint && <div className="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{hint}</div>}
    </label>
  );
}

function TextInput(props) {
  return (
    <input
      {...props}
      className={cx(
        'w-full h-9 px-3 rounded-md bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 outline-none text-[13px] focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 tnum',
        props.className,
      )}
    />
  );
}

function Select({ children, ...rest }) {
  return (
    <select
      {...rest}
      className="w-full h-9 px-2.5 rounded-md bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 outline-none text-[13px] focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
    >
      {children}
    </select>
  );
}

function SettingsCard({ title, description, children, footer }) {
  return (
    <section className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 mb-4">
      <header className="px-5 pt-4 pb-1">
        <h3 className="text-[14px] font-semibold tracking-tight">{title}</h3>
        {description && <p className="text-[12px] text-slate-500 dark:text-slate-400 mt-0.5">{description}</p>}
      </header>
      <div className="px-5 pt-3 pb-4">{children}</div>
      {footer && <footer className="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/30 rounded-b-xl flex items-center justify-end gap-2">{footer}</footer>}
    </section>
  );
}

function AccountSection({ account, accounts }) {
  const compact = useCompact();
  return (
    <div>
      <h2 className="text-[18px] font-semibold tracking-tight mb-1">SIP Account</h2>
      <p className="text-[12.5px] text-slate-500 dark:text-slate-400 mb-5">Manage registration credentials and transport for each line.</p>

      <SettingsCard title="Accounts">
        <ul className="divide-y divide-slate-100 dark:divide-slate-800 -mx-5">
          {accounts.map(a => (
            <li key={a.id} className="px-5 py-2.5 flex items-center gap-3">
              <StatusDot status={a.status} />
              <div className="min-w-0 flex-1">
                <div className="text-[13px] font-medium truncate">{a.label}</div>
                <div className="text-[11.5px] text-slate-500 dark:text-slate-400 truncate">{a.sipUri} · {a.transport}</div>
              </div>
              <span className={cx(
                'text-[10.5px] font-medium px-2 py-0.5 rounded-full',
                a.status === 'registered' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' :
                a.status === 'registering' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' :
                'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
              )}>
                {a.status === 'registered' ? 'Registered' : a.status === 'registering' ? 'Registering…' : 'Failed'}
              </span>
              <button className="p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"><Icon name="pencil" size={14} /></button>
            </li>
          ))}
        </ul>
      </SettingsCard>

      <SettingsCard
        title={`Edit · ${account.label}`}
        description="Changes apply on next registration cycle."
        footer={<>
          <PrimaryButton color="ghost" size="sm">Cancel</PrimaryButton>
          <PrimaryButton size="sm">Save changes</PrimaryButton>
        </>}
      >
        <div className={cx('grid gap-3.5', compact ? 'grid-cols-1' : 'grid-cols-2')}>
          <Field label="Display name">
            <TextInput defaultValue="Support — Agent 042" />
          </Field>
          <Field label="SIP URI">
            <TextInput defaultValue={account.sipUri} />
          </Field>
          <Field label="Username">
            <TextInput defaultValue="agent042" />
          </Field>
          <Field label="Password">
            <TextInput type="password" defaultValue="●●●●●●●●●●●●" />
          </Field>
          <Field label="Server / Domain">
            <TextInput defaultValue="voip.acme.io" />
          </Field>
          <Field label="Outbound proxy" hint="Optional. Use for SBC routing.">
            <TextInput defaultValue="sbc.voip.acme.io:5061" />
          </Field>
          <Field label="Transport">
            <Select defaultValue="TLS">
              <option>UDP</option>
              <option>TCP</option>
              <option>TLS</option>
            </Select>
          </Field>
          <Field label="STUN server">
            <TextInput defaultValue="stun:stun.acme.io:3478" />
          </Field>
          <Field label="Register expires (s)">
            <TextInput type="number" defaultValue={3600} />
          </Field>
          <Field label="Keep-alive (s)">
            <TextInput type="number" defaultValue={30} />
          </Field>
        </div>
      </SettingsCard>
    </div>
  );
}

function AudioSection() {
  const compact = useCompact();
  return (
    <div>
      <h2 className="text-[18px] font-semibold tracking-tight mb-1">Audio Devices</h2>
      <p className="text-[12.5px] text-slate-500 dark:text-slate-400 mb-5">Route input, output, and ringing to specific devices.</p>

      <SettingsCard title="Devices">
        <div className={cx('grid gap-3.5', compact ? 'grid-cols-1' : 'grid-cols-2')}>
          <Field label="Microphone (input)">
            <Select defaultValue="poly">
              <option value="poly">Poly Voyager Focus 2 — headset mic</option>
              <option>MacBook Pro Microphone</option>
              <option>Logitech BRIO</option>
              <option>Krisp Virtual Mic</option>
            </Select>
          </Field>
          <Field label="Speaker (output)">
            <Select defaultValue="poly">
              <option value="poly">Poly Voyager Focus 2 — headset</option>
              <option>MacBook Pro Speakers</option>
              <option>Studio Display Speakers</option>
            </Select>
          </Field>
          <Field label="Ringing device" hint="Plays incoming ringtone separately from the headset.">
            <Select defaultValue="laptop">
              <option value="laptop">MacBook Pro Speakers</option>
              <option>Poly Voyager Focus 2 — headset</option>
              <option>Studio Display Speakers</option>
            </Select>
          </Field>
          <Field label="DTMF tone level">
            <input type="range" min={0} max={100} defaultValue={60} className="w-full accent-brand-600" />
          </Field>
        </div>

        <div className={cx('mt-5 grid gap-3.5', compact ? 'grid-cols-1' : 'grid-cols-2')}>
          <MeterCard label="Microphone level" value={0.62} />
          <MeterCard label="Output level" value={0.78} />
        </div>
      </SettingsCard>

      <SettingsCard title="Echo & noise">
        <div className="grid grid-cols-1 gap-2">
          <RowToggle label="Acoustic echo cancellation" desc="Recommended for laptop speakers." defaultOn />
          <RowToggle label="Noise suppression" desc="Removes keyboard and HVAC background noise." defaultOn />
          <RowToggle label="Automatic gain control" desc="Levels microphone volume." defaultOn />
          <RowToggle label="High-pass filter" desc="Cuts low-frequency rumble." />
        </div>
      </SettingsCard>
    </div>
  );
}

function MeterCard({ label, value }) {
  return (
    <div className="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 px-3 py-2.5">
      <div className="flex items-center justify-between text-[11.5px] mb-1.5">
        <span className="font-medium text-slate-700 dark:text-slate-200">{label}</span>
        <span className="tnum text-slate-500 dark:text-slate-400">{Math.round(value * 100)}%</span>
      </div>
      <div className="flex gap-[3px]">
        {Array.from({ length: 22 }).map((_, i) => {
          const active = i / 22 < value;
          const color = i < 14 ? 'bg-emerald-500' : i < 19 ? 'bg-amber-500' : 'bg-rose-500';
          return <span key={i} className={cx('flex-1 h-2 rounded-sm', active ? color : 'bg-slate-200 dark:bg-slate-800')} />;
        })}
      </div>
    </div>
  );
}

function RowToggle({ label, desc, defaultOn }) {
  const [on, setOn] = useState(!!defaultOn);
  return (
    <div className="flex items-center gap-3 py-2 border-b last:border-b-0 border-slate-100 dark:border-slate-800">
      <div className="min-w-0 flex-1">
        <div className="text-[13px] font-medium">{label}</div>
        {desc && <div className="text-[11.5px] text-slate-500 dark:text-slate-400">{desc}</div>}
      </div>
      <button
        onClick={() => setOn(v => !v)}
        className={cx(
          'relative inline-flex items-center w-9 h-5 rounded-full transition shrink-0',
          on ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-700',
        )}
        aria-pressed={on}
      >
        <span className={cx(
          'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform',
          on ? 'translate-x-4' : 'translate-x-0',
        )} />
      </button>
    </div>
  );
}

function CodecsSection() {
  const [codecs, setCodecs] = useState([
    { id: 'opus',   name: 'Opus',      hz: '48 kHz', bitrate: '6–510 kbps', on: true },
    { id: 'g722',   name: 'G.722',     hz: '16 kHz', bitrate: '64 kbps',    on: true },
    { id: 'pcma',   name: 'G.711 A-law', hz: '8 kHz',  bitrate: '64 kbps',  on: true },
    { id: 'pcmu',   name: 'G.711 µ-law', hz: '8 kHz',  bitrate: '64 kbps',  on: true },
    { id: 'g729',   name: 'G.729',     hz: '8 kHz',  bitrate: '8 kbps',     on: false },
    { id: 'speex',  name: 'Speex',     hz: '8/16 kHz', bitrate: '2.15–24.6 kbps', on: false },
  ]);

  const move = (idx, dir) => {
    setCodecs(prev => {
      const next = [...prev];
      const swap = idx + dir;
      if (swap < 0 || swap >= next.length) return prev;
      [next[idx], next[swap]] = [next[swap], next[idx]];
      return next;
    });
  };

  const toggle = (id) => setCodecs(prev => prev.map(c => c.id === id ? { ...c, on: !c.on } : c));

  return (
    <div>
      <h2 className="text-[18px] font-semibold tracking-tight mb-1">Codecs</h2>
      <p className="text-[12.5px] text-slate-500 dark:text-slate-400 mb-5">Drag to reorder. Codecs near the top are offered first during negotiation.</p>

      <SettingsCard title="Negotiation priority" description="Disabled codecs are never offered, even if the remote peer supports them.">
        <ul className="divide-y divide-slate-100 dark:divide-slate-800 -mx-5">
          {codecs.map((c, idx) => (
            <li key={c.id} className="px-5 py-2.5 flex items-center gap-3">
              <div className="flex flex-col items-center gap-0.5">
                <button onClick={() => move(idx, -1)} disabled={idx === 0} className="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 disabled:opacity-30">
                  <Icon name="chevron-up" size={13} />
                </button>
                <button onClick={() => move(idx, 1)} disabled={idx === codecs.length - 1} className="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 disabled:opacity-30">
                  <Icon name="chevron-down" size={13} />
                </button>
              </div>
              <Icon name="grip-vertical" size={14} className="text-slate-300 dark:text-slate-700 cursor-grab" />
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                  <span className="text-[13px] font-medium">{c.name}</span>
                  {idx === 0 && c.on && <span className="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300">Preferred</span>}
                </div>
                <div className="text-[11.5px] text-slate-500 dark:text-slate-400 tnum">{c.hz} · {c.bitrate}</div>
              </div>
              <button
                onClick={() => toggle(c.id)}
                className={cx(
                  'relative inline-flex items-center w-9 h-5 rounded-full transition shrink-0',
                  c.on ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-700',
                )}
              >
                <span className={cx(
                  'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform',
                  c.on ? 'translate-x-4' : 'translate-x-0',
                )} />
              </button>
            </li>
          ))}
        </ul>
      </SettingsCard>
    </div>
  );
}

function RingtonesSection() {
  const compact = useCompact();
  const [selected, setSelected] = useState('office');
  const tones = [
    { id: 'office', name: 'Office classic', length: '0:08' },
    { id: 'soft',   name: 'Soft chime',     length: '0:04' },
    { id: 'pulse',  name: 'Modern pulse',   length: '0:06' },
    { id: 'analog', name: 'Analog bell',    length: '0:10' },
    { id: 'silent', name: 'Silent (vibrate only)', length: '—' },
  ];
  return (
    <div>
      <h2 className="text-[18px] font-semibold tracking-tight mb-1">Ringtones</h2>
      <p className="text-[12.5px] text-slate-500 dark:text-slate-400 mb-5">Assigned per account; defaults apply to unmatched calls.</p>

      <SettingsCard title="Default ringtone">
        <ul className="-mx-5">
          {tones.map(t => (
            <li key={t.id}>
              <label className="flex items-center gap-3 px-5 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 cursor-pointer border-b last:border-b-0 border-slate-100 dark:border-slate-800">
                <input
                  type="radio"
                  name="ringtone"
                  checked={selected === t.id}
                  onChange={() => setSelected(t.id)}
                  className="accent-brand-600"
                />
                <div className="min-w-0 flex-1">
                  <div className="text-[13px] font-medium">{t.name}</div>
                  <div className="text-[11.5px] text-slate-500 dark:text-slate-400 tnum">{t.length}</div>
                </div>
                <button className="p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Preview">
                  <Icon name="play" size={14} />
                </button>
              </label>
            </li>
          ))}
        </ul>
      </SettingsCard>

      <SettingsCard title="Per-account override">
        <div className={cx('grid gap-3.5', compact ? 'grid-cols-1' : 'grid-cols-2')}>
          <Field label="Support Line">
            <Select defaultValue="office"><option>Office classic</option><option>Soft chime</option></Select>
          </Field>
          <Field label="Sales Queue">
            <Select defaultValue="pulse"><option>Modern pulse</option><option>Analog bell</option></Select>
          </Field>
        </div>
      </SettingsCard>
    </div>
  );
}

function AdvancedSection({ dark, onToggleTheme, dnd, setDnd, autoAnswer, setAutoAnswer }) {
  const compact = useCompact();
  return (
    <div>
      <h2 className="text-[18px] font-semibold tracking-tight mb-1">Advanced</h2>
      <p className="text-[12.5px] text-slate-500 dark:text-slate-400 mb-5">App behavior, presence, and appearance.</p>

      <SettingsCard title="Behavior">
        <div className="grid grid-cols-1 gap-2">
          <RowToggleControlled label="Do Not Disturb" desc="Decline all incoming calls automatically." on={dnd} setOn={setDnd} />
          <RowToggleControlled label="Auto-Answer" desc="Answer after one ring. Useful for headset agents." on={autoAnswer} setOn={setAutoAnswer} />
          <RowToggle label="Always on top" desc="Keep the compact window above other apps." defaultOn />
          <RowToggle label="Launch at login" />
        </div>
      </SettingsCard>

      <SettingsCard title="Appearance">
        <div className="flex items-center gap-3 py-2">
          <div className="min-w-0 flex-1">
            <div className="text-[13px] font-medium">Theme</div>
            <div className="text-[11.5px] text-slate-500 dark:text-slate-400">Light is the default; dark reduces glare in low-light spaces.</div>
          </div>
          <div className="flex rounded-md border border-slate-200 dark:border-slate-800 overflow-hidden text-[12px] font-medium">
            <button
              onClick={() => dark && onToggleTheme()}
              className={cx('px-3 h-8 flex items-center gap-1.5', !dark ? 'bg-slate-900 text-white' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300')}
            >
              <Icon name="sun" size={13} /> Light
            </button>
            <button
              onClick={() => !dark && onToggleTheme()}
              className={cx('px-3 h-8 flex items-center gap-1.5', dark ? 'bg-slate-100 text-slate-900' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300')}
            >
              <Icon name="moon" size={13} /> Dark
            </button>
          </div>
        </div>
      </SettingsCard>

      <SettingsCard title="Diagnostics" description="For escalation to network engineering.">
        <div className={cx('grid gap-2', compact ? 'grid-cols-1' : 'grid-cols-3')}>
          <PrimaryButton color="ghost" icon="file-text" size="sm">View SIP log</PrimaryButton>
          <PrimaryButton color="ghost" icon="bug" size="sm">Run echo test</PrimaryButton>
          <PrimaryButton color="ghost" icon="upload" size="sm">Send report</PrimaryButton>
        </div>
      </SettingsCard>
    </div>
  );
}

function RowToggleControlled({ label, desc, on, setOn }) {
  return (
    <div className="flex items-center gap-3 py-2 border-b last:border-b-0 border-slate-100 dark:border-slate-800">
      <div className="min-w-0 flex-1">
        <div className="text-[13px] font-medium">{label}</div>
        {desc && <div className="text-[11.5px] text-slate-500 dark:text-slate-400">{desc}</div>}
      </div>
      <button
        onClick={() => setOn(!on)}
        className={cx(
          'relative inline-flex items-center w-9 h-5 rounded-full transition shrink-0',
          on ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-700',
        )}
      >
        <span className={cx(
          'absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform',
          on ? 'translate-x-4' : 'translate-x-0',
        )} />
      </button>
    </div>
  );
}

window.Settings = Settings;
