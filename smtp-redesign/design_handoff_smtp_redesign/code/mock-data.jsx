// mock-data.jsx — fake state for the prototype
const SMTP_LOGS = [
  { id: 1, to: "client@example.com", subject: "Votre commande #4821 est confirmée", status: "ok", dkim: true, date: "11 juin 2026, 14:32", from: "boutique@monsite.com", body: "Bonjour,<br><br>Merci pour votre commande. Votre colis sera expédié sous 48h.<br><br>L'équipe Boutique" },
  { id: 2, to: "marie.tremblay@gmail.com", subject: "Réinitialisation de votre mot de passe", status: "ok", dkim: true, date: "11 juin 2026, 13:58", from: "no-reply@monsite.com", body: "Cliquez sur le lien pour réinitialiser votre mot de passe. Ce lien expire dans 1 heure." },
  { id: 3, to: "facturation@grosclient.ca", subject: "Facture mensuelle — Mai 2026", status: "ok", dkim: true, date: "11 juin 2026, 11:20", from: "compta@monsite.com", body: "Veuillez trouver ci-joint votre facture." },
  { id: 4, to: "invalide@domaine-inexistant.xyz", subject: "Bienvenue sur la plateforme", status: "err", dkim: false, date: "11 juin 2026, 10:04", from: "no-reply@monsite.com", error: "SMTP error: 550 5.1.1 Recipient address rejected: User unknown", body: "" },
  { id: 5, to: "support@partenaire.com", subject: "Demande de contact — Formulaire", status: "ok", dkim: true, date: "10 juin 2026, 17:45", from: "contact@monsite.com", body: "Nouveau message reçu via le formulaire de contact." },
  { id: 6, to: "jean.dupuis@outlook.com", subject: "Confirmation d'inscription à l'infolettre", status: "ok", dkim: true, date: "10 juin 2026, 16:12", from: "infolettre@monsite.com", body: "Merci de vous être inscrit !" },
  { id: 7, to: "ancienne-adresse@vieuxdomaine.net", subject: "Votre abonnement arrive à échéance", status: "err", dkim: false, date: "10 juin 2026, 14:30", from: "no-reply@monsite.com", error: "Connection timed out after 30s — serveur SMTP injoignable", body: "" },
  { id: 8, to: "admin@monsite.com", subject: "Rapport quotidien d'activité", status: "ok", dkim: true, date: "10 juin 2026, 06:00", from: "system@monsite.com", body: "Résumé : 142 visiteurs, 8 commandes." },
  { id: 9, to: "luc.gagnon@videotron.ca", subject: "Votre reçu de paiement", status: "ok", dkim: true, date: "9 juin 2026, 22:18", from: "compta@monsite.com", body: "Paiement reçu : 89,99 $." },
  { id: 10, to: "newsletter@liste.com", subject: "Nouveautés de la semaine", status: "ok", dkim: true, date: "9 juin 2026, 09:00", from: "infolettre@monsite.com", body: "Découvrez nos nouveaux produits." },
  { id: 11, to: "test@spamtrap.org", subject: "Offre spéciale -50%", status: "err", dkim: true, date: "8 juin 2026, 15:22", from: "promo@monsite.com", error: "550 Message rejected as spam by recipient server", body: "" },
  { id: 12, to: "sophie.roy@hotmail.com", subject: "Votre commande a été expédiée", status: "ok", dkim: true, date: "8 juin 2026, 11:47", from: "boutique@monsite.com", body: "Numéro de suivi : CA123456789." },
];

const SMTP_STATS = { total: 1284, success: 1247, failed: 37, rate: 97, dkim: 1198 };

const SMTP_CONFIG_DEFAULT = {
  // master + mailer
  enabled: true,
  mailerType: "smtp",
  host: "smtp.monsite.com",
  port: 587,
  secure: "tls",
  auth: true,
  username: "boutique@monsite.com",
  hasPassword: true,
  fromEmail: "boutique@monsite.com",
  fromName: "Boutique Mon Site",
  forceFrom: true,
  // dkim
  dkimEnabled: true,
  dkimDomain: "monsite.com",
  dkimSelector: "default",
  dnsVerified: true,
  hasKeys: true,
  storageMethod: "database",
  // logs
  loggingEnabled: true,
  retentionDays: 30,
  logBody: false,
  // advanced
  debugMode: false,
  deleteOnUninstall: false,
  lastTestOk: true,
};

const PROVIDERS = [
  { id: "custom",    name: "SMTP personnalisé", host: "smtp.exemple.com",   color: "#5c6470", abbr: "@" },
  { id: "gmail",     name: "Gmail / Workspace",  host: "smtp.gmail.com",     color: "#ea4335", abbr: "G" },
  { id: "microsoft", name: "Microsoft 365",      host: "smtp.office365.com", color: "#0078d4", abbr: "M" },
  { id: "sendgrid",  name: "SendGrid",           host: "smtp.sendgrid.net",  color: "#1a82e2", abbr: "S" },
  { id: "mailgun",   name: "Mailgun",            host: "smtp.mailgun.org",   color: "#c02e2e", abbr: "M" },
  { id: "ses",       name: "Amazon SES",         host: "email-smtp.aws.com", color: "#ff9900", abbr: "A" },
];

Object.assign(window, { SMTP_LOGS, SMTP_STATS, SMTP_CONFIG_DEFAULT, PROVIDERS });
