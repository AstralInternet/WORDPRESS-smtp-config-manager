// oauth.jsx — OAuth2 mailer panel + OAuth2 connection wizard
const { useState: useStateO, useEffect: useEffectO, useRef: useRefO } = React;

const OAUTH_PROVIDERS = {
  microsoft: { name: "Microsoft 365 / Outlook", short: "Microsoft", color: "#0078d4", abbr: "M", host: "smtp.office365.com" },
  google:    { name: "Google Workspace / Gmail", short: "Google",   color: "#ea4335", abbr: "G", host: "smtp.gmail.com" },
};
const REDIRECT_URI = "https://monsite.com/wp-admin/options-general.php?page=simple-smtp-dkim&oauth=callback";

/* =====================================================================
   OAuth2 sub-tab panel
   ===================================================================== */
function OAuthPanel({ state, setState, connected, onConnect }) {
  const set = (k, v) => setState(s => ({ ...s, [k]: v }));
  const prov = OAUTH_PROVIDERS[state.provider] || OAUTH_PROVIDERS.microsoft;

  return (
    <>
      {/* Connect hero */}
      <div className="card" style={{ background: "linear-gradient(160deg, var(--accent-softer), var(--surface))", borderColor: "var(--accent-soft)" }}>
        <div className="card-head"><Icon name="shieldcheck" size={18} style={{ color: "var(--accent)" }} /><h2>Connexion OAuth2</h2></div>
        <p className="lead">La méthode moderne et la plus sûre : aucun mot de passe n'est stocké. Vous autorisez l'envoi via votre compte {prov.short}, et un jeton révocable est utilisé à la place.</p>
        {connected ? (
          <div style={{ display: "flex", alignItems: "center", gap: 12, flexWrap: "wrap" }}>
            <Badge kind="ok" dot={false}><Icon name="check" size={13} />Connecté à {prov.short} — jeton actif</Badge>
            <Button variant="ghost" size="sm" icon="refresh" onClick={onConnect}>Reconnecter</Button>
          </div>
        ) : (
          <Button variant="primary" icon="plug" onClick={onConnect}>Se connecter avec {prov.short}</Button>
        )}
      </div>

      <div className="grid-2">
        <Card title="Fournisseur" icon="globe">
          <Field label="Fournisseur d'e-mail" required>
            <select className="sel" value={state.provider} onChange={e => set("provider", e.target.value)}>
              <option value="microsoft">Microsoft 365 / Outlook</option>
              <option value="google">Google Workspace / Gmail</option>
            </select>
          </Field>
          <Field label="Adresse d'enveloppe (SMTP)" required tip="L'adresse e-mail utilisée comme expéditeur d'enveloppe. En général votre boîte aux lettres.">
            <input className="inp" value={state.smtpAddress} onChange={e => set("smtpAddress", e.target.value)} placeholder="vous@votredomaine.com" />
          </Field>
          <p className="desc" style={{ display: "flex", alignItems: "center", gap: 6 }}><Icon name="server" size={13} />Hôte : <span className="mono">{prov.host}</span> · port 587 (TLS)</p>
        </Card>

        <Card title="Méthode d'autorisation" icon="key">
          <Field label="Type d'octroi (grant)" tip="Authorization Code : consentement utilisateur + jeton de rafraîchissement (le plus courant). Client Credentials : accès applicatif, sans interaction (comptes de service).">
            <select className="sel" value={state.grant} onChange={e => set("grant", e.target.value)}>
              <option value="authorization_code">Authorization Code (consentement utilisateur)</option>
              <option value="client_credentials">Client Credentials (applicatif / compte de service)</option>
            </select>
          </Field>
          <Field label="Type d'identifiant">
            <div className="radio-cards cols-2">
              <label className={`rc ${state.authMethod === "secret" ? "sel" : ""}`} onClick={() => set("authMethod", "secret")}>
                <span className="rc-dot" /><span><div className="rc-title">Secret client</div><div className="rc-desc">Plus simple</div></span>
              </label>
              <label className={`rc ${state.authMethod === "certificate" ? "sel" : ""}`} onClick={() => set("authMethod", "certificate")}>
                <span className="rc-dot" /><span><div className="rc-title">Certificat X.509</div><div className="rc-desc">Plus sûr</div></span>
              </label>
            </div>
          </Field>
        </Card>
      </div>

      <Card title="Identifiants de l'application" icon="lock">
        <Field label="ID client (Application ID)" required>
          <input className="inp mono" value={state.clientId} onChange={e => set("clientId", e.target.value)} placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
        </Field>
        {state.authMethod === "secret" ? (
          <Field label="Secret client" required desc="Chiffré en AES-256 avant stockage.">
            <input className="inp mono" type="password" placeholder={state.hasSecret ? "•••••••• (enregistré)" : "Saisir le secret client"} />
          </Field>
        ) : (
          <div className="field-row">
            <Field label="Empreinte du certificat" required tip="Empreinte SHA-1 (hex) du certificat X.509.">
              <input className="inp mono" value={state.thumbprint} onChange={e => set("thumbprint", e.target.value)} placeholder="A1B2C3…" />
            </Field>
          </div>
        )}
        {state.grant === "authorization_code" && (
          <Field label="Jeton de rafraîchissement" required tip="Obtenu lors du flux de consentement OAuth2 initial.">
            <input className="inp mono" type="password" placeholder={connected ? "•••••••• (obtenu via l'assistant)" : "Lancez « Se connecter » pour l'obtenir automatiquement"} readOnly />
            <p className="desc" style={{ display: "flex", alignItems: "center", gap: 6, color: connected ? "var(--ok-text)" : "var(--ink-3)" }}>
              <Icon name={connected ? "check" : "info"} size={13} />{connected ? "Rempli automatiquement par l'assistant de connexion." : "L'assistant le récupère pour vous — pas de copier-coller manuel."}
            </p>
          </Field>
        )}
      </Card>

      <div className="grid-2">
        {state.provider === "microsoft" && (
          <Card title="Paramètres Microsoft" icon="settings">
            <Field label="ID de locataire (Tenant)" required tip="Le GUID de votre locataire Azure AD. Azure Portal → Azure AD → Propriétés.">
              <input className="inp mono" value={state.tenant} onChange={e => set("tenant", e.target.value)} placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
            </Field>
          </Card>
        )}
        {state.provider === "google" && (
          <Card title="Paramètres Google" icon="settings">
            <Field label="Domaine hébergé" tip="Votre domaine Google Workspace. Laissez vide pour un compte @gmail.com personnel.">
              <input className="inp" value={state.hostedDomain} onChange={e => set("hostedDomain", e.target.value)} placeholder="votredomaine.com" />
            </Field>
          </Card>
        )}
        <Card title="URI de redirection" icon="globe">
          <p className="desc" style={{ marginTop: 0, marginBottom: 10 }}>Ajoutez cette URI dans la configuration de votre application chez le fournisseur :</p>
          <div className="dns-block" style={{ marginTop: 0 }}>
            <div className="dns-row">
              <div className="dns-val"><code>{REDIRECT_URI}</code><CopyButton text={REDIRECT_URI} /></div>
            </div>
          </div>
        </Card>
      </div>

      <Card>
        <details>
          <summary style={{ cursor: "pointer", fontWeight: 600, fontSize: 13.5, display: "flex", alignItems: "center", gap: 8 }}>
            <Icon name="info" size={15} style={{ color: "var(--ink-3)" }} />Guide de configuration ({prov.short})
          </summary>
          <div style={{ paddingTop: 14 }}>
            {state.provider === "microsoft" ? (
              <ol className="muted-note" style={{ paddingLeft: 18, lineHeight: 1.9, margin: 0 }}>
                <li>Azure Portal → <strong>Inscriptions d'applications → Nouvelle inscription</strong></li>
                <li>Définissez l'URI de redirection ci-dessus</li>
                <li><strong>Certificats &amp; secrets</strong> : créez un secret client ou téléversez un certificat</li>
                <li><strong>Autorisations API</strong> : ajoutez <span className="mono">SMTP.Send</span> (déléguée)</li>
                <li>Copiez l'<strong>ID d'application</strong> et l'<strong>ID de locataire</strong></li>
                <li>Lancez « Se connecter » pour obtenir le jeton</li>
              </ol>
            ) : (
              <ol className="muted-note" style={{ paddingLeft: 18, lineHeight: 1.9, margin: 0 }}>
                <li>Google Cloud Console → <strong>API et services → Identifiants</strong></li>
                <li>Créez un <strong>ID client OAuth 2.0</strong> (application Web)</li>
                <li>Définissez l'URI de redirection ci-dessus</li>
                <li>Activez l'<strong>API Gmail</strong> dans votre projet</li>
                <li>Copiez l'<strong>ID client</strong> et le <strong>secret client</strong></li>
                <li>Lancez « Se connecter » pour obtenir le jeton</li>
              </ol>
            )}
          </div>
        </details>
      </Card>
    </>
  );
}

