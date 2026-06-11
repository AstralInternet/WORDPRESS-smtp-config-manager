// components.jsx — icons + shared UI primitives (exported to window)
const { useState, useEffect, useRef } = React;

/* ---- Icon set (Lucide-style, stroke) ---- */
const ICON_PATHS = {
  check: "M20 6 9 17l-5-5",
  x: "M18 6 6 18M6 6l12 12",
  mail: "M2 6.5A2.5 2.5 0 0 1 4.5 4h15A2.5 2.5 0 0 1 22 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 17.5zM2.5 6l9.5 7 9.5-7",
  send: "M14.5 9.5 21 3m0 0-6.5 18-4-9-9-4z",
  server: "M3 4.5h18v6H3zM3 13.5h18v6H3zM7 7.5h.01M7 16.5h.01",
  shield: "M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z",
  shieldcheck: "M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z|M9 12l2 2 4-4",
  key: "M15.5 8.5a4.5 4.5 0 1 0-4.9 4.48L4 20v0h3v-2h2v-2l1.96-1.96A4.5 4.5 0 0 0 15.5 8.5zM16.5 7.5h.01",
  list: "M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01",
  gauge: "M12 14l4-4M3.5 18a9 9 0 1 1 17 0z",
  settings: "M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z|M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.81 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 7 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H4.5a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 6 9.4l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 12 4.6V4.5a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 2.82 1.18l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15z",
  search: "M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3",
  copy: "M9 9h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zM5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1",
  info: "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16v-4M12 8h.01",
  lock: "M5 11h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1zM8 11V7a4 4 0 0 1 8 0v4",
  refresh: "M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5",
  plug: "M12 22v-5M9 7V2M15 7V2M6 7h12v3a6 6 0 0 1-12 0z",
  globe: "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z",
  alert: "M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z",
  alertc: "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 8v4M12 16h.01",
  eye: "M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z",
  download: "M12 3v12M7 10l5 5 5-5M5 21h14",
  trash: "M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6",
  arrow: "M5 12h14M13 6l6 6-6 6",
  wand: "M15 4V2M15 10V8M12.5 6.5h-2M19.5 6.5h-2M9 22 21 10l-3-3L6 19zM3 13l2 2",
  spark: "M12 3l1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4z",
  chevron: "M9 6l6 6-6 6",
  chevrond: "M6 9l6 6 6-6",
  user: "M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0",
  clock: "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 7v5l3 2",
  bug: "M9 9V6a3 3 0 0 1 6 0v3M8 9h8a3 3 0 0 1 3 3v3a7 7 0 0 1-14 0v-3a3 3 0 0 1 3-3zM2 13h3M19 13h3M3 7l3 2M21 7l-3 2M2 19l3-1M22 19l-3-1",
  inbox: "M22 12h-6l-2 3h-4l-2-3H2M5.5 5h13l3.5 7v6a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-6z",
  rocket: "M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2 0-2.8a2 2 0 0 0-3 0zM12 15l-3-3a22 22 0 0 1 8-10c2 0 5 3 5 5a22 22 0 0 1-10 8zM9 12H4s.5-2.8 2-4 4-1 4-1M12 15v5s2.8-.5 4-2 1-4 1-4",
  filter: "M3 5h18l-7 8v6l-4 2v-8z",
};

function Icon({ name, size = 18, fill = false, style, className }) {
  const raw = ICON_PATHS[name] || "";
  const paths = raw.split("|");
  return (
    <svg viewBox="0 0 24 24" width={size} height={size} fill="none"
      stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
      style={style} className={className} aria-hidden="true">
      {paths.map((d, i) => <path key={i} d={d} />)}
    </svg>
  );
}

/* ---- Toggle ---- */
function Toggle({ checked, onChange, id }) {
  return (
    <label className="toggle" htmlFor={id}>
      <input type="checkbox" id={id} checked={checked} onChange={e => onChange(e.target.checked)} />
      <span className="track"></span>
    </label>
  );
}

