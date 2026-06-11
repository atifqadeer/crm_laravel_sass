<?php
/**
 * patch-xplosip.php — fixes xplosip click-to-dial
 *
 * Run on the server:
 *   docker cp patch-xplosip.php crm_app:/tmp/patch-xplosip.php
 *   docker exec crm_app php /tmp/patch-xplosip.php
 */

// ── 1. Blade partial — inject React hooks into global scope ───────────────────

$bladeFile = '/var/www/html/resources/views/layouts/partials/xplosip-widget.blade.php';
$bladeContent = file_get_contents($bladeFile);
if ($bladeContent === false) { die("ERROR: cannot read $bladeFile\n"); }

$hooksBlock = <<<'SCRIPT'

{{-- Expose React hooks as globals so JSX files can use bare names (no React. prefix) --}}
<script>
    (function () {
        var R = window.React;
        if (!R) { console.error('xplosip: React UMD not loaded'); return; }
        window.useState             = R.useState;
        window.useEffect            = R.useEffect;
        window.useCallback          = R.useCallback;
        window.useRef               = R.useRef;
        window.useMemo              = R.useMemo;
        window.useContext           = R.useContext;
        window.useReducer           = R.useReducer;
        window.useLayoutEffect      = R.useLayoutEffect;
        window.useId                = R.useId;
        window.useTransition        = R.useTransition;
        window.useDeferredValue     = R.useDeferredValue;
        window.useImperativeHandle  = R.useImperativeHandle;
        window.createContext        = R.createContext;
        window.createRef            = R.createRef;
        window.forwardRef           = R.forwardRef;
        window.memo                 = R.memo;
        window.Fragment             = R.Fragment;
    })();
</script>

SCRIPT;

$bladeMarker = "{{-- xplosip source files (order matters — dependencies first) --}}";

if (strpos($bladeContent, 'window.useState') !== false) {
    echo "[blade] Already patched — skipping.\n";
} elseif (strpos($bladeContent, $bladeMarker) === false) {
    echo "[blade] ERROR: marker not found.\n";
} else {
    $new = str_replace($bladeMarker, $hooksBlock . $bladeMarker, $bladeContent);
    file_put_contents($bladeFile, $new);
    echo "[blade] Patched OK.\n";
}

// ── 2. crm-widget.jsx — fix timing race for click-to-dial number injection ───

$jsxFile = '/var/www/html/public/xplosip/src/crm-widget.jsx';
$jsxContent = file_get_contents($jsxFile);
if ($jsxContent === false) { die("ERROR: cannot read $jsxFile\n"); }

// ---- 2a. Store pending dial number before dispatching event ----
$old2a = <<<'OLD'
  // If dialNumber changes after mount, update via window event
  useEffect(() => {
    if (!dialNumber) return;
    window.dispatchEvent(new CustomEvent('xplosip:dial', { detail: { number: dialNumber } }));
    setDialNum(''); // reset so next dial fires a fresh event
  }, [dialNumber]);
OLD;

$new2a = <<<'NEW'
  // If dialNumber changes after mount, store it as a pending number and
  // also broadcast the event for already-mounted CrmAppShell instances.
  // The pending variable lets a freshly-mounted CrmAppShell pick up the
  // number even when it mounts *after* the event fires (timing race).
  useEffect(() => {
    if (!dialNumber) return;
    window.__xplosipPendingDial = dialNumber;           // for late-mounting shells
    window.dispatchEvent(new CustomEvent('xplosip:dial', { detail: { number: dialNumber } }));
    setDialNum(''); // reset so next dial fires a fresh event
  }, [dialNumber]);
NEW;

// ---- 2b. Replace CrmAppShell with timing-safe version ----
$old2b = <<<'OLD'
// ── Thin CRM-specific shell around the prototype App ──────────────────────
// Forces compact frame mode and listens for xplosip:dial events.
function CrmAppShell({ initialNumber = '' }) {
  // Patch: force the app into compact/embedded mode by overriding usePersisted
  // for the 'frame' key so it always returns 'compact', not 'desktop'.
  // We do this by rendering App with a shim over localStorage.
  const _orig_getItem = Storage.prototype.getItem;

  // Temporarily override localStorage for the initial render to
  // inject compact mode and the initialNumber into the dialer.
  useEffect(() => {
    // Pre-seed the frame as 'compact' so the app starts collapsed.
    try {
      localStorage.setItem('softphone:frame', JSON.stringify('compact'));
      if (initialNumber) {
        // The dialer view reads the number from its own state — we signal via
        // the custom event xplosip:dial dispatched in XplosipWidget.
      }
    } catch (_) {}

    // Listen for dial events (from click-to-dial links outside React tree)
    function onDial(e) {
      const num = e.detail && e.detail.number;
      if (!num) return;
      // Fire a keyboard-style injection: find the dialer input and update it
      const input = document.querySelector('#xplosip-app-mount input[type="text"]');
      if (input) {
        const nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        nativeSetter.call(input, num);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }
    window.addEventListener('xplosip:dial', onDial);
    return () => window.removeEventListener('xplosip:dial', onDial);
  }, []);

  return <App />;
}
OLD;

$new2b = <<<'NEW'
// ── Thin CRM-specific shell around the prototype App ──────────────────────
// Forces compact frame mode and listens for xplosip:dial events.
function CrmAppShell({ initialNumber = '' }) {
  useEffect(() => {
    // Force compact frame so the widget fits inside the 380x640 panel.
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
      const MAX = 20; // up to 1s (20 x 50ms)
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

  return <App />;
}
NEW;

$patches = [
    ['old' => $old2a, 'new' => $new2a, 'label' => 'pending-dial store'],
    ['old' => $old2b, 'new' => $new2b, 'label' => 'CrmAppShell timing fix'],
];

foreach ($patches as $p) {
    if (strpos($jsxContent, $p['old']) === false) {
        // Check if already patched
        if (strpos($jsxContent, $p['new']) !== false) {
            echo "[jsx/{$p['label']}] Already patched — skipping.\n";
        } else {
            echo "[jsx/{$p['label']}] ERROR: search string not found — manual review needed.\n";
        }
    } else {
        $jsxContent = str_replace($p['old'], $p['new'], $jsxContent);
        echo "[jsx/{$p['label']}] Patched OK.\n";
    }
}
file_put_contents($jsxFile, $jsxContent);

// ── 3. Clear Blade view cache ─────────────────────────────────────────────────
$cacheDir = '/var/www/html/storage/framework/views';
$cleared = 0;
if (is_dir($cacheDir)) {
    foreach (glob("$cacheDir/*.php") as $f) {
        if (@unlink($f)) $cleared++;
    }
}
echo "[cache] Cleared $cleared compiled view files.\n";

echo "\nDone. Hard-refresh the browser (Ctrl+Shift+R) to test click-to-dial.\n";