/* =====================================================================
   OAuth2 connection wizard (the integrable example)
   ===================================================================== */
const OWIZ_STEPS = ["Fournisseur", "Application", "Autorisation", "Terminé"];

function OAuthWizard({ initialProvider, initialStep, initialPhase, onClose, onComplete }) {
  const [step, setStep] = useStateO(initialStep || 0);
  const [provider, setProvider] = useStateO(initialProvider || null);
  const [form, setForm] = useStateO({ clientId: "", clientSecret: "", tenant: "", hostedDomain: "", smtpAddress: "" });
  const [phase, setPhase] = useStateO(initialPhase || "idle"); // idle | redirecting | consent | exchanging | granted
  const set = (k, v) => setForm(f => ({ ...f, [k]: v }));
  const prov = provider ? OAUTH_PROVIDERS[provider] : null;
  const timers = useRefO([]);
  useEffectO(() => () => timers.current.forEach(clearTimeout), []);

  function authorize() {
    setPhase("redirecting");
    timers.current.push(setTimeout(() => setPhase("consent"), 1300));
  }
  function grant() {
    setPhase("exchanging");
    timers.current.push(setTimeout(() => setPhase("granted"), 1300));
    timers.current.push(setTimeout(() => setStep(3), 2200));
  }

  const canNext = step === 0 ? !!provider
    : step === 1 ? form.clientId && (provider === "microsoft" ? form.tenant : true)
    : true;

  return ReactDOM.createPortal((
    <div className="overlay" onClick={e => { if (e.target.classList.contains("overlay")) onClose(); }}>
      <div className="modal wiz" role="dialog" aria-modal="true">
        <div className="modal-head">
          <div style={{ width: 38, height: 38, borderRadius: 10, background: "linear-gradient(150deg,var(--accent),var(--accent-700))", color: "#fff", display: "grid", placeItems: "center", flexShrink: 0 }}>
            <Icon name="shieldcheck" size={20} />
          </div>
          <div>
            <h3>Connexion OAuth2</h3>
            <div className="mh-sub">Autorisez l'envoi via votre compte — sans mot de passe</div>
          </div>
          <span className="mh-spacer" />
          <button className="modal-x" onClick={onClose} aria-label="Fermer"><Icon name="x" size={18} /></button>
        </div>

        <div className="modal-body">
          {step < 3 && (
            <div className="wiz-progress">
              {OWIZ_STEPS.slice(0, 3).map((label, i) => (
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
              <p className="section-label">Choisissez votre fournisseur</p>
              <div className="provider-grid">
                {Object.entries(OAUTH_PROVIDERS).map(([id, p]) => (
                  <button key={id} className={`prov ${provider === id ? "sel" : ""}`} onClick={() => setProvider(id)}>
                    <span className="prov-logo" style={{ background: p.color }}>{p.abbr}</span>
                    <span><div className="prov-name">{p.short}</div><div className="prov-host">{p.host}</div></span>
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Step 1 — app creds */}
          {step === 1 && (
            <div>
              <p className="section-label">Identifiants de l'application {prov.short}</p>
              <Field label="ID client (Application ID)" required>
                <input className="inp mono" value={form.clientId} onChange={e => set("clientId", e.target.value)} placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
              </Field>
              <Field label="Secret client" required desc="Chiffré en AES-256 avant stockage.">
                <input className="inp mono" type="password" value={form.clientSecret} onChange={e => set("clientSecret", e.target.value)} placeholder="Saisir le secret client" />
              </Field>
              {provider === "microsoft"
                ? <Field label="ID de locataire (Tenant)" required><input className="inp mono" value={form.tenant} onChange={e => set("tenant", e.target.value)} placeholder="xxxxxxxx-xxxx-…" /></Field>
                : <Field label="Domaine hébergé"><input className="inp" value={form.hostedDomain} onChange={e => set("hostedDomain", e.target.value)} placeholder="votredomaine.com (facultatif)" /></Field>}
              <div className="banner info" style={{ margin: "4px 0 0" }}>
                <Icon name="globe" className="b-ico" />
                <div>URI de redirection à déclarer dans votre app :<br /><code style={{ fontFamily: "var(--mono)", fontSize: 11.5, wordBreak: "break-all" }}>{REDIRECT_URI}</code></div>
              </div>
            </div>
          )}

          {/* Step 2 — authorization (consent simulation) */}
          {step === 2 && (
            <div style={{ minHeight: 240 }}>
              {phase === "idle" && (
                <div style={{ textAlign: "center", padding: "16px 0" }}>
                  <div style={{ width: 64, height: 64, borderRadius: "50%", background: prov.color, color: "#fff", display: "grid", placeItems: "center", margin: "0 auto 16px", fontSize: 26, fontWeight: 800 }}>{prov.abbr}</div>
                  <p className="lead" style={{ maxWidth: 360, margin: "0 auto 18px" }}>Vous allez être redirigé vers <strong>{prov.short}</strong> pour autoriser l'envoi d'e-mails. Connectez-vous avec le compte expéditeur.</p>
                  <Button variant="primary" size="lg" icon="plug" onClick={authorize}>Autoriser l'accès</Button>
                </div>
              )}
              {phase === "redirecting" && (
                <div style={{ textAlign: "center", padding: "48px 0", color: "var(--ink-2)" }}>
                  <span className="spin" style={{ width: 28, height: 28, color: prov.color }} />
                  <p className="lead" style={{ marginTop: 16 }}>Redirection vers {prov.short}…</p>
                </div>
              )}
              {phase === "consent" && (
                <div style={{ border: "1px solid var(--line)", borderRadius: "var(--radius)", overflow: "hidden", boxShadow: "var(--shadow)" }}>
                  <div style={{ background: prov.color, color: "#fff", padding: "13px 18px", display: "flex", alignItems: "center", gap: 10, fontWeight: 700 }}>
                    <span style={{ width: 24, height: 24, borderRadius: 6, background: "rgba(255,255,255,.25)", display: "grid", placeItems: "center", fontSize: 13 }}>{prov.abbr}</span>
                    Se connecter à {prov.short}
                  </div>
                  <div style={{ padding: 20 }}>
                    <div style={{ fontSize: 13.5, fontWeight: 600, marginBottom: 4 }}>Mon Site souhaite accéder à votre compte</div>
                    <div className="muted-note" style={{ marginBottom: 14 }}>{form.smtpAddress || "vous@votredomaine.com"}</div>
                    <div style={{ display: "flex", flexDirection: "column", gap: 9, marginBottom: 18 }}>
                      {["Envoyer des e-mails en votre nom", "Maintenir l'accès aux données autorisées"].map(p => (
                        <div key={p} style={{ display: "flex", alignItems: "center", gap: 9, fontSize: 13 }}>
                          <Icon name="check" size={15} style={{ color: "var(--ok)" }} />{p}
                        </div>
                      ))}
                    </div>
                    <div style={{ display: "flex", gap: 10, justifyContent: "flex-end" }}>
                      <Button variant="ghost" onClick={onClose}>Annuler</Button>
                      <Button variant="primary" style={{ background: prov.color }} onClick={grant}>Accepter</Button>
                    </div>
                  </div>
                </div>
              )}
              {phase === "exchanging" && (
                <div style={{ textAlign: "center", padding: "48px 0", color: "var(--ink-2)" }}>
                  <span className="spin" style={{ width: 28, height: 28, color: "var(--accent)" }} />
                  <p className="lead" style={{ marginTop: 16 }}>Échange du code contre un jeton…</p>
                </div>
              )}
              {phase === "granted" && (
                <div style={{ textAlign: "center", padding: "40px 0" }}>
                  <div className="ws-ring" style={{ width: 64, height: 64, margin: "0 auto 14px" }}><Icon name="check" size={34} /></div>
                  <p className="lead" style={{ fontWeight: 600, color: "var(--ink)" }}>Jeton de rafraîchissement obtenu et chiffré.</p>
                </div>
              )}
            </div>
          )}

          {/* Step 3 — done */}
          {step === 3 && (
            <div className="wiz-success">
              <div className="ws-ring"><Icon name="shieldcheck" size={40} /></div>
              <h3>Connecté à {prov.short}</h3>
              <p>L'envoi OAuth2 est actif. Aucun mot de passe n'est stocké — un jeton révocable est utilisé, et vous pouvez retirer l'accès à tout moment depuis votre compte {prov.short}.</p>
            </div>
          )}
        </div>

        <div className="modal-foot">
          {step > 0 && step < 2 && <Button variant="ghost" onClick={() => setStep(step - 1)}>Précédent</Button>}
          <span className="mf-spacer" />
          {step < 1 && <Button variant="primary" iconRight="arrow" disabled={!canNext} onClick={() => setStep(step + 1)}>Continuer</Button>}
          {step === 1 && <Button variant="primary" iconRight="arrow" disabled={!canNext} onClick={() => setStep(2)}>Autoriser</Button>}
          {step === 3 && <Button variant="primary" icon="check" onClick={() => { onComplete && onComplete(provider); onClose(); }}>Terminer</Button>}
        </div>
      </div>
    </div>
  ), document.body);
}

Object.assign(window, { OAuthPanel, OAuthWizard, OAUTH_PROVIDERS });
