=== Simple SMTP & DKIM ===
Contributors: @astralinternet, @neutrall, @sleyeur
Tags: smtp, email, dkim, mail, mailer
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free, comprehensive SMTP solution with email logging, DKIM signing, SPF validation, and encrypted credential storage for WordPress.

== Description ==

Simple SMTP & DKIM is a **completely free** WordPress plugin developed to provide a robust email delivery solution without the limitations of premium alternatives. Unlike other SMTP plugins that charge for essential features like email logging or DKIM support, this plugin offers everything you need at no cost.

= Why We Built This Plugin =

We created Simple SMTP & DKIM because we believe essential email functionality should be accessible to everyone. Many existing SMTP plugins lock critical features like email logs, DKIM signing, and advanced testing behind paywalls. This plugin provides all these features for free, with enterprise-grade security and performance.

= Key Features =

**SMTP Configuration**
* Easy-to-use interface for configuring any SMTP server
* Support for TLS/SSL encryption
* SMTP authentication with encrypted password storage (AES-256-CBC)
* Connection testing before going live
* Debug mode with detailed SMTP communication logs
* Force From email/name to override WordPress defaults

**DKIM Email Signing**
* One-click DKIM key generation (2048-bit RSA)
* Automatic DNS record generation for easy setup
* Support for encrypted private keys with passphrases
* Two storage options: encrypted database or secure file storage
* DKIM validation with DNS verification
* View saved public keys anytime

**SPF Validation**
* Automatic SPF record checking during connection tests
* Verify your SMTP server is authorized in your domain's SPF record
* Helps prevent emails from being marked as spam

**Email Logging & Analytics**
* Track all sent emails with success/failure status
* Search and filter logs by recipient, subject, or status
* View statistics (success rate, DKIM usage, total emails)
* Auto-purge old logs (configurable retention period)
* Export logs to CSV
* DKIM signature status tracking
* Detailed error messages for failed emails

**Security Features**
* AES-256-CBC encryption for passwords and DKIM keys
* Protected upload directory with .htaccess security
* Unique encryption key per installation
* Administrator-only access (manage_options capability)
* Input sanitization and XSS protection
* Nonce verification on all forms and AJAX requests
* Secure file deletion with data overwriting

**Testing & Debugging**
* Test SMTP connection without sending emails
* Send professional HTML test emails with full diagnostics
* DKIM key validation and format checking
* DNS record verification for DKIM
* SPF authorization checking
* Detailed debug output with toggle visibility
* Configuration summary on test page

**Performance Optimizations**
* All options stored with autoload='no' to save memory
* Efficient database queries
* WordPress object cache integration
* Minimal impact on page load times

**Translation Ready**
* Fully translatable with included POT file
* All strings use WordPress translation functions
* Email templates support multiple languages
* Translation guide included

= Perfect For =

* Sites requiring reliable email delivery
* WooCommerce stores needing transactional emails
* Membership sites with email notifications
* Contact forms (Contact Form 7, Gravity Forms, etc.)
* Password reset and user registration emails
* Newsletter plugins
* Any WordPress site sending emails

= Supported SMTP Providers =

Works with all SMTP services.

= Technical Specifications =

* **Encryption**: AES-256-CBC for sensitive data
* **DKIM**: 2048-bit RSA key generation
* **Database**: Custom table for email logs with auto-purge
* **Compatibility**: WordPress 5.0+, PHP 7.4+
* **No External Dependencies**: All processing done locally
* **Privacy**: No data sent to external servers

= What Makes This Different =

Unlike premium SMTP plugins that charge monthly fees:
* ✅ **100% Free** - All features included at no cost
* ✅ **Email Logging** - Track every email (often a premium feature elsewhere)
* ✅ **DKIM Support** - Full signing capability (typically paid)
* ✅ **No Limits** - Unlimited emails, unlimited logging
* ✅ **Open Source** - GPL licensed, fully transparent
* ✅ **No Upsells** - No premium versions or locked features
* ✅ **Active Development** - Regular updates and improvements

= Developer Friendly =

* Clean, well-documented code
* WordPress coding standards
* Action and filter hooks for customization
* Programmatic access to logging and configuration
* Extensible architecture

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to **Plugins → Add New**
3. Search for "Simple SMTP & DKIM"
4. Click **Install Now**
5. Click **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the downloaded file and click **Install Now**
4. Click **Activate Plugin**

