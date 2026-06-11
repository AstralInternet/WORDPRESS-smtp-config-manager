// app.jsx — WordPress chrome + plugin shell + routing
const { useState: useStateA, useEffect: useEffectA } = React;

const ACCENTS = {
  indigo:    ["#4263eb", "#3b51d6", "#2f40b3", "#eef1fe", "#f6f7fe"],
  teal:      ["#0d9488", "#0c8278", "#0a6b63", "#e3f5f2", "#f1fbf9"],
  wordpress: ["#2271b1", "#1d6097", "#185179", "#e9f1f8", "#f4f8fb"],
  violet:    ["#7a5ae0", "#6b4cd6", "#5739b8", "#f1edfd", "#f8f6fe"],
};

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "accent": ["#0d9488", "#0c8278", "#0a6b63", "#e3f5f2", "#f1fbf9"],
  "density": "compact"
}/*EDITMODE-END*/;

const WP_MENU = [
  { ico: "gauge", label: "Tableau de bord" },
  { ico: "list", label: "Articles" },
  { ico: "inbox", label: "Médias" },
  { ico: "mail", label: "Pages" },
  { ico: "user", label: "Comptes" },
];

const TABS = [
  { id: "dashboard", label: "Tableau de bord", ico: "gauge" },
  { id: "mailer", label: "Mailer", ico: "server" },
  { id: "dkim", label: "DKIM", ico: "shield" },
  { id: "logs", label: "Journaux", ico: "inbox" },
  { id: "advanced", label: "Avancé", ico: "settings" },
];

// Capture/demo deep-link: localStorage.__smtp_demo opens a given screen/overlay on load
// (used to grab pixel-accurate handoff screenshots; harmless when unset).
const DEMO = (() => { try { return localStorage.getItem("__smtp_demo") || ""; } catch (e) { return ""; } })();
const DEMO_TAB = ["mailer", "mailer-oauth", "oauthwiz", "oauthconsent", "diag"].includes(DEMO) ? "mailer"
  : (DEMO === "logs" || DEMO === "logdetail") ? "logs"
  : ["dkim", "advanced"].includes(DEMO) ? DEMO : "dashboard";

function App() {
  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);
  const [config, setConfig] = useStateA(SMTP_CONFIG_DEFAULT);
  const [tab, setTab] = useStateA(DEMO_TAB);
  const [wizard, setWizard] = useStateA(DEMO === "wizard");
  const [diag, setDiag] = useStateA(DEMO === "diag" ? SMTP_CONFIG_DEFAULT.fromEmail : null);
  const [toast, setToast] = useStateA(null);

  useEffectA(() => {
    if (!DEMO) return;
    document.body.classList.add("capmode");
    if (["diag", "wizard", "oauthwiz", "oauthconsent", "logdetail"].includes(DEMO)) document.body.classList.add("capmode-overlay");
  }, []);

  // apply tweaks → CSS vars
  useEffectA(() => {
    const a = Array.isArray(t.accent) ? t.accent : ACCENTS.indigo;
    const r = document.documentElement;
    r.style.setProperty("--accent", a[0]);
    r.style.setProperty("--accent-600", a[1]);
    r.style.setProperty("--accent-700", a[2]);
    r.style.setProperty("--accent-soft", a[3]);
    r.style.setProperty("--accent-softer", a[4]);
    r.setAttribute("data-density", t.density);
  }, [t.accent, t.density]);

  function fireToast(msg) { setToast(msg); setTimeout(() => setToast(null), 2200); }
  const errCount = SMTP_LOGS.filter(l => l.status === "err").length;

  return (
    <div className="wp">
      {/* admin bar */}
      <div className="wp-adminbar">
        <Icon name="settings" size={18} style={{ opacity: .8 }} />
        <span className="ab-item"><Icon name="globe" size={15} />Mon Site</span>
        <span className="ab-item"><Icon name="refresh" size={15} />0</span>
        <span className="ab-spacer" />
        <span className="ab-item">Bonjour, Admin</span>
        <span className="ab-avatar" />
      </div>

      <div className="wp-body">
        {/* left menu */}
        <nav className="wp-menu">
          {WP_MENU.map(m => (
            <div key={m.label} className="mi"><span className="ico"><Icon name={m.ico} size={16} /></span>{m.label}</div>
          ))}
          <div className="mi parent-open"><span className="ico"><Icon name="settings" size={16} /></span>Réglages</div>
          <div className="wp-submenu">
            <div className="si">Général</div>
            <div className="si">Écriture</div>
            <div className="si active">SMTP &amp; DKIM</div>
          </div>
        </nav>

        {/* content */}
        <main className="wp-content">
          <div className="app">
            <header className="app-head">
              <div className="brand-mark"><Icon name="send" size={24} /></div>
              <div className="app-titles">
                <h1>Simple SMTP &amp; DKIM</h1>
                <div className="sub">Livraison d'e-mails fiable, journalisation et signature DKIM</div>
              </div>
              <span className="spacer" />
              <span className={`master-pill ${config.enabled ? "on" : "off"}`}>
                <span className="dot" />{config.enabled ? "Envoi actif" : "Inactif"}
              </span>
            </header>

            <nav className="tabs">
              {TABS.map(tb => (
                <button key={tb.id} className={`tab ${tab === tb.id ? "active" : ""}`} onClick={() => setTab(tb.id)}>
                  <Icon name={tb.ico} size={16} className="tab-ico" />{tb.label}
                  {tb.id === "logs" && errCount > 0 && <span className="count">{errCount}</span>}
                </button>
              ))}
            </nav>

            {tab === "dashboard" && <DashboardScreen config={config} openWizard={() => setWizard(true)} openDiagnostic={r => setDiag(r)} go={setTab} />}
            {tab === "mailer" && <MailerScreen config={config} setConfig={setConfig} openDiagnostic={r => setDiag(r || config.fromEmail)}
              initialSub={["mailer-oauth", "oauthwiz", "oauthconsent"].includes(DEMO) ? "oauth" : "smtp"}
              initialOauthWiz={DEMO === "oauthwiz" || DEMO === "oauthconsent"}
              initialOauthConsent={DEMO === "oauthconsent"} />}
            {tab === "dkim" && <DkimScreen config={config} setConfig={setConfig} toast={fireToast} />}
            {tab === "logs" && <LogsScreen config={config} setConfig={setConfig} toast={fireToast} initialOpenLog={DEMO === "logdetail"} />}
            {tab === "advanced" && <AdvancedScreen config={config} setConfig={setConfig} />}
          </div>
        </main>
      </div>

      {wizard && <SetupWizard onClose={() => setWizard(false)} onComplete={() => { setConfig(c => ({ ...c, enabled: true, host: c.host || "smtp.monsite.com", lastTestOk: true })); fireToast("Configuration enregistrée"); }} />}
      {diag && <TestDiagnostic recipient={diag} onClose={() => setDiag(null)} />}
      <Toast msg={toast} />

      <TweaksPanel>
        <TweakSection label="Apparence" />
        <TweakColor label="Couleur d'accent" value={t.accent}
          options={[ACCENTS.indigo, ACCENTS.teal, ACCENTS.wordpress, ACCENTS.violet]}
          onChange={v => setTweak("accent", v)} />
        <TweakRadio label="Densité" value={t.density} options={["compact", "balanced", "airy"]}
          onChange={v => setTweak("density", v)} />
      </TweaksPanel>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById("root")).render(<App />);
