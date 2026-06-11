# Handoff — Refonte de l'interface « Simple SMTP & DKIM »

> Paquet de transfert pour développement (Claude Code).
> Extension WordPress de **Astral Internet** — refonte complète de l'écran d'administration
> *Réglages → SMTP & DKIM*.

---

## 1. Aperçu

Cette refonte modernise l'interface d'administration de l'extension **Simple SMTP & DKIM**
tout en restant intégrée à l'admin WordPress. Elle couvre 5 onglets (Tableau de bord, Mailer,
DKIM, Journaux, Avancé), un **assistant de configuration guidé** (SMTP et OAuth2), et un
**diagnostic de livraison animé étape par étape**.

Objectifs prioritaires du client : **configuration, test et journaux** doivent être très
intuitifs et faciles à utiliser.

---

## 2. À propos des fichiers de ce paquet

Les fichiers du dossier `code/` sont des **références de design réalisées en HTML/CSS/React (JSX
via Babel)**. Ce sont des **prototypes** qui montrent l'apparence et le comportement voulus —
**pas du code de production à copier tel quel**.

La tâche consiste à **recréer ces designs dans l'environnement réel de l'extension** :
des **templates PHP** (`includes/admin/tab-*.php`), une feuille de style
`assets/css/admin-style.css` et du JavaScript jQuery `assets/js/admin-script.js`, en suivant
les conventions WordPress (fonctions `get_option`, `esc_html`, `wp_nonce_field`, AJAX
`admin-ajax.php`, etc.) déjà présentes dans le plugin.

> Le prototype React n'est qu'un support visuel. La logique métier (PHPMailer, OpenSSL/DKIM,
> chiffrement AES-256-CBC, table de logs) existe déjà côté PHP et ne doit **pas** être réécrite.

---

## 3. Fidélité

**Haute-fidélité (hifi).** Couleurs, typographie, espacements, rayons et interactions sont
définitifs. Reproduire l'UI au pixel près en utilisant les valeurs exactes ci-dessous.
La densité par défaut retenue est **compact**, l'accent par défaut est le **vert teal `#0d9488`**.

---

## 4. Jetons de design (design tokens)

### Couleurs — Accent (teal, choix par défaut du client)
| Rôle | Hex |
|---|---|
| `--accent` | `#0d9488` |
| `--accent-600` (hover) | `#0c8278` |
| `--accent-700` (actif/dégradé) | `#0a6b63` |
| `--accent-soft` (fonds, onglet actif) | `#e3f5f2` |
| `--accent-softer` (fond de carte test) | `#f1fbf9` |

> Variante alternative livrée (indigo) : `#4263eb / #3b51d6 / #2f40b3 / #eef1fe / #f6f7fe`.
> L'interface est « thémable » : ces 5 valeurs sont les seules à changer pour reskinner.

### Couleurs — Sémantique
| Rôle | Texte | Fond doux | Bordure |
|---|---|---|---|
| Succès (livré) | `#136b3a` (icône `#1f9d57`) | `#e7f6ec` | `#c4e8d1` |
| Avertissement | `#8a5a06` (icône `#c9810c`) | `#fdf3e0` | `#f1dcae` |
| Erreur (échec) | `#9e1f1d` (icône `#df3a36`) | `#fdecec` | `#f3c9c9` |
| Info | `#1a548f` (icône `#2a7de1`) | `#eaf3fd` | `#c5dffa` |

### Couleurs — Neutres
| Rôle | Hex |
|---|---|
| Fond app | `#eef0f3` |
| Surface (carte) | `#ffffff` |
| Surface 2 (fonds doux, champs) | `#f7f8fa` |
| Ligne / bordure | `#e5e8ee` |
| Ligne 2 (séparateurs internes) | `#eef0f4` |
| Texte principal | `#1b1f27` |
| Texte secondaire | `#565e6c` |
| Texte atténué | `#8a929e` |

### Chrome WordPress (cadre)
| Rôle | Hex |
|---|---|
| Barre admin / menu | `#1d2327` |
| Sous-menu | `#2c3338` |
| Élément de menu actif | `#2271b1` (bleu WP) |