= After Installation =

1. Go to **Settings → Simple SMTP & DKIM**
2. Configure your SMTP settings
3. (Optional) Set up DKIM signing
4. Test your configuration
5. Enable SMTP and start sending!

== Frequently Asked Questions ==

= How do I set up SMTP authentication? =

1. Go to **Settings → Simple SMTP & DKIM**
2. Enable the **Enable SMTP** toggle
3. Enter your SMTP server details:
   * **SMTP Host**: Your SMTP server address (e.g., smtp.gmail.com)
   * **SMTP Port**: Usually 587 for TLS or 465 for SSL
   * **Encryption**: Select TLS (recommended) or SSL
4. Enable **Use SMTP Authentication**
5. Enter your **Username** (usually your full email address)
6. Enter your **Password** (will be encrypted automatically)
7. Set your **From Email** and **From Name**
8. Click **Test Connection** to verify
9. Click **Save SMTP Settings**

**For Gmail/Google Workspace:**
* You must use an App Password, not your regular Gmail password
* Enable 2-factor authentication first
* Generate an App Password in your Google Account settings
* Use smtp.gmail.com on port 587 with TLS

= How do I activate debug mode and where are the logs? =

**To Enable Debug Mode:**

1. Go to **Settings → Simple SMTP & DKIM**
2. Scroll to the **Advanced Settings** section
3. Enable the **Debug Mode** toggle
4. Click **Save SMTP Settings**

**Where to Find Logs:**

Debug logs are written to your WordPress debug.log file:

1. Enable WordPress debugging in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. View the log file at: `/wp-content/debug.log`

3. You can view it via:
   * FTP/SFTP client
   * File manager in cPanel
   * SSH: `tail -f /path/to/wp-content/debug.log`
   * WP-CLI: `wp eval 'echo file_get_contents(WP_CONTENT_DIR . "/debug.log");'`

**Email Logs (Always Available):**

The plugin also has built-in email logging:
1. Go to **Settings → Simple SMTP & DKIM → Email Logs** tab
2. Enable logging
3. View all sent emails with success/failure status
4. Search and filter by recipient or status
5. Export to CSV if needed

= How do I add a DKIM key? =

**Option 1: Auto-Generate (Easiest)**

1. Go to **Settings → Simple SMTP & DKIM → DKIM Settings** tab
2. Enable the **Enable DKIM** toggle
3. Enter your **Domain** (e.g., example.com)
4. Enter a **Selector** (e.g., "default" or "mail")
5. Click **Generate New DKIM Keys**
6. Copy the DNS TXT record displayed
7. Add the record to your DNS provider:
   * **Type**: TXT
   * **Name**: [shown in plugin]
   * **Value**: [shown in plugin]
8. Wait 5-10 minutes for DNS propagation
9. Click **Validate DKIM** to verify
10. Click **Save DKIM Settings**

**Option 2: Use Existing Keys**

1. Go to **DKIM Settings** tab
2. Enable DKIM
3. Enter Domain and Selector
4. Choose **Storage Method**:
   * **Database (Encrypted)**: Upload your private key file
   * **File Storage**: Enter path or upload file
5. Enter passphrase if your key is encrypted
6. Click **Validate DKIM**
7. Click **Save DKIM Settings**

**To View Your Saved DKIM Key:**

If you need to see your DNS record again:
1. Go to DKIM Settings tab
2. Click **View Saved DKIM Public Key**
3. Copy the DNS record
4. Click **Close**

= Do I need DKIM? =

DKIM is **optional but highly recommended**. It:
* Significantly improves email deliverability
* Helps prevent emails from being marked as spam
* Provides authentication that emails are from your domain
* Is increasingly required by major email providers (Gmail, Yahoo, etc.)

= Will this work with my SMTP provider? =

Yes! The plugin works with **any SMTP server** that supports standard SMTP protocols. This includes:
* Gmail, Outlook, Yahoo
* SendGrid, Mailgun, Amazon SES
* Custom SMTP servers
* Dedicated email services
* Any server supporting SMTP with TLS/SSL

= Can I see what emails are being sent? =

Yes! Enable **Email Logging** in the Email Logs tab. You'll see:
* Recipient email address
* Subject line
* Timestamp
* Success or failure status
* Error messages (if failed)
* DKIM signature status

Logs can be searched, filtered, and exported to CSV.

= How are my passwords stored? =

