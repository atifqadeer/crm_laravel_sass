// ════════════════════════════════════════════════════════════════════════════
// SIP configuration
//
// In the CRM context window.SIP_CONFIG is injected by the Blade partial
// (xplosip-widget.blade.php) from Auth::user()->sip_extension / sip_password
// BEFORE this file loads, so the block below is intentionally a no-op when
// running inside the CRM.
//
// When running as the standalone prototype (index.html), set your values here.
// ════════════════════════════════════════════════════════════════════════════
if (!window.SIP_CONFIG) {
  window.SIP_CONFIG = {
    wsUri:       'wss://voip.ibstec.com:8089/ws',
    domain:      'voip.ibstec.com',
    extension:   '',          // set via user profile in CRM
    password:    '',          // set via user profile in CRM
    displayName: 'Agent',
    stun:        'stun:stun.l.google.com:19302',
  };
}
const SIP_CONFIG = window.SIP_CONFIG;
// ════════════════════════════════════════════════════════════════════════════

// Mock data + helpers shared across views.

const ACCOUNTS = [
  {
    id:        'main',
    label:     'Extension ' + SIP_CONFIG.extension,
    sipUri:    SIP_CONFIG.extension + '@' + SIP_CONFIG.domain,
    status:    'registering',   // updated live by SIP engine → 'registered' | 'failed'
    transport: 'WSS',
  },
];

const CONTACTS = [
  { id: 'c1', name: 'Amelia Okafor',   number: '+1 415 555 0142', org: 'Northwind Logistics', favorite: true,  presence: 'available' },
  { id: 'c2', name: 'Daniel Park',     number: '+1 415 555 0188', org: 'Northwind Logistics', favorite: true,  presence: 'busy' },
  { id: 'c3', name: 'Priya Raghavan',  number: '+44 20 7946 0211', org: 'Helix Robotics',     favorite: false, presence: 'available' },
  { id: 'c4', name: 'Marcus Lin',      number: '+1 212 555 0119', org: 'Acme Internal',       favorite: true,  presence: 'dnd' },
  { id: 'c5', name: 'Sara Holm',       number: '+47 22 555 014',  org: 'Fjord Maritime',      favorite: false, presence: 'offline' },
  { id: 'c6', name: 'Tomás Ribeiro',   number: '+351 21 555 0166', org: 'Vega Energy',        favorite: false, presence: 'available' },
  { id: 'c7', name: 'Hana Watanabe',   number: '+81 3 5555 0193', org: 'Helix Robotics',      favorite: false, presence: 'available' },
  { id: 'c8', name: 'Reception',       number: '100',              org: 'Acme Internal',       favorite: true,  presence: 'available' },
  { id: 'c9', name: 'Eng On-Call',     number: '7011',             org: 'Acme Internal',       favorite: true,  presence: 'busy' },
  { id: 'c10', name: 'Iris Bauer',     number: '+49 30 5555 0177', org: 'Northwind Logistics', favorite: false, presence: 'available' },
];

// Build call history off contacts so names line up.
function _h(id, contactId, number, dir, status, mins, secs, hoursAgo) {
  return {
    id, contactId, number, direction: dir, status,
    duration: mins * 60 + secs,
    ts: Date.now() - hoursAgo * 3600 * 1000,
  };
}
const HISTORY = [
  _h('h1',  'c1', '+1 415 555 0142', 'in',  'answered', 4, 12, 0.5),
  _h('h2',  'c2', '+1 415 555 0188', 'out', 'answered', 1, 38, 1.2),
  _h('h3',  null, '+1 510 555 0902', 'in',  'missed',   0, 0,  2.1),
  _h('h4',  'c4', '+1 212 555 0119', 'out', 'answered', 22, 5, 3.4),
  _h('h5',  'c3', '+44 20 7946 0211','in',  'answered', 8, 41, 5.8),
  _h('h6',  null, '+1 925 555 0410', 'out', 'no-answer', 0, 0, 7.0),
  _h('h7',  'c8', '100',              'out', 'answered', 0, 22, 9.1),
  _h('h8',  'c9', '7011',             'in',  'missed',   0, 0, 11.5),
  _h('h9',  'c7', '+81 3 5555 0193',  'out', 'answered', 14, 19, 26),
  _h('h10', 'c6', '+351 21 555 0166', 'in',  'answered', 2, 7,  29),
  _h('h11', null, '+1 707 555 0828',  'in',  'missed',   0, 0, 49),
  _h('h12', 'c10','+49 30 5555 0177', 'out', 'answered', 5, 50, 51),
];

const THREADS = [
  {
    id: 't1', contactId: 'c1', unread: 2, last: 'Got it — patching the relay now.',
    messages: [
      { id: 'm1', from: 'c1',  text: 'Hi — quick one: the trunk to Frankfurt is flapping.',     ts: '09:41' },
      { id: 'm2', from: 'me',  text: 'Looking. Stand by.',                                       ts: '09:42' },
      { id: 'm3', from: 'c1',  text: 'Got it — patching the relay now.',                         ts: '09:46' },
      { id: 'm4', from: 'c1',  text: 'Can you call when you have a minute?',                     ts: '09:51' },
    ],
  },
  {
    id: 't2', contactId: 'c2', unread: 0, last: 'Sounds good. Talk later.',
    messages: [
      { id: 'm1', from: 'me', text: 'Pushed the new dial plan to staging.',  ts: 'Yesterday' },
      { id: 'm2', from: 'c2', text: 'Sounds good. Talk later.',              ts: 'Yesterday' },
    ],
  },
  {
    id: 't3', contactId: 'c4', unread: 0, last: 'Will loop back after the standup.',
    messages: [
      { id: 'm1', from: 'c4', text: 'Will loop back after the standup.', ts: 'Mon' },
    ],
  },
];

const KEYPAD = [
  { d: '1', s: '' },
  { d: '2', s: 'ABC' },
  { d: '3', s: 'DEF' },
  { d: '4', s: 'GHI' },
  { d: '5', s: 'JKL' },
  { d: '6', s: 'MNO' },
  { d: '7', s: 'PQRS' },
  { d: '8', s: 'TUV' },
  { d: '9', s: 'WXYZ' },
  { d: '*', s: '' },
  { d: '0', s: '+' },
  { d: '#', s: '' },
];

function fmtDuration(totalSec) {
  if (!totalSec || totalSec < 0) return '0:00';
  const m = Math.floor(totalSec / 60);
  const s = totalSec % 60;
  if (m >= 60) {
    const h = Math.floor(m / 60);
    const mm = m % 60;
    return `${h}:${String(mm).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  }
  return `${m}:${String(s).padStart(2, '0')}`;
}

function fmtRelative(ts) {
  const diff = (Date.now() - ts) / 1000;
  if (diff < 60) return 'just now';
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  const d = Math.floor(diff / 86400);
  if (d < 7) return d + 'd ago';
  return new Date(ts).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function initials(name) {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function contactFor(numberOrId) {
  if (!numberOrId) return null;
  return (
    CONTACTS.find(c => c.id === numberOrId) ||
    CONTACTS.find(c => c.number.replace(/\s+/g, '') === numberOrId.replace(/\s+/g, '')) ||
    null
  );
}

Object.assign(window, {
  ACCOUNTS, CONTACTS, HISTORY, THREADS, KEYPAD,
  fmtDuration, fmtRelative, initials, contactFor,
});
