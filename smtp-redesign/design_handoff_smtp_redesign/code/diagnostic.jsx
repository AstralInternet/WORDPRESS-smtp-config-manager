// diagnostic.jsx — animated step-by-step connection/send diagnostic
const { useState: useStateD, useEffect: useEffectD, useRef: useRefD } = React;

const DIAG_STEPS = [
  { key: "resolve", icon: "globe",       title: "Résolution DNS du serveur",     ok: "smtp.monsite.com → 198.51.100.24", ms: 650 },
  { key: "connect", icon: "plug",        title: "Connexion au serveur",          ok: "Connecté sur le port 587", ms: 750 },
  { key: "tls",     icon: "lock",        title: "Négociation du chiffrement TLS", ok: "TLS 1.3 — certificat valide", ms: 700 },
  { key: "auth",    icon: "key",         title: "Authentification SMTP",         ok: "Identifiants acceptés (235 OK)", ms: 850 },
  { key: "spf",     icon: "shield",      title: "Vérification SPF",              ok: "Le serveur est autorisé dans l'enregistrement SPF", ms: 700, kind: "ok" },
  { key: "dkim",    icon: "shieldcheck", title: "Signature DKIM",                ok: "Clé valide — DNS vérifié (default._domainkey)", ms: 800 },
  { key: "send",    icon: "send",        title: "Envoi de l'e-mail de test",     ok: "Message accepté pour livraison (250 OK)", ms: 950 },
];

const DEBUG_LINES = [
  "CLIENT -> SERVER: EHLO monsite.com",
  "SERVER -> CLIENT: 250-smtp.monsite.com Hello [198.51.100.24]",
  "SERVER -> CLIENT: 250-STARTTLS 250-AUTH LOGIN PLAIN",
  "CLIENT -> SERVER: STARTTLS",
  "SERVER -> CLIENT: 220 2.0.0 Ready to start TLS",
  "CLIENT -> SERVER: AUTH LOGIN  [credentials hidden]",
  "SERVER -> CLIENT: 235 2.7.0 Authentication successful",
  "CLIENT -> SERVER: MAIL FROM:<boutique@monsite.com>",
  "SERVER -> CLIENT: 250 2.1.0 OK",
  "CLIENT -> SERVER: DATA  [DKIM-Signature added]",
  "SERVER -> CLIENT: 250 2.0.0 OK: queued as 9F2A1C",
];

function TestDiagnostic({ recipient, onClose }) {
  // status per step: 'idle' | 'running' | 'ok' | 'warn' | 'err'
  const [statuses, setStatuses] = useStateD(DIAG_STEPS.map(() => "idle"));
  const [done, setDone] = useStateD(false);
  const [showDebug, setShowDebug] = useStateD(false);
  const [runId, setRunId] = useStateD(0);
  const timers = useRefD([]);

  useEffectD(() => {
    timers.current.forEach(clearTimeout);
    timers.current = [];
    setStatuses(DIAG_STEPS.map(() => "idle"));
    setDone(false);
    let acc = 350;
    DIAG_STEPS.forEach((step, i) => {
      timers.current.push(setTimeout(() => {
        setStatuses(s => { const n = [...s]; n[i] = "running"; return n; });
      }, acc));
      acc += step.ms;
      timers.current.push(setTimeout(() => {
        setStatuses(s => { const n = [...s]; n[i] = step.kind || "ok"; return n; });
        if (i === DIAG_STEPS.length - 1) setDone(true);
      }, acc));
      acc += 120;
    });
    return () => timers.current.forEach(clearTimeout);
  }, [runId]);

  const passed = statuses.filter(s => s === "ok").length;

  return (
    <div className="overlay" onClick={e => { if (e.target.classList.contains("overlay")) onClose(); }}>
      <div className="modal" role="dialog" aria-modal="true">
        <div className="modal-head">
          <div style={{ width: 38, height: 38, borderRadius: 10, background: "var(--accent-soft)", color: "var(--accent)", display: "grid", placeItems: "center", flexShrink: 0 }}>
            <Icon name="rocket" size={20} />
          </div>
          <div>
            <h3>Diagnostic de livraison</h3>
            <div className="mh-sub">Envoi d'un message de test vers <strong>{recipient}</strong></div>
          </div>
          <span className="mh-spacer" />
          <button className="modal-x" onClick={onClose} aria-label="Fermer"><Icon name="x" size={18} /></button>
        </div>

        <div className="modal-body">
          {done && (
            <div className={`diag-summary ${passed === DIAG_STEPS.length ? "ok" : "err"}`}>
              <Icon name={passed === DIAG_STEPS.length ? "check" : "alert"} size={20} />
              {passed === DIAG_STEPS.length
                ? "Tout fonctionne — l'e-mail de test a été livré avec succès."
                : "Le diagnostic a rencontré un problème."}
            </div>
          )}

          <div className="diag-steps">
            {DIAG_STEPS.map((step, i) => {
              const st = statuses[i];
              return (
                <div key={step.key} className={`diag-step ${st}`}>
                  <div className="ds-icon">
                    {st === "running" ? <span className="spin" />
                      : st === "ok" || st === "warn" ? <Icon name="check" size={17} />
                      : st === "err" ? <Icon name="x" size={17} />
                      : <Icon name={step.icon} size={17} />}
                  </div>
                  <div className="ds-body">
                    <div className="ds-title">{step.title}</div>
                    <div className="ds-note">
                      {st === "idle" ? "En attente…"
                        : st === "running" ? "Vérification en cours…"
                        : step.ok}
                    </div>
                  </div>
                  {(st === "ok") && <Badge kind="ok" dot={false}>OK</Badge>}
                  {(st === "warn") && <Badge kind="warn" dot={false}>Attention</Badge>}
                  {(st === "err") && <Badge kind="err" dot={false}>Échec</Badge>}
                </div>
              );
            })}
          </div>

          {done && (
            <>
              <button className="linkish" style={{ marginTop: 16, display: "inline-flex", alignItems: "center", gap: 6 }}
                onClick={() => setShowDebug(v => !v)}>
                <Icon name="bug" size={15} />{showDebug ? "Masquer" : "Afficher"} le journal de débogage SMTP
              </button>
              {showDebug && (
                <pre className="debug-pre">{DEBUG_LINES.join("\n")}</pre>
              )}
            </>
          )}
        </div>

        <div className="modal-foot">
          {done && <Button variant="ghost" icon="refresh" onClick={() => setRunId(r => r + 1)}>Relancer</Button>}
          <span className="mf-spacer" />
          <Button variant={done ? "primary" : "ghost"} onClick={onClose}>{done ? "Terminé" : "Annuler"}</Button>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { TestDiagnostic, DIAG_STEPS });