### Typographie
- **Police UI** : `Figtree` (Google Fonts), poids 400/500/600/700/800.
- **Police mono** (hôtes, ports, DNS, code) : `JetBrains Mono`, 400/500.
- Échelle (px) : H1 page **21/700** · titre de carte **15.5/700** · corps **13–13.5/500–600**
  · libellés de champ **13/600** · descriptions **12/500** · micro-libellés **11–11.5/700 majuscules,
  letter-spacing .05em**. Titres en `letter-spacing: -0.01em à -0.02em`.

### Forme, ombres, espacement
| Jeton | Compact (défaut) | Équilibré | Aéré |
|---|---|---|---|
| `--pad` (padding carte) | 16px | 22px | 30px |
| `--gap` (entre cartes) | 13px | 18px | 26px |
| `--radius` (carte) | 10px | 13px | 16px |
| `--radius-sm` (champs/boutons) | 9px | 9px | 9px |

- Ombres : `--shadow-sm: 0 1px 2px rgba(20,28,46,.06)` ·
  `--shadow: 0 1px 2px rgba(20,28,46,.05), 0 6px 20px -8px rgba(20,28,46,.14)` ·
  `--shadow-lg: 0 12px 40px -12px rgba(20,28,46,.28)`.
- Conteneur principal : `max-width: 1080px`, centré.
- Transitions : `.14s` (couleurs/fonds), `.18–.2s` (toggles), `.5–.6s` (anneau de progression).

---

## 5. Écrans / vues

Captures dans `screens/` (numérotées dans l'ordre logique). Le cadre WordPress (barre admin
sombre + menu latéral) est un **contexte de maquette** : dans l'extension réelle, seul le contenu
de la zone `.wrap` est à recréer.

### 01 — Tableau de bord (`tab-dashboard.php`)
- **But** : vue d'ensemble + progression de configuration.
- **Layout** : bannière de statut → carte « Progression » (anneau circulaire + checklist 4 étapes
  + bouton « Assistant guidé ») → carte « Activité d'envoi » (grille de 5 stats) →
  grille 2 colonnes (« Configuration actuelle » = table résumé · « Échecs récents » = liste).
- **Anneau de progression** : SVG, rayon 28, trait 6px, piste `--line`, arc `--accent`,
  pourcentage centré (16/800).
- **Checklist** : pastille ronde 24px (verte ✓ si fait / pointillée si à faire), titre barré
  `--ink-3` si fait, bouton « Configurer » (ghost, sm) si à faire.

### 02 — Assistant de configuration SMTP (`screens/02-wizard-setup.png`)
- **But** : première configuration guidée. Modal centré, largeur 680px.
- **Étapes** : `Fournisseur → Identifiants → Test → Terminé`. Indicateur à pastilles numérotées
  reliées par des barres (`done` = vert, `active` = teal + halo `--accent-soft`).
- **Étape 1** : grille 2×3 de fournisseurs (SMTP perso, Gmail, Microsoft 365, SendGrid, Mailgun,
  Amazon SES). Chaque carte : pastille logo colorée 38px + nom + hôte mono.
- **Étape 3** : gros bouton « Lancer le diagnostic » qui déroule la même liste animée que le
  diagnostic complet (voir 07). Le bouton « Continuer » reste désactivé tant que le test n'est
  pas réussi.

### 03 — Mailer · SMTP (`tab-mailer.php` + `tab-mailer-smtp.php`)
- **Sous-onglets** en pilules : **SMTP** (actif, fond `--ink` foncé) · **OAuth2** (badge « Nouveau »).
- **Bannière** d'état du mailer (ok / avertissement « un autre mailer est actif » / info).
- **Carte Activation** : toggle « Activer le mailer SMTP ». Un seul mailer actif à la fois.
- **Grille 2 col.** : « Serveur » (hôte, chiffrement, port — le port s'auto-ajuste : TLS→587,
  SSL→465, Aucun→25) · « Authentification » (toggle + utilisateur + mot de passe, mention
  « Chiffré en AES-256-CBC » avec cadenas).
