#!/bin/bash
# Run these commands on the server (copy-paste into your VPS console)
# They fix xplosip click-to-dial by:
#   1. Patching public/xplosip/src/crm-widget.jsx (served from host, no rebuild needed)
#   2. Patching the Blade partial inside the container (clears view cache after)

set -e
cd ~/crm_laravel_sass

echo "=== Step 1: Patch crm-widget.jsx on host ==="
python3 << 'PYEOF'
import re, sys

path = 'public/xplosip/src/crm-widget.jsx'
src = open(path).read()
changed = False

# ---- patch A: store pending dial number ----
OLD_A = """  // If dialNumber changes after mount, update via window event
  useEffect(() => {
    if (!dialNumber) return;
    window.dispatchEvent(new CustomEvent('xplosip:dial', { detail: { number: dialNumber } }));
    setDialNum(''); // reset so next dial fires a fresh event
  }, [dialNumber]);"""

NEW_A = """  // If dialNumber changes after mount, store it as a pending number and
  // also broadcast the event for already-mounted CrmAppShell instances.
  // The pending variable lets a freshly-mounted CrmAppShell pick up the
  // number even when it mounts *after* the event fires (timing race).
  useEffect(() => {
    if (!dialNumber) return;
    window.__xplosipPendingDial = dialNumber;           // for late-mounting shells
    window.dispatchEvent(new CustomEvent('xplosip:dial', { detail: { number: dialNumber } }));
    setDialNum(''); // reset so next dial fires a fresh event
  }, [dialNumber]);"""

if OLD_A in src:
    src = src.replace(OLD_A, NEW_A)
    changed = True
    print('[A] pending-dial store: OK')
elif NEW_A in src:
    print('[A] pending-dial store: already patched')
else:
    print('[A] ERROR: search string not found', file=sys.stderr)

# ---- patch B: CrmAppShell timing fix ----
OLD_B_MARKER = "function CrmAppShell({ initialNumber = '' }) {"
NEW_B = """function CrmAppShell({ initialNumber = '' }) {
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
        input.value = num;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }

    // Listen for dial events dispatched while this shell is already mounted.
    function onDial(e) {
      injectNumber(e.detail && e.detail.number);
    }
    window.addEventListener('xplosip:dial', onDial);

    // Consume any number stored before this shell mounted (race fix).
    // Retry until the App has rendered its input element.
    const pending = window.__xplosipPendingDial || initialNumber;
    if (pending) {
      window.__xplosipPendingDial = '';
      let attempts = 0;
      const tid = setInterval(() => {
        attempts++;
        const input = document.querySelector('#xplosip-app-mount input[type="text"]');
        if (input) { clearInterval(tid); injectNumber(pending); }
        else if (attempts >= 20) { clearInterval(tid); }
      }, 50);
    }

    return () => window.removeEventListener('xplosip:dial', onDial);
  }, []);

  return <App />;
}"""

if 'injectNumber' in src:
    print('[B] CrmAppShell timing fix: already patched')
elif OLD_B_MARKER in src:
    # Find the old CrmAppShell block and replace it up to "return <App />;"
    # Locate start of the function
    start = src.index(OLD_B_MARKER)
    # Find the closing "return <App />;\n}" pattern after that
    end_marker = '  return <App />;\n}'
    end = src.index(end_marker, start) + len(end_marker)
    src = src[:start] + NEW_B + src[end:]
    changed = True
    print('[B] CrmAppShell timing fix: OK')
else:
    print('[B] ERROR: CrmAppShell marker not found', file=sys.stderr)

if changed:
    open(path, 'w').write(src)
    print('crm-widget.jsx written.')
else:
    print('crm-widget.jsx: no changes written.')
PYEOF

echo ""
echo "=== Step 2: Patch Blade partial inside container ==="
docker exec crm_app php << 'PHPEOF'
<?php
$file = '/var/www/html/resources/views/layouts/partials/xplosip-widget.blade.php';
$content = file_get_contents($file);

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

$marker = "{{-- xplosip source files (order matters — dependencies first) --}}";

if (strpos($content, 'window.useState') !== false) {
    echo "[blade] Already patched.\n";
} elseif (strpos($content, $marker) !== false) {
    $patched = str_replace($marker, $hooksBlock . $marker, $content);
    file_put_contents($file, $patched);
    echo "[blade] Patched OK.\n";
} else {
    echo "[blade] ERROR: marker not found.\n";
}

// Clear view cache
$cacheDir = '/var/www/html/storage/framework/views';
$n = 0;
foreach (glob("$cacheDir/*.php") as $f) { if (@unlink($f)) $n++; }
echo "[cache] Cleared $n compiled view file(s).\n";
PHPEOF

echo ""
echo "=== Done! Hard-refresh browser (Ctrl+Shift+R) to test ==="
