// screens-config.jsx — Dashboard + Mailer
const { useState: useStateS } = React;

function DashboardScreen({ config, openWizard, openDiagnostic, go }) {
  const steps = [
    { done: !!config.host, title: "Configurer le serveur d'envoi", desc: config.host ? `${config.host}:${config.port}` : "Aucun serveur configuré", tab: "mailer" },
    { done: config.lastTestOk, title: "Réussir un envoi de test", desc: config.lastTestOk ? "Dernier test réussi" : "Pas encore testé", tab: "mailer" },
    { done: config.dkimEnabled, title: "Activer la signature DKIM", desc: config.dkimEnabled ? `Domaine ${config.dkimDomain}` : "DKIM désactivé", tab: "dkim" },
    { done: config.dnsVerified, title: "Vérifier l'enregistrement DNS", desc: config.dnsVerified ? "DNS vérifié" : "Pas encore vérifié", tab: "dkim" },
  ];
  const doneN = steps.filter(s => s.done).length;
  const pct = Math.round((doneN / steps.length) * 100);
  const fullyOk = config.enabled && config.host && config.lastTestOk;

  return (
    <div>
      {fullyOk ? (
        <div className="banner ok"><Icon name="check" className="b-ico" /><div><strong>Tout est opérationnel.</strong> Votre site envoie ses e-mails via SMTP{config.dkimEnabled && config.dnsVerified ? " avec signature DKIM active" : ""}.</div></div>
      ) : (
        <div className="banner warn"><Icon name="alert" className="b-ico" /><div><strong>Configuration incomplète.</strong> Terminez les étapes ci-dessous pour fiabiliser vos envois.</div></div>
      )}

      <Card title="Progression de la configuration" icon="gauge"
        action={<Button variant="soft" size="sm" icon="wand" onClick={openWizard}>Assistant guidé</Button>}>
        <div className="progress-wrap">
          <ProgressRing pct={pct} />
          <div className="progress-meta">
            <div className="pm-title">{doneN} étape{doneN > 1 ? "s" : ""} sur {steps.length} terminée{doneN > 1 ? "s" : ""}</div>
            <div className="pm-sub">{pct === 100 ? "Bravo — votre configuration est complète." : "Continuez pour une délivrabilité optimale."}</div>
          </div>
        </div>
        <div className="checklist">
          {steps.map((s, i) => (
            <div key={i} className={`cl-item ${s.done ? "done" : "pending"}`}>
              <div className="cl-check"><Icon name={s.done ? "check" : "arrow"} size={15} /></div>
              <div className="cl-body">
                <div className={`cl-title ${s.done ? "struck" : ""}`}>{s.title}</div>
                <div className="cl-desc">{s.desc}</div>
              </div>
              {!s.done && <Button variant="ghost" size="sm" iconRight="arrow" onClick={() => go(s.tab)}>Configurer</Button>}
            </div>
          ))}
        </div>
      </Card>

      <Card title="Activité d'envoi" icon="gauge" hint="30 derniers jours">
        <div className="stats">
          <div className="stat"><div className="s-num">{SMTP_STATS.total.toLocaleString("fr")}</div><div className="s-lab">Total</div></div>
          <div className="stat ok"><div className="s-num">{SMTP_STATS.success.toLocaleString("fr")}</div><div className="s-lab">Livrés</div></div>
          <div className="stat err"><div className="s-num">{SMTP_STATS.failed}</div><div className="s-lab">Échecs</div></div>
          <div className="stat accent"><div className="s-num">{SMTP_STATS.rate}%</div><div className="s-lab">Taux</div></div>
          <div className="stat"><div className="s-num">{SMTP_STATS.dkim.toLocaleString("fr")}</div><div className="s-lab">Signés DKIM</div></div>
        </div>
      </Card>

      <div className="grid-2">
        <Card title="Configuration actuelle" icon="settings">
          <table className="summary">
            <tbody>
              <tr><th>SMTP</th><td><Badge kind={config.enabled ? "ok" : "muted"}>{config.enabled ? "Activé" : "Désactivé"}</Badge></td></tr>
              <tr><th>Serveur</th><td><span className="mono">{config.host || "—"}</span></td></tr>
              <tr><th>Port / Chiffrement</th><td><span className="mono">{config.port} / {config.secure.toUpperCase() || "AUCUN"}</span></td></tr>
              <tr><th>Expéditeur</th><td>{config.fromEmail}</td></tr>
              <tr><th>DKIM</th><td><Badge kind={config.dkimEnabled && config.dnsVerified ? "ok" : config.dkimEnabled ? "warn" : "muted"}>{config.dkimEnabled ? (config.dnsVerified ? "Actif" : "En attente DNS") : "Désactivé"}</Badge></td></tr>
            </tbody>
          </table>
        </Card>

        <Card title="Échecs récents" icon="alertc" action={<span className="linkish" onClick={() => go("logs")}>Tous les logs</span>}>
          <div className="log-list">
            {SMTP_LOGS.filter(l => l.status === "err").slice(0, 3).map(l => (
              <div key={l.id} className="log-item" style={{ cursor: "default", gridTemplateColumns: "34px 1fr" }}>
                <div className="log-status-ico err"><Icon name="x" size={16} /></div>
                <div className="log-main">
                  <div className="log-to">{l.to}</div>
                  <div className="log-subject errtxt">{l.error}</div>
                </div>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}

function MailerScreen({ config, setConfig, openDiagnostic, initialSub, initialOauthWiz, initialOauthConsent }) {
  const [sub, setSub] = useStateS(initialSub || "smtp");
  const set = (k, v) => setConfig(c => ({ ...c, [k]: v }));
  const [recipient, setRecipient] = useStateS(config.fromEmail);
  const [oauthState, setOauthState] = useStateS({ provider: "microsoft", smtpAddress: config.fromEmail, grant: "authorization_code", authMethod: "secret", clientId: "", thumbprint: "", tenant: "", hostedDomain: "", hasSecret: false });
  const [oauthConnected, setOauthConnected] = useStateS(false);
  const [oauthWiz, setOauthWiz] = useStateS(!!initialOauthWiz);
  const activeType = config.enabled ? config.mailerType : null;
  const enableThis = config.enabled && config.mailerType === sub;
  const toggleEnable = v => setConfig(c => ({ ...c, enabled: v, mailerType: v ? sub : c.mailerType }));
  const subLabel = sub === "smtp" ? "SMTP" : "OAuth2";

  return (
    <div>
      <div className="subtabs">
        <button className={`subtab ${sub === "smtp" ? "active" : ""}`} onClick={() => setSub("smtp")}>
          <Icon name="server" size={15} />SMTP {config.enabled && config.mailerType === "smtp" && <span className="st-badge">Actif</span>}
        </button>
        <button className={`subtab ${sub === "oauth" ? "active" : ""}`} onClick={() => setSub("oauth")}>
          <Icon name="shieldcheck" size={15} />OAuth2 {activeType === "oauth" ? <span className="st-badge">Actif</span> : <span className="st-badge" style={{ background: "var(--accent-soft)", color: "var(--accent-700)" }}>Nouveau</span>}
        </button>
      </div>

      {enableThis
        ? <div className="banner ok"><Icon name="check" className="b-ico" /><div>Le mailer <strong>{subLabel}</strong> est actif — tous les e-mails de WordPress passent par lui.</div></div>
        : activeType
          ? <div className="banner warn"><Icon name="alert" className="b-ico" /><div>Le mailer <strong>{activeType === "smtp" ? "SMTP" : "OAuth2"}</strong> est actuellement actif. Activer <strong>{subLabel}</strong> ci-dessous le désactivera.</div></div>
          : <div className="banner info"><Icon name="info" className="b-ico" /><div>Aucun mailer actif. Activez {subLabel} ci-dessous puis testez la connexion.</div></div>}

      <Card title="Activation" icon="plug">
        <ToggleRow id="m-enable" title={`Activer le mailer ${subLabel}`}
          desc="Un seul mailer peut être actif à la fois. Activer celui-ci désactive l'autre."
          checked={enableThis} onChange={toggleEnable} />
      </Card>

      {sub === "oauth" ? (
        <OAuthPanel state={oauthState} setState={setOauthState} connected={oauthConnected} onConnect={() => setOauthWiz(true)} />
      ) : (
      <div className="grid-2">
        <Card title="Serveur" icon="server">
          <Field label="Hôte SMTP" required tip="L'adresse de votre serveur d'envoi, ex. smtp.gmail.com">
            <input className="inp mono" value={config.host} onChange={e => set("host", e.target.value)} placeholder="smtp.exemple.com" />
          </Field>
          <div className="field-row">
            <Field label="Chiffrement" tip="Le port s'ajuste : TLS→587, SSL→465, Aucun→25">
              <select className="sel" value={config.secure} onChange={e => { const m = { tls: 587, ssl: 465, "": 25 }; set("secure", e.target.value); set("port", m[e.target.value]); }}>
                <option value="tls">TLS (recommandé)</option>
                <option value="ssl">SSL</option>
                <option value="">Aucun</option>
              </select>
            </Field>
            <Field label="Port" required>
              <input className="inp" type="number" value={config.port} onChange={e => set("port", e.target.value)} />
            </Field>
          </div>
        </Card>

        <Card title="Authentification" icon="key">
          <ToggleRow id="m-auth" title="Utiliser l'authentification" checked={config.auth} onChange={v => set("auth", v)} />
          {config.auth && <>
            <div style={{ height: 14 }} />
            <Field label="Nom d'utilisateur" required>
              <input className="inp" value={config.username} onChange={e => set("username", e.target.value)} placeholder="vous@exemple.com" />
            </Field>
            <Field label="Mot de passe" required desc="Laissez vide pour conserver le mot de passe enregistré.">
              <input className="inp" type="password" placeholder={config.hasPassword ? "•••••••• (enregistré)" : "Saisir le mot de passe"} />
              <p className="desc" style={{ display: "flex", alignItems: "center", gap: 6, color: "var(--ok-text)" }}><Icon name="lock" size={13} />Chiffré en AES-256-CBC</p>
            </Field>
          </>}
        </Card>
      </div>
      )}

      <Card title="Adresse d'expédition" icon="mail">
        <div className="field-row">
          <Field label="E-mail expéditeur"><input className="inp" value={config.fromEmail} onChange={e => set("fromEmail", e.target.value)} /></Field>
          <Field label="Nom expéditeur"><input className="inp" value={config.fromName} onChange={e => set("fromName", e.target.value)} /></Field>
        </div>
        <ToggleRow id="m-force" title="Forcer l'adresse d'expédition"
          desc="Remplace l'adresse définie par d'autres extensions ou thèmes."
          checked={config.forceFrom} onChange={v => set("forceFrom", v)} />
      </Card>

      {/* TEST — hero */}
      <div className="card" style={{ background: "linear-gradient(160deg, var(--accent-softer), var(--surface))", borderColor: "var(--accent-soft)" }}>
        <div className="card-head"><Icon name="rocket" size={18} style={{ color: "var(--accent)" }} /><h2>Tester votre configuration</h2></div>
        <p className="lead">Un seul clic lance un diagnostic complet — connexion, chiffrement, authentification, SPF, DKIM, puis un envoi réel — et vous montre exactement où ça bloque, étape par étape.</p>
        <div className="field-row" style={{ alignItems: "flex-end", gap: 12 }}>
          <Field label="Envoyer le test à"><input className="inp" value={recipient} onChange={e => setRecipient(e.target.value)} /></Field>
          <Button variant="primary" size="lg" icon="spark" style={{ flexShrink: 0, marginBottom: 16 }} onClick={() => openDiagnostic(recipient)}>Lancer le diagnostic complet</Button>
        </div>
        {config.lastTestOk && <div className="muted-note" style={{ display: "flex", alignItems: "center", gap: 6 }}><Icon name="check" size={14} style={{ color: "var(--ok)" }} />Dernier diagnostic réussi — tous les contrôles au vert.</div>}
      </div>

      <div style={{ display: "flex", justifyContent: "flex-end", marginTop: 4 }}>
        <Button variant="primary" size="lg" icon="check">Enregistrer les réglages {subLabel}</Button>
      </div>

      {oauthWiz && <OAuthWizard initialProvider={oauthState.provider}
        initialStep={initialOauthConsent ? 2 : 0}
        initialPhase={initialOauthConsent ? "consent" : "idle"}
        onClose={() => setOauthWiz(false)}
        onComplete={(p) => { setOauthConnected(true); setOauthState(s => ({ ...s, provider: p })); setConfig(c => ({ ...c, enabled: true, mailerType: "oauth" })); }} />}
    </div>
  );
}

Object.assign(window, { DashboardScreen, MailerScreen });