/* ---- InfoTip ---- */
function InfoTip({ text }) {
  return (
    <span className="info-tip" tabIndex={0}>
      <Icon name="info" size={15} />
      <span className="tip">{text}</span>
    </span>
  );
}

/* ---- Card ---- */
function Card({ title, hint, icon, action, children, flush, className = "" }) {
  return (
    <div className={`card ${flush ? "flush" : ""} ${className}`}>
      {title && (
        <div className="card-head" style={flush ? { padding: "var(--pad) var(--pad) 0" } : null}>
          {icon && <Icon name={icon} size={18} style={{ color: "var(--accent)" }} />}
          <h2>{title}</h2>
          {hint && <span className="hint">{hint}</span>}
          {action && <><span className="ch-spacer" />{action}</>}
        </div>
      )}
      {children}
    </div>
  );
}

/* ---- Badge ---- */
function Badge({ kind = "muted", children, dot = true }) {
  return <span className={`badge ${kind}`}>{dot && kind !== "accent" && <span className="bd" />}{children}</span>;
}

/* ---- Button ---- */
function Button({ variant = "ghost", size, block, loading, icon, iconRight, children, ...rest }) {
  return (
    <button className={`btn btn-${variant} ${size ? "btn-" + size : ""} ${block ? "btn-block" : ""}`}
      disabled={loading || rest.disabled} {...rest}>
      {loading ? <span className="spin" /> : icon && <Icon name={icon} size={size === "sm" ? 14 : 16} />}
      {children}
      {iconRight && !loading && <Icon name={iconRight} size={size === "sm" ? 14 : 16} />}
    </button>
  );
}

/* ---- CopyButton ---- */
function CopyButton({ text, label = "Copier" }) {
  const [done, setDone] = useState(false);
  return (
    <button className={`copy-btn ${done ? "copied" : ""}`} onClick={() => {
      navigator.clipboard?.writeText(text); setDone(true); setTimeout(() => setDone(false), 1600);
    }}>
      <Icon name={done ? "check" : "copy"} size={13} />{done ? "Copié" : label}
    </button>
  );
}

/* ---- Field ---- */
function Field({ label, required, tip, desc, children }) {
  return (
    <div className="field">
      {label && <label>{label}{required && <span className="req">*</span>}{tip && <InfoTip text={tip} />}</label>}
      {children}
      {desc && <p className="desc">{desc}</p>}
    </div>
  );
}

/* ---- ToggleRow ---- */
function ToggleRow({ title, desc, tip, checked, onChange, id }) {
  return (
    <div className="trow">
      <div className="t-main">
        <div className="t-title">{title}{tip && <InfoTip text={tip} />}</div>
        {desc && <div className="t-desc">{desc}</div>}
      </div>
      <Toggle checked={checked} onChange={onChange} id={id} />
    </div>
  );
}

/* ---- ProgressRing ---- */
function ProgressRing({ pct }) {
  const r = 28, c = 2 * Math.PI * r;
  return (
    <div className="progress-ring">
      <svg width="66" height="66" viewBox="0 0 66 66">
        <circle cx="33" cy="33" r={r} fill="none" stroke="var(--line)" strokeWidth="6" />
        <circle cx="33" cy="33" r={r} fill="none" stroke="var(--accent)" strokeWidth="6"
          strokeLinecap="round" strokeDasharray={c} strokeDashoffset={c - (c * pct) / 100}
          transform="rotate(-90 33 33)" style={{ transition: "stroke-dashoffset .6s ease" }} />
      </svg>
      <span className="pr-num">{pct}%</span>
    </div>
  );
}

/* ---- Toast ---- */
function Toast({ msg }) {
  if (!msg) return null;
  return <div className="toast-wrap"><div className="toast"><Icon name="check" size={16} />{msg}</div></div>;
}

Object.assign(window, {
  Icon, Toggle, InfoTip, Card, Badge, Button, CopyButton, Field, ToggleRow, ProgressRing, Toast,
});
