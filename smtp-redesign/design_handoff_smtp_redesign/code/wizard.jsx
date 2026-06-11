// wizard.jsx — guided first-time setup
const { useState: useStateW, useEffect: useEffectW, useRef: useRefW } = React;

const WIZ_STEPS = ["Fournisseur", "Identifiants", "Test", "Terminé"];

function WizMiniTest({ onPass }) {
  const [statuses, setStatuses] = useStateW(DIAG_STEPS.map(() => "idle"));
  const [running, setRunning] = useStateW(false);
  const [done, setDone] = useStateW(false);
  const timers = useRefW([]);

  function run() {
    timers.current.forEach(clearTimeout);
    setRunning(true); setDone(false);
    setStatuses(DIAG_STEPS.map(() => "idle"));
    let acc = 200;
    DIAG_STEPS.forEach((step, i) => {
      timers.current.push(setTimeout(() => setStatuses(s => { const n = [...s]; n[i] = "running"; return n; }), acc));
      acc += Math.max(380, step.ms - 250);
      timers.current.push(setTimeout(() => {
        setStatuses(s => { const n = [...s]; n[i] = step.kind || "ok"; return n; });
        if (i === DIAG_STEPS.length - 1) { setRunning(false); setDone(true); onPass && onPass(); }
      }, acc));
      acc += 80;
    });
  }
  useEffectW(() => () => timers.current.forEach(clearTimeout), []);

  if (!running && !done) {
    return (
      <div style={{ textAlign: "center", padding: "20px 0" }}>
        <div style={{ width: 70, height: 70, borderRadius: "50%", background: "var(--accent-soft)", color: "var(--accent)", display: "grid", placeItems: "center", margin: "0 auto 16px" }}>
          <Icon name="rocket" size={34} />
        </div>
        <p className="lead" style={{ maxWidth: 360, margin: "0 auto 18px" }}>
          On vérifie tout d'un coup : connexion, chiffrement, authentification, SPF, DKIM et un envoi réel.
        </p>
        <Button variant="primary" size="lg" icon="spark" onClick={run}>Lancer le diagnostic</Button>
      </div>
    );
  }
  return (
    <div className="diag-steps">
      {DIAG_STEPS.map((step, i) => {
        const st = statuses[i];
        return (
          <div key={step.key} className={`diag-step ${st}`}>
            <div className="ds-icon">
              {st === "running" ? <span className="spin" />
                : st === "ok" ? <Icon name="check" size={17} />
                : <Icon name={step.icon} size={17} />}
            </div>
            <div className="ds-body">
              <div className="ds-title">{step.title}</div>
              <div className="ds-note">{st === "idle" ? "En attente…" : st === "running" ? "En cours…" : step.ok}</div>
            </div>
            {st === "ok" && <Badge kind="ok" dot={false}>OK</Badge>}
          </div>
        );
      })}
    </div>
  );
}