All passwords and DKIM keys are encrypted using **AES-256-CBC** encryption before being stored in the WordPress database. Each installation has a unique encryption key. Your credentials are never stored in plain text.

= Does this plugin send data to external servers? =

**No.** This plugin:
* Does NOT send any data to external servers
* Does NOT track users
* Does NOT use cookies
* Does NOT collect analytics
* Does NOT phone home

All data stays on your server. The only external communication is with your SMTP server when sending emails.

= Can I use this with WooCommerce? =

Yes! The plugin hooks into WordPress's core `wp_mail()` function, so all emails from WooCommerce (and any other plugin) will automatically use your SMTP settings.

= What happens if I deactivate the plugin? =

WordPress will revert to using the default PHP `mail()` function. Your settings are preserved and will work again when you reactivate the plugin.

= Is there a limit on how many emails I can send? =

The plugin itself has **no limits**. However, your SMTP provider may have sending limits. Check with your provider for their specific limits.

= Can I translate this plugin? =

Yes! The plugin is fully translation-ready. You can:
* Use Poedit with the included POT file
* Use the Loco Translate plugin
* Contribute translations to WordPress.org

Translation guide available in the `languages/` folder.

== Screenshots ==

1. SMTP Settings - Easy configuration with connection testing and SPF validation
2. DKIM Settings - One-click key generation with DNS record display
3. Email Logs - Track all emails with search, filter, and export capabilities
4. Test Email - Send professional HTML test emails with full diagnostics
5. Generated DKIM Keys - Copy-ready DNS records for easy setup

== Changelog ==

= 1.0.0 =
* Initial release
* Complete SMTP configuration with TLS/SSL support
* DKIM signing with automatic key generation
* SPF validation during connection tests
* Email logging with auto-purge functionality
* Professional HTML test emails with diagnostics
* AES-256-CBC encryption for credentials
* WordPress 5.0+ compatibility
* PHP 7.4+ compatibility
* Translation ready with included POT file
* Comprehensive testing tools
* No autoload for options (performance optimization)

== Upgrade Notice ==

= 1.0.0 =
Initial release of Simple SMTP & DKIM - A free, comprehensive SMTP solution with email logging, DKIM signing, and SPF validation.

== Privacy Policy ==

This plugin:
* Does NOT send any data to external servers
* Does NOT track users or collect analytics
* Does NOT use cookies
* Does NOT phone home

Data stored locally:
* SMTP credentials (encrypted with AES-256-CBC)
* Email logs (optional, can be disabled)
* DKIM keys (encrypted in database or stored as files)
* Plugin settings

All data remains on your WordPress installation.

== Credits ==

