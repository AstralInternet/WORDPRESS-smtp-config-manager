// screens-logs.jsx — DKIM + Email Logs + Advanced
const { useState: useStateL } = React;

function DkimScreen({ config, setConfig, toast }) {
  const set = (k, v) => setConfig(c => ({ ...c, [k]: v }));
  const [showDns, setShowDns] = useStateL(config.hasKeys);
  const [generating, setGenerating] = useStateL(false);
  const [validating, setValidating] = useStateL(false);
  const [validated, setValidated] = useStateL(config.dnsVerified);

  const dnsName = `${config.dkimSelector}._domainkey.${config.dkimDomain}`;
  const dnsValue = "v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2vХ9k…q8wIDAQAB";

  function generate() {
    setGenerating(true);
    setTimeout(() => { setGenerating(false); setShowDns(true); set("hasKeys", true); toast("Clés DKIM générées"); }, 1400);
  }
  function validate() {
    setValidating(true); setValidated(false);
    setTimeout(() => { setValidating(false); setValidated(true); set("dnsVerified", true); toast("DKIM validé — DNS vérifié"); }, 1600);
  }

  return (
    <div>
      {config.dkimEnabled
        ? (config.dnsVerified
          ? <div className="banner ok"><Icon name="shieldcheck" className="b-ico" /><div>DKIM est <strong>actif</strong> — vos e-mails sortants sont signés.</div></div>
          : <div className="banner warn"><Icon name="alert" className="b-ico" /><div>DKIM est activé mais <strong>ne signe pas encore</strong>. Terminez la validation DNS ci-dessous.</div></div>)
        : <div className="banner info"><Icon name="info" className="b-ico" /><div>La signature DKIM améliore fortement la délivrabilité. Activez-la pour commencer.</div></div>}

      <Card title="Signature DKIM" icon="shield">
        <ToggleRow id="d-enable" title="Activer la signature DKIM"
          desc="Signe les e-mails sortants. La signature ne s'applique qu'après validation DNS réussie."
          checked={config.dkimEnabled} onChange={v => set("dkimEnabled", v)} />
        {config.dkimEnabled && <>
          <div style={{ height: 14 }} />
          <div className="field-row">
            <Field label="Domaine" required><input className="inp mono" value={config.dkimDomain} onChange={e => set("dkimDomain", e.target.value)} placeholder="exemple.com" /></Field>
            <Field label="Sélecteur" required tip="Identifiant unique de la clé. Valeurs courantes : « default », « mail ».">
              <input className="inp mono" value={config.dkimSelector} onChange={e => set("dkimSelector", e.target.value)} placeholder="default" />
            </Field>
          </div>
        </>}
      </Card>

      {config.dkimEnabled && <>
        <Card title="Générer les clés DKIM" icon="key">
          <p className="lead">Générez automatiquement une paire de clés 2048-bit. Vous ajouterez ensuite l'enregistrement DNS chez votre registraire.</p>
          <div style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
            <Button variant="primary" icon="key" loading={generating} onClick={generate}>{config.hasKeys ? "Régénérer les clés" : "Générer les clés DKIM"}</Button>
            {config.hasKeys && !showDns && <Button variant="ghost" icon="eye" onClick={() => setShowDns(true)}>Voir l'enregistrement DNS</Button>}
          </div>

          {showDns && (
            <div className="dns-block">
              <div style={{ padding: "13px 16px", borderBottom: "1px solid var(--line-2)", fontSize: 13, fontWeight: 600, display: "flex", alignItems: "center", gap: 8 }}>
                <Icon name="globe" size={16} style={{ color: "var(--accent)" }} />Ajoutez cet enregistrement TXT chez votre registraire DNS
              </div>
              <div className="dns-row">
                <div className="dns-key">Nom</div>
                <div className="dns-val"><code>{dnsName}</code><CopyButton text={dnsName} /></div>
              </div>
              <div className="dns-row">
                <div className="dns-key">Type</div>
                <div className="dns-val"><code>TXT</code></div>
              </div>
              <div className="dns-row">
                <div className="dns-key">Valeur</div>
                <div className="dns-val"><code>{dnsValue}</code><CopyButton text={dnsValue} /></div>
              </div>
            </div>
          )}
        </Card>

        <Card title="Valider la configuration" icon="shieldcheck">
          <p className="lead">Vérifiez que la clé privée est valide et que l'enregistrement DNS est correctement publié.</p>
          <div style={{ display: "flex", alignItems: "center", gap: 14, flexWrap: "wrap" }}>
            <Button variant="ghost" icon="shieldcheck" loading={validating} onClick={validate}>Valider DKIM</Button>
            {validated && !validating && <Badge kind="ok" dot={false}><Icon name="check" size={13} />DNS vérifié et signature active</Badge>}
          </div>
        </Card>

        <Card>
          <details>
            <summary style={{ cursor: "pointer", fontWeight: 600, fontSize: 14, display: "flex", alignItems: "center", gap: 8 }}>
              <Icon name="settings" size={16} style={{ color: "var(--ink-3)" }} />Configuration manuelle / avancée
            </summary>
            <div style={{ paddingTop: 16 }}>
              <p className="muted-note" style={{ marginBottom: 14 }}>Utilisez cette section si vous gérez vos clés DKIM manuellement (générées ailleurs).</p>
              <p className="section-label">Stockage de la clé privée</p>
              <div className="radio-cards cols-2">
                <label className={`rc ${config.storageMethod === "database" ? "sel" : ""}`} onClick={() => set("storageMethod", "database")}>
                  <span className="rc-dot" /><span><div className="rc-title">Base de données</div><div className="rc-desc">Chiffrée AES-256</div></span>
                </label>
                <label className={`rc ${config.storageMethod === "file" ? "sel" : ""}`} onClick={() => set("storageMethod", "file")}>
                  <span className="rc-dot" /><span><div className="rc-title">Fichier serveur</div><div className="rc-desc">Chemin protégé</div></span>
                </label>
              </div>
              <div style={{ height: 16 }} />
              <p className="section-label">Générer manuellement (OpenSSL)</p>
              <div className="code-box">
                <span className="cm"># Clé privée</span><br />
                openssl genrsa -out dkim.private 2048<br /><br />
                <span className="cm"># Clé publique</span><br />
                openssl rsa -in dkim.private -pubout -out dkim.public
              </div>
            </div>
          </details>
        </Card>
      </>}

      <div style={{ display: "flex", justifyContent: "flex-end", marginTop: 4 }}>
        <Button variant="primary" size="lg" icon="check">Enregistrer les réglages DKIM</Button>
      </div>
    </div>
  );
}

function LogsScreen({ config, setConfig, toast, initialOpenLog }) {
  const set = (k, v) => setConfig(c => ({ ...c, [k]: v }));
  const [filter, setFilter] = useStateL("all");
  const [query, setQuery] = useStateL("");
  const [open, setOpen] = useStateL(initialOpenLog ? SMTP_LOGS[0] : null);

  const counts = { all: SMTP_LOGS.length, ok: SMTP_LOGS.filter(l => l.status === "ok").length, err: SMTP_LOGS.filter(l => l.status === "err").length };
  const filtered = SMTP_LOGS.filter(l =>
    (filter === "all" || l.status === filter) &&
    (!query || l.to.toLowerCase().includes(query.toLowerCase()) || l.subject.toLowerCase().includes(query.toLowerCase()))
  );

  if (!config.loggingEnabled) {
    return (
      <div>
        <Card title="Journalisation" icon="list">
          <ToggleRow id="l-enable" title="Activer la journalisation" desc="Enregistre chaque e-mail envoyé avec son statut." checked={config.loggingEnabled} onChange={v => set("loggingEnabled", v)} />
        </Card>
        <Card><div className="empty"><Icon name="inbox" /><div className="e-title">La journalisation est désactivée</div><p className="muted-note">Activez-la ci-dessus pour suivre vos envois.</p></div></Card>
      </div>
    );
  }

  return (
    <div>
      <Card title="Réglages de journalisation" icon="list">
        <ToggleRow id="l-enable" title="Activer la journalisation" checked={config.loggingEnabled} onChange={v => set("loggingEnabled", v)} />
        <ToggleRow id="l-body" title="Conserver le contenu des e-mails" tip="Le contenu est chiffré avant stockage. Peut inclure des données sensibles."
          desc="Permet de revoir le message complet — réinitialisations de mot de passe, codes de vérification, etc."
          checked={config.logBody} onChange={v => set("logBody", v)} />
        <div className="trow">
          <div className="t-main">
            <div className="t-title">Durée de conservation</div>
            <div className="t-desc">Les logs plus anciens sont supprimés automatiquement. 0 = conserver indéfiniment.</div>
          </div>
          <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
            <input className="inp" type="number" value={config.retentionDays} onChange={e => set("retentionDays", e.target.value)} style={{ width: 80 }} /><span className="muted-note">jours</span>
          </div>
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

      <Card title="Journal des e-mails" icon="inbox"
        action={<div style={{ display: "flex", gap: 8 }}>
          <Button variant="ghost" size="sm" icon="download" onClick={() => toast("Export CSV téléchargé")}>Export CSV</Button>
          <Button variant="danger" size="sm" icon="trash" onClick={() => toast("Tous les logs supprimés")}>Tout effacer</Button>
        </div>}>
        <div className="logs-toolbar">
          <div className="filter-pills">
            {[["all", "Tous"], ["ok", "Livrés"], ["err", "Échecs"]].map(([k, lab]) => (
              <button key={k} className={`fp ${filter === k ? "active" : ""}`} onClick={() => setFilter(k)}>
                {lab} <span className="fp-n">{counts[k]}</span>
              </button>
            ))}
          </div>
          <div className="search-box">
            <Icon name="search" size={16} />
            <input placeholder="Rechercher par destinataire ou objet…" value={query} onChange={e => setQuery(e.target.value)} />
          </div>
        </div>

        {filtered.length ? (
          <div className="log-list">
            {filtered.map(l => (
              <div key={l.id} className="log-item" onClick={() => setOpen(l)}>
                <div className={`log-status-ico ${l.status}`}><Icon name={l.status === "ok" ? "check" : "x"} size={16} /></div>
                <div className="log-main">
                  <div className="log-line1"><span className="log-to">{l.to}</span></div>
                  <div className="log-subject">{l.status === "err" ? <span className="errtxt">{l.error}</span> : l.subject}</div>
                </div>
                <div className="log-meta">
                  <span className="log-date">{l.date}</span>
                  <span className={`dkim-tag ${l.dkim ? "yes" : "no"}`}>{l.dkim ? "DKIM ✓" : "non signé"}</span>
                </div>
              </div>
            ))}
          </div>
        ) : <div className="empty"><Icon name="search" /><div className="e-title">Aucun résultat</div></div>}
      </Card>

      {open && <LogDetail log={open} onClose={() => setOpen(null)} />}
    </div>
  );
}

function LogDetail({ log, onClose }) {
  return ReactDOM.createPortal((
    <>
      <div className="overlay" style={{ background: "rgba(20,24,33,.35)" }} onClick={onClose} />
      <div className="slideover" role="dialog" aria-modal="true">
        <div className="so-head">
          <div className={`log-status-ico ${log.status}`}><Icon name={log.status === "ok" ? "check" : "x"} size={16} /></div>
          <h3>{log.status === "ok" ? "E-mail livré" : "Échec d'envoi"}</h3>
          <span style={{ flex: 1 }} />
          <button className="modal-x" onClick={onClose} aria-label="Fermer"><Icon name="x" size={18} /></button>
        </div>
        <div className="so-body">
          <div className="so-meta">
            <div className="so-meta-row"><span className="k">À</span><span className="v">{log.to}</span></div>
            <div className="so-meta-row"><span className="k">De</span><span className="v">{log.from}</span></div>
            <div className="so-meta-row"><span className="k">Objet</span><span className="v">{log.subject}</span></div>
            <div className="so-meta-row"><span className="k">Date</span><span className="v">{log.date}</span></div>
            <div className="so-meta-row"><span className="k">Statut</span><span className="v"><Badge kind={log.status === "ok" ? "ok" : "err"} dot={false}>{log.status === "ok" ? "Livré" : "Échec"}</Badge></span></div>
            <div className="so-meta-row"><span className="k">DKIM</span><span className="v"><span className={`dkim-tag ${log.dkim ? "yes" : "no"}`}>{log.dkim ? "Signé ✓" : "Non signé"}</span></span></div>
          </div>
          {log.status === "err" && <div className="banner err" style={{ marginBottom: 16 }}><Icon name="alertc" className="b-ico" /><div>{log.error}</div></div>}
          <p className="section-label">Contenu du message</p>
          {log.body
            ? <div className="email-frame" dangerouslySetInnerHTML={{ __html: log.body }} />
            : <div className="email-frame muted-note" style={{ fontStyle: "italic" }}>Contenu non conservé (la conservation du corps des e-mails est désactivée).</div>}
        </div>
      </div>
    </>
  ), document.body);
}

function AdvancedScreen({ config, setConfig }) {
  const set = (k, v) => setConfig(c => ({ ...c, [k]: v }));
  return (
    <div>
      <Card title="Mode débogage" icon="bug">
        <ToggleRow id="a-debug" title="Activer la journalisation de débogage" tip="Enregistre la communication SMTP détaillée dans le journal PHP. À désactiver en production."
          desc="À utiliser uniquement pour le dépannage." checked={config.debugMode} onChange={v => set("debugMode", v)} />
      </Card>

      <Card title="Sécurité du chiffrement" icon="lock">
        <table className="summary">
          <tbody>
            <tr><th>OpenSSL</th><td><Badge kind="ok" dot={false}><Icon name="check" size={13} />Disponible</Badge></td></tr>
            <tr><th>Algorithme</th><td><span className="mono">AES-256-CBC</span></td></tr>
            <tr><th>Emplacement de la clé</th><td><Badge kind="ok" dot={false}><Icon name="check" size={13} />wp-config.php (sécurisé)</Badge></td></tr>
          </tbody>
        </table>
      </Card>

      <Card title="Désinstallation" icon="trash">
        <ToggleRow id="a-del" title="Supprimer toutes les données à la désinstallation" tip="Supprime définitivement réglages, logs et tables. Irréversible."
          checked={config.deleteOnUninstall} onChange={v => set("deleteOnUninstall", v)} />
        {config.deleteOnUninstall && <div className="banner err" style={{ marginTop: 14, marginBottom: 0 }}><Icon name="alert" className="b-ico" /><div><strong>Attention :</strong> tous les logs, clés DKIM et réglages seront supprimés définitivement lors de la suppression de l'extension.</div></div>}
      </Card>

      <div style={{ display: "flex", justifyContent: "flex-end", marginTop: 4 }}>
        <Button variant="primary" size="lg" icon="check">Enregistrer les réglages avancés</Button>
      </div>
    </div>
  );
}

Object.assign(window, { DkimScreen, LogsScreen, AdvancedScreen });