function SetupWizard({ onClose, onComplete }) {
  const [step, setStep] = useStateW(0);
  const [provider, setProvider] = useStateW(null);
  const [form, setForm] = useStateW({ host: "", port: 587, secure: "tls", username: "", password: "", fromEmail: "", fromName: "" });
  const [testPassed, setTestPassed] = useStateW(false);
  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));

  function pickProvider(p) {
    setProvider(p.id);
    set("host", p.host);
  }

  const canNext = step === 0 ? !!provider
    : step === 1 ? form.host && form.username && (form.password || provider !== "custom")
    : true;

  return (
    <div className="overlay" onClick={e => { if (e.target.classList.contains("overlay")) onClose(); }}>
      <div className="modal wiz" role="dialog" aria-modal="true">
        <div className="modal-head">
          <div style={{ width: 38, height: 38, borderRadius: 10, background: "linear-gradient(150deg,var(--accent),var(--accent-700))", color: "#fff", display: "grid", placeItems: "center", flexShrink: 0 }}>
            <Icon name="wand" size={20} />
          </div>
          <div>
            <h3>Assistant de configuration</h3>
            <div className="mh-sub">Configurez l'envoi d'e-mails en quelques étapes</div>
          </div>
          <span className="mh-spacer" />
          <button className="modal-x" onClick={onClose} aria-label="Fermer"><Icon name="x" size={18} /></button>
        </div>

        <div className="modal-body">
          {/* progress dots */}
          {step < 3 && (
            <div className="wiz-progress">
              {WIZ_STEPS.slice(0, 3).map((label, i) => (
                <React.Fragment key={label}>
                  <div className={`wiz-dot ${i === step ? "active" : i < step ? "done" : ""}`}>
                    <div className="wd-circle">{i < step ? <Icon name="check" size={15} /> : i + 1}</div>
                    <div className="wd-label">{label}</div>
                  </div>
                  {i < 2 && <div className={`wiz-bar ${i < step ? "filled" : ""}`} />}
                </React.Fragment>
              ))}
            </div>
          )}

          {/* Step 0 — provider */}
          {step === 0 && (
            <div>
              <p className="section-label">Choisissez votre fournisseur d'e-mail</p>
              <div className="provider-grid">
                {PROVIDERS.map(p => (
                  <button key={p.id} className={`prov ${provider === p.id ? "sel" : ""}`} onClick={() => pickProvider(p)}>
                    <span className="prov-logo" style={{ background: p.color }}>{p.abbr}</span>
                    <span>
                      <div className="prov-name">{p.name}</div>
                      <div className="prov-host">{p.host}</div>
                    </span>
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Step 1 — credentials */}
          {step === 1 && (
            <div>
              <p className="section-label">Identifiants du serveur</p>
              <div className="field-row">
                <Field label="Serveur SMTP" required><input className="inp mono" value={form.host} onChange={e => set("host", e.target.value)} placeholder="smtp.exemple.com" /></Field>
              </div>
              <div className="field-row">
                <Field label="Chiffrement">
                  <select className="sel" value={form.secure} onChange={e => { const m = { tls: 587, ssl: 465, "": 25 }; set("secure", e.target.value); set("port", m[e.target.value]); }}>
                    <option value="tls">TLS (recommandé)</option>
                    <option value="ssl">SSL</option>
                    <option value="">Aucun</option>
                  </select>
                </Field>
                <Field label="Port" required><input className="inp" type="number" value={form.port} onChange={e => set("port", e.target.value)} /></Field>
              </div>
              <div className="field-row">
                <Field label="Nom d'utilisateur" required><input className="inp" value={form.username} onChange={e => set("username", e.target.value)} placeholder="vous@exemple.com" /></Field>
              </div>
              <Field label="Mot de passe" required desc="Chiffré en AES-256 avant stockage.">
                <input className="inp" type="password" value={form.password} onChange={e => set("password", e.target.value)} placeholder="••••••••••••" />
              </Field>
              <hr className="hr" />
              <p className="section-label">Adresse d'expédition</p>
              <div className="field-row">
                <Field label="E-mail expéditeur"><input className="inp" value={form.fromEmail} onChange={e => set("fromEmail", e.target.value)} placeholder="boutique@exemple.com" /></Field>
                <Field label="Nom expéditeur"><input className="inp" value={form.fromName} onChange={e => set("fromName", e.target.value)} placeholder="Boutique Mon Site" /></Field>
              </div>
            </div>
          )}

          {/* Step 2 — test */}
          {step === 2 && <WizMiniTest onPass={() => setTestPassed(true)} />}

          {/* Step 3 — done */}
          {step === 3 && (
            <div className="wiz-success">
              <div className="ws-ring"><Icon name="check" size={40} /></div>
              <h3>Configuration terminée 🎉</h3>
              <p>Votre site envoie maintenant ses e-mails via <strong>{form.host || "votre serveur SMTP"}</strong>. Vous pouvez configurer la signature DKIM pour améliorer encore la délivrabilité.</p>
            </div>
          )}
        </div>

        <div className="modal-foot">
          {step > 0 && step < 3 && <Button variant="ghost" onClick={() => setStep(step - 1)} icon="chevron" style={{ transform: "none" }}>Précédent</Button>}
          <span className="mf-spacer" />
          {step < 2 && <Button variant="primary" iconRight="arrow" disabled={!canNext} onClick={() => setStep(step + 1)}>Continuer</Button>}
          {step === 2 && <Button variant="primary" iconRight="arrow" disabled={!testPassed} onClick={() => setStep(3)}>{testPassed ? "Continuer" : "Lancez le test pour continuer"}</Button>}
          {step === 3 && <Button variant="primary" icon="check" onClick={() => { onComplete && onComplete(); onClose(); }}>Accéder au tableau de bord</Button>}
        </div>
      </div>
    </div>
  );
}

window.SetupWizard = SetupWizard;