- **03b — Zone de test** (`screens/03b-mailer-test.png`) : carte en **dégradé teal doux**
  (`linear-gradient(160deg, --accent-softer, --surface)`), champ destinataire + gros bouton
  **« Lancer le diagnostic complet »**. C'est l'élément central demandé par le client.

### 04 — Mailer · OAuth2 (`tab-mailer-oauth.php`)
- **But** : configuration OAuth2 (Microsoft 365 / Google), méthode moderne sans mot de passe.
- **Carte « Connexion OAuth2 »** (dégradé teal) avec bouton **« Se connecter avec Microsoft »**
  → ouvre l'assistant OAuth (voir 05). Si connecté : badge vert « Connecté — jeton actif ».
- **Champs conditionnels** (afficher/masquer selon les choix) :
  - Fournisseur (Microsoft / Google) + adresse d'enveloppe.
  - Type d'octroi : `authorization_code` / `client_credentials`.
  - Type d'identifiant : `Secret client` / `Certificat X.509` (cartes radio).
  - Identifiants : ID client, secret **ou** clé/empreinte de certificat, **jeton de
    rafraîchissement** (uniquement si `authorization_code`, rempli par l'assistant).
  - Spécifique : Tenant ID (Microsoft) · Domaine hébergé (Google).
  - URI de redirection à copier + guide repliable par fournisseur.

### 05 — Assistant OAuth2 / écran de consentement (`screens/05-oauth-consent.png`)
- **But** : reproduire le **flux OAuth2 réel** sans copier-coller manuel de jeton.
- **Étapes** : `Fournisseur → Application → Autorisation → Terminé`.
- **Étape Autorisation** : bouton « Autoriser l'accès » → état « Redirection… » (spinner) →
  **carte de consentement** imitant le fournisseur (en-tête à la couleur de la marque, ex.
  bleu Microsoft `#0078d4`, « Mon Site souhaite accéder à votre compte », liste de permissions
  cochées, boutons Annuler / Accepter) → « Échange du code… » → jeton obtenu → écran de succès.

### 06 — DKIM (`tab-dkim.php`)
- **But** : signature DKIM + publication DNS + validation.
- **Carte « Signature DKIM »** : toggle + domaine + sélecteur (champs révélés à l'activation).
- **Carte « Générer les clés DKIM »** : bouton primaire → révèle un **bloc DNS** (Nom / Type
  `TXT` / Valeur) avec boutons **Copier** par champ.
- **Carte « Valider la configuration »** : bouton + badge « DNS vérifié » au succès.
- **`<details>` « Configuration manuelle / avancée »** : stockage (BD chiffrée / fichier) +
  encadré OpenSSL.

### 07 — Diagnostic de livraison (`screens/07-diagnostic-test.png`)
- **But** : le test « un clic » demandé. Modal 540px.
- **7 étapes animées séquentiellement** : Résolution DNS → Connexion → TLS → Authentification →
  SPF → Signature DKIM → Envoi de l'e-mail de test. Chaque ligne : icône d'état (idle gris /
  `running` = spinner teal / `ok` = pastille verte ✓) + titre + note technique + badge OK.
- **Résumé** en haut au terme (vert succès / rouge échec) + lien repliable
  « Afficher le journal de débogage SMTP » (bloc `<pre>` sombre `#161a20`).
- Côté PHP : brancher sur les actions AJAX existantes
  (`simple_smtp_dkim_test_connection`, `..._send_test_email`).

### 08 — Journaux d'e-mails (`tab-logs.php`)
- **But** : suivi des envois, vue aérée.
- **Réglages** : toggles « Activer la journalisation » / « Conserver le contenu » + champ
  rétention (jours).
- **Grille de 5 stats** (Total / Livrés / Échecs / Taux / Signés DKIM).
- **Barre d'outils** : **filtres pilules** (Tous / Livrés / Échecs avec compteurs) +
  champ de recherche + Export CSV + Tout effacer.
- **Liste** (pas un tableau dense) : chaque ligne = icône d'état carrée 34px (verte/rouge) +
  destinataire (gras) + objet (ou message d'erreur en rouge) + date + **badge DKIM**
  (« DKIM ✓ » teal / « non signé » gris). Clic → panneau latéral (voir 09).

### 09 — Détail d'un e-mail (`screens/09-log-detail.png`)
- **Panneau latéral** (slide-over) 460px depuis la droite : en-tête avec état, table méta
  (À / De / Objet / Date / Statut / DKIM), bannière d'erreur si échec, puis aperçu du contenu
  HTML de l'e-mail dans un cadre. (Le prototype l'affiche en carte centrée pour la capture.)

### 10 — Avancé (`tab-advanced.php`)
- Carte « Mode débogage » (toggle) · « Sécurité du chiffrement » (table : OpenSSL Disponible,
  Algorithme `AES-256-CBC`, Emplacement de clé `wp-config.php`) · « Désinstallation » (toggle +
  bannière rouge d'avertissement quand activé).

---

## 6. Composants UI réutilisables

| Composant | Spécifications |
|---|---|
| **Carte** | fond blanc, bordure `--line`, rayon `--radius`, `--shadow`, padding `--pad`. En-tête : icône teal 18px + titre 15.5/700 (+ « hint » atténué à droite, + action optionnelle poussée à droite). |
| **Toggle** | piste 44×25px, pastille 19px ; OFF `#cdd2da`, ON `--accent` ; transition .18s ; halo focus `--accent-soft`. |
| **Bouton primaire** | fond `--accent`, texte blanc, rayon 9px, `0 4px 12px -4px --accent` ; hover `--accent-600` ; tailles sm/normal/lg. |
| **Bouton ghost** | fond blanc, bordure `--line`, texte `--ink` ; hover fond `--surface-2`. |
| **Bouton danger** | texte/bordure rouge ; hover fond rouge plein, texte blanc. |
| **Badge** | pilule 11.5/700, variantes ok/err/warn/muted/accent (+ point coloré 6px optionnel). |
| **Bannière** | icône + texte, bordure gauche 1px de la teinte, fond doux, rayon 9px. |
| **Champ** | input 10×12px, bordure `--line`, rayon 9px ; focus bordure `--accent` + halo 3px `--accent-soft`. Classe `.mono` pour valeurs techniques. |
| **Cartes radio** | bordure 1.5px, sélection = bordure `--accent` + fond `--accent-softer` + pastille pleine. |
| **Pilules de filtre** | conteneur `--surface-2` rayon 10px ; pilule active = surface blanche + `--shadow-sm`. |
| **Stat** | bloc `--surface-2`, nombre 27/800 (teinté selon ok/err/accent), libellé majuscule 11.5. |
| **Bloc DNS** | tableau encadré ; clé (col. grise majuscule) / valeur mono + bouton Copier (devient vert « Copié » 1,6 s). |
| **Modal** | overlay `rgba(20,24,33,.5)`, boîte blanche rayon 18px, `--shadow-lg`, anim `pop .22s`. |
| **Panneau latéral** | 460px, depuis la droite, anim `slidein .26s`. |
| **Indicateur d'étapes** | pastilles numérotées reliées (done vert / active teal+halo). |
| **Toast** | pilule sombre centrée en bas, ✓ vert, anim `pop .2s`, auto-disparition 2,2 s. |
| **Icônes** | jeu type *Lucide* (trait 2px, 24×24, `currentColor`). Voir la map `ICON_PATHS` dans `code/components.jsx`. Côté WordPress, équivalents **Dashicons** acceptables. |

---

## 7. Interactions & comportement

- **Onglets** : navigation instantanée (déjà via `?tab=` côté PHP). L'onglet Journaux affiche
  un compteur rouge du nombre d'échecs.
- **Diagnostic** : les 7 étapes passent `idle → running (spinner) → ok` en cascade
  (~650–950 ms/étape) ; résumé final ; journal de débogage repliable.
- **Assistants** : navigation Précédent/Continuer ; bouton final désactivé tant que l'étape
  bloquante (test réussi / champs requis) n'est pas satisfaite.
- **OAuth** : `Autoriser → redirection (spinner ~1,3 s) → consentement → échange (~1,3 s) →
  jeton obtenu → succès`. À l'issue : le sous-onglet OAuth2 passe « Actif » et le badge
  « Connecté » s'affiche.
- **Champs conditionnels OAuth** : reproduire la logique de `toggleOAuthFields()` déjà présente
  dans `admin-script.js` (provider, grant, méthode d'auth).
- **Auto-ajustement du port** selon le chiffrement (TLS 587 / SSL 465 / Aucun 25).
- **Copier** : `navigator.clipboard`, feedback « Copié » 1,6 s.
- **Filtres/recherche logs** : filtrage client par statut + texte (destinataire/objet).
- **Réduction de mouvement** : prévoir `@media (prefers-reduced-motion)` pour neutraliser les
  animations d'entrée.

---

## 8. État (state)

Tout est déjà persté côté WordPress via `get_option()` / table de logs. Variables clés à relier
aux champs : `simple_smtp_dkim_enabled`, `..._mailer_type`, `..._host/port/secure/auth/username/
password`, `..._from_email/from_name/force_from`, `..._dkim_enabled/dkim_domain/dkim_selector/
dns_verified`, `..._logging_enabled/log_retention_days/log_email_body`, `..._debug_mode/
delete_on_uninstall`, et les options OAuth `..._oauth_*`. États UI transitoires (étape
d'assistant, phase OAuth, log ouvert, filtre/recherche) : locaux à la vue.

---

## 9. Accessibilité

- Toggles = vraies cases à cocher masquées + halo de focus visible.
- Modals : `role="dialog"`, `aria-modal`, fermeture Échap, piège de focus, restitution du focus.
- Régions de résultat en `aria-live`. Cibles tactiles ≥ 44px. Contrastes conformes AA.

---

## 10. Assets

- **Polices** : Figtree + JetBrains Mono (Google Fonts). Self-host recommandé en production.
- **Icônes** : tracées en SVG (style Lucide) — aucune image bitmap. Voir `ICON_PATHS`.
- **Aucune** image/logo externe ; pastilles de fournisseurs = lettre + couleur de marque.
- Captures de référence dans `screens/` (rendues avec l'accent teal `#0d9488`, densité compact).

> Note : certaines captures comportent de légers artefacts du moteur de capture (surbrillance
> d'onglet, fond de bouton primaire, chevauchement de texte sur 2 lignes) — l'interface réelle
> dans le navigateur est correcte. Se fier aux valeurs de ce README en cas de doute.

---

## 11. Fichiers (dossier `code/`)

| Fichier | Rôle |
|---|---|
| `index.html` | Point d'entrée (charge React + Babel + les scripts, polices, `styles.css`). |
| `styles.css` | **Système de design complet** (tokens, cartes, formulaires, modals, chrome WP). |
| `app.jsx` | Coquille : chrome WordPress, onglets, routage, panneau Tweaks, deep-links de capture. |
| `components.jsx` | Primitives partagées + jeu d'icônes (`ICON_PATHS`). |
| `mock-data.jsx` | Données factices (logs, stats, config, fournisseurs). |
| `screens-config.jsx` | Écrans Tableau de bord + Mailer. |
| `screens-logs.jsx` | Écrans DKIM + Journaux (+ panneau de détail) + Avancé. |
| `diagnostic.jsx` | Diagnostic de livraison animé. |
| `wizard.jsx` | Assistant de configuration SMTP. |
| `oauth.jsx` | Panneau OAuth2 + assistant de connexion OAuth2. |
| `tweaks-panel.jsx` | Panneau de réglages live (accent, densité) — outil de maquette, non destiné à l'extension. |

Pour exécuter le prototype : ouvrir `code/index.html` dans un navigateur (connexion requise pour
React/Babel via CDN). Le panneau **Tweaks** permet de basculer accent (teal/indigo/bleu WP/violet)
et densité (compact/équilibré/aéré).
