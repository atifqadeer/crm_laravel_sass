// Small wrapper for Lucide icons as React components.
// Lucide UMD exposes window.lucide.icons[PascalName] as the IconNode format:
//   [svgTag, svgAttrs, [[childTag, childAttrs], ...]]
// We render that to an SVG string and inject via dangerouslySetInnerHTML.

const _ICON_CACHE = {};

function _pascal(name) {
  // 'phone-off' -> 'PhoneOff', 'grid-2x2' -> 'Grid2x2', 'chevrons-up-down' -> 'ChevronsUpDown'
  return name.replace(/(^|-)([a-z0-9])/g, (_, __, c) => c.toUpperCase());
}

function _findIcon(name) {
  if (_ICON_CACHE[name] !== undefined) return _ICON_CACHE[name];
  if (!window.lucide || !window.lucide.icons) return null;
  const candidates = [
    _pascal(name),
    name,
    // Some names like 'grid-3x3' might already exist as 'Grid3x3'
  ];
  let icon = null;
  for (const k of candidates) {
    if (window.lucide.icons[k]) { icon = window.lucide.icons[k]; break; }
  }
  _ICON_CACHE[name] = icon;
  return icon;
}

function _serializeAttrs(attrs) {
  let out = '';
  for (const k in attrs) {
    if (attrs[k] === undefined || attrs[k] === null) continue;
    out += ` ${k}="${String(attrs[k]).replace(/"/g, '&quot;')}"`;
  }
  return out;
}

function _renderIconSvg(node, overrides) {
  const [tag, attrs, children] = node;
  const merged = { ...attrs, ...overrides };
  let out = `<${tag}${_serializeAttrs(merged)}`;
  if (Array.isArray(children) && children.length) {
    out += '>';
    for (const child of children) {
      out += _renderIconSvg(child, {});
    }
    out += `</${tag}>`;
  } else {
    out += '/>';
  }
  return out;
}

function Icon({ name, size = 18, strokeWidth = 1.75, className = '', style, ...rest }) {
  const node = _findIcon(name);
  if (!node) {
    return (
      <span
        aria-hidden="true"
        className={className}
        style={{ display: 'inline-block', width: size, height: size, ...style }}
        title={'icon:' + name}
      />
    );
  }
  const svg = _renderIconSvg(node, {
    width: size,
    height: size,
    'stroke-width': strokeWidth,
  });
  return (
    <span
      aria-hidden="true"
      className={'inline-flex items-center justify-center ' + className}
      style={style}
      dangerouslySetInnerHTML={{ __html: svg }}
      {...rest}
    />
  );
}

window.Icon = Icon;