Developed with ❤️ by [Astral Internet](https://astralinternet.com/) for the WordPress community.

== Support ==

For support, please:
1. Check the FAQ section above
2. Review the documentation in the plugin
3. Enable Debug Mode and check error logs
4. Contact [Astral Internet](https://astralinternet.com/)

== Contributing ==

This is an open-source project. Contributions are welcome!
* GitHub: https://github.com/astralinternet/simple-smtp-dkim
* Report bugs or request features via GitHub Issues
* Submit pull requests for improvements


== Description ==

Simple SMTP & DKIM is a powerful yet easy-to-use plugin that allows you to configure WordPress to send emails through any SMTP server. It includes advanced features like DKIM signing, SPF validation, email logging, and comprehensive testing tools.

= Key Features =

* **Easy SMTP Configuration** - Configure any SMTP server with a user-friendly interface
* **DKIM Email Signing** - Automatically sign emails with DKIM to improve deliverability
* **Auto-Generate DKIM Keys** - Generate 2048-bit RSA keys with one click
* **SPF Validation** - Check if your SMTP server is authorized in your domain's SPF record
* **Email Logging** - Track all sent emails with success/failure status
* **Test Tools** - Test SMTP connection and send test emails before going live
* **Encrypted Storage** - All passwords and keys stored encrypted using AES-256-CBC
* **HTML Test Emails** - Beautiful, professional test emails with full diagnostic information
* **Auto-Purge Logs** - Automatically delete old logs (configurable retention period)
* **Translation Ready** - Fully translatable with included POT file

= Perfect For =

* WordPress sites that need reliable email delivery
* Sites using third-party SMTP services (Gmail, SendGrid, Mailgun, etc.)
* Developers who need detailed email debugging
* Sites requiring DKIM signatures for email authentication
* Multi-lingual sites (fully translatable)

= SMTP Providers Supported =

Works with all SMTP providers including:
* Gmail / Google Workspace
* Microsoft 365 / Outlook
* SendGrid
* Mailgun
* Amazon SES
* Postmark
* SparkPost
* Custom SMTP servers

= Security Features =

* AES-256-CBC encryption for sensitive data
* Protected upload directory for DKIM keys
* Nonce verification on all forms
* Administrator-only access
* Input sanitization and XSS protection

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Simple SMTP & DKIM"
4. Click "Install Now"
5. Click "Activate Plugin"

= Manual Installation =

1. Download the plugin ZIP file
2. Extract the files
3. Upload the `simple-smtp-dkim` folder to `/wp-content/plugins/`
4. Activate the plugin through the WordPress 'Plugins' menu

= After Installation =

1. Go to **Settings → Simple SMTP & DKIM**
2. Configure your SMTP settings
3. (Optional) Set up DKIM signing
4. Test your configuration
5. Enable SMTP to start sending emails

== Frequently Asked Questions ==

= Do I need DKIM? =

DKIM is optional but highly recommended. It significantly improves email deliverability and helps prevent your emails from being marked as spam.

= Will this work with my current SMTP provider? =

Yes! The plugin works with any SMTP server that supports standard SMTP protocols (TLS/SSL authentication).

= Can I use this with Gmail? =

Yes, but you'll need to:
1. Enable 2-factor authentication on your Google account
2. Create an App Password (not your regular Gmail password)
3. Use `smtp.gmail.com` on port `587` with TLS encryption

= How do I generate DKIM keys? =

1. Go to the DKIM Settings tab
2. Enter your domain and selector
3. Click "Generate New DKIM Keys"
4. Copy the DNS record to your DNS provider
5. Save your DKIM settings

= Where are my passwords stored? =

All passwords and DKIM keys are encrypted using AES-256-CBC before being stored in the WordPress database.

= Can I see what emails are being sent? =

Yes! Enable email logging in the Email Logs tab. You can see:
* Who the email was sent to
* Subject line
* Success/failure status
* DKIM signing status
* Error messages (if any)

= How do I test if it's working? =

1. Go to the Test Email tab
2. Enter your email address
3. Click "Send Test Email"
4. Check your inbox for a professional HTML test email with diagnostic details

= What if emails aren't sending? =

1. Click "Test Connection" to verify SMTP credentials
2. Check the Email Logs for error messages
3. Enable Debug Mode to see detailed SMTP communication
4. Verify your SPF record includes your SMTP server
5. Check that DKIM is properly configured in DNS

= Can I translate this plugin? =

Yes! The plugin is fully translation-ready. Use Poedit or Loco Translate with the included POT file in the `languages/` folder.

== Screenshots ==

1. SMTP Settings - Easy configuration interface with test button
2. DKIM Settings - Generate keys automatically or upload your own
3. Email Logs - Track all sent emails with filtering and search
4. Test Email - Professional HTML test email with diagnostics
5. Generated DKIM Keys - DNS record ready to copy

== Changelog ==

= 1.0.0 =
* Initial release
* SMTP configuration with TLS/SSL support
* DKIM signing with auto-generation
* Email logging with auto-purge
* SPF validation
* Professional HTML test emails
* Encrypted credential storage
* Translation ready
* Comprehensive testing tools

== Upgrade Notice ==

= 1.0.0 =
Initial release of Simple SMTP & DKIM.

== Usage Guide ==

= Basic SMTP Setup =

1. **Go to Settings → Simple SMTP & DKIM**
2. **Enable SMTP** using the toggle switch
3. **Enter SMTP Server Details:**
   - SMTP Host (e.g., smtp.gmail.com)
   - SMTP Port (587 for TLS, 465 for SSL)
   - Encryption (TLS recommended)
4. **Enter Authentication:**
   - Username (usually your email address)
   - Password (will be encrypted automatically)
5. **Set From Email and Name:**
   - From Email: your-email@domain.com
   - From Name: Your Site Name
6. **Click "Test Connection"** to verify
7. **Save SMTP Settings**

= DKIM Configuration (Optional but Recommended) =

**Option 1: Auto-Generate Keys (Easiest)**

1. Go to the **DKIM Settings** tab
2. Enter your **Domain** (e.g., example.com)
3. Enter a **Selector** (e.g., "default" or "mail")
4. Click **"Generate New DKIM Keys"**
5. Copy the DNS TXT record shown
6. Add the record to your DNS provider
7. Wait 5-10 minutes for DNS propagation
8. Click **"Validate DKIM"** to verify
9. **Save DKIM Settings**

**Option 2: Use Existing Keys**

1. Go to the **DKIM Settings** tab
2. Enable DKIM
3. Enter Domain and Selector
4. Choose storage method:
   - **Database:** Upload your private key file
   - **File:** Enter the path to your key file
5. Enter passphrase if your key is encrypted
6. Click **"Validate DKIM"**
7. **Save DKIM Settings**

= Viewing Saved DKIM Keys =

If you need to see your DNS record again:

1. Go to DKIM Settings tab
2. Click **"View Saved DKIM Public Key"**
3. Copy the DNS record
4. Click "Close" when done

= Email Logging =

**Enable Logging:**

1. Go to the **Email Logs** tab
2. Toggle **"Enable Logging"** ON
3. Set **Log Retention** days (default: 30)
4. Save settings

**View Logs:**

1. See statistics (total emails, success rate, etc.)
2. Search logs by recipient or subject
3. Filter by status (success/failed)
4. Click "Delete All Logs" to clear

**Export Logs:**

Logs can be exported to CSV for analysis.

= Testing Your Configuration =

**Test SMTP Connection:**

1. Go to **SMTP Settings** tab
2. Click **"Test Connection"**
3. Review results:
   - Connection success/failure
   - SPF validation status
   - Debug information (if needed)

**Send Test Email:**

1. Go to **Test Email** tab
2. Enter recipient email (defaults to admin email)
3. Click **"Send Test Email"**
4. Check inbox for professional HTML email with:
   - Full configuration details
   - Message ID for support
   - DKIM status
   - Diagnostic information

= Common SMTP Settings =

**Gmail / Google Workspace:**
- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: `TLS`
- Username: `your-email@gmail.com`
- Password: App-specific password (not your Gmail password)

**Office 365 / Outlook:**
- Host: `smtp.office365.com`
- Port: `587`
- Encryption: `TLS`
- Username: `your-email@yourdomain.com`
- Password: Your Office 365 password

**SendGrid:**
- Host: `smtp.sendgrid.net`
- Port: `587`
- Encryption: `TLS`
- Username: `apikey`
- Password: Your SendGrid API key

**Mailgun:**
- Host: `smtp.mailgun.org`
- Port: `587`
- Encryption: `TLS`
- Username: Your Mailgun SMTP username
- Password: Your Mailgun SMTP password

**Amazon SES:**
- Host: `email-smtp.REGION.amazonaws.com`
- Port: `587`
- Encryption: `TLS`
- Username: Your SES SMTP username
- Password: Your SES SMTP password

= Troubleshooting =

**Emails not sending:**
1. Check SMTP credentials are correct
2. Click "Test Connection"
3. Review Email Logs for errors
4. Enable Debug Mode
5. Check server firewall allows outbound SMTP

**DKIM validation fails:**
1. Verify DNS record is published
2. Wait for DNS propagation (up to 48 hours)
3. Check private key format (PEM)
4. Verify passphrase is correct
5. Use "Validate DKIM" for detailed errors

**Emails going to spam:**
1. Ensure SPF record authorizes your SMTP host
2. Enable DKIM signing
3. Check DMARC policy
4. Verify From email matches authorized domain

= Advanced Options =

**Force From Email/Name:**
Enable this to override any From email set by plugins/themes.

**Debug Mode:**
Enable to log detailed SMTP communication to WordPress error log.

**Storage Method:**
- **Database (Encrypted):** Easier, key stored in database
- **File (More Secure):** Key stored as file outside web root

== Support ==

For support, please:
1. Check the FAQ section above
2. Enable Debug Mode and check error logs
3. Review the NAMING_CHANGES.md and README.md files
4. Contact plugin support with:
   - WordPress version
   - PHP version
   - Error messages from logs
   - Test email Message ID

== Privacy ==

This plugin:
* Stores SMTP credentials encrypted in your WordPress database
* Logs email metadata if logging is enabled (optional)
* Does not send any data to external servers
* Does not track users
* Does not use cookies
* Does not collect analytics

All data stays on your server.

== Credits ==

Developed with ❤️ for the WordPress community.
