<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Mailer sub-tab: OAuth2 (Microsoft 365, Google Gmail)
 *
 * Based on PHPMailer + SendOauth2 wrapper.
 * Fields are shown/hidden dynamically based on provider and grant type.
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$provider       = get_option('simple_smtp_dkim_oauth_provider', '');
$grant_type     = get_option('simple_smtp_dkim_oauth_grant_type', 'authorization_code');
$client_id      = get_option('simple_smtp_dkim_oauth_client_id', '');
$smtp_address   = get_option('simple_smtp_dkim_oauth_smtp_address', '');
$tenant         = get_option('simple_smtp_dkim_oauth_tenant', '');
$hosted_domain  = get_option('simple_smtp_dkim_oauth_hosted_domain', '');
$project_id     = get_option('simple_smtp_dkim_oauth_project_id', '');
$svc_account    = get_option('simple_smtp_dkim_oauth_service_account', '');
$impersonate    = get_option('simple_smtp_dkim_oauth_impersonate', '');
$cert_thumbprint = get_option('simple_smtp_dkim_oauth_cert_thumbprint', '');
$auth_method    = get_option('simple_smtp_dkim_oauth_auth_method', 'secret');
$has_secret     = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_client_secret', ''));
$has_refresh    = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_refresh_token', ''));
$has_cert_key   = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_cert_private_key', ''));

// Provider presets for SMTP host
$provider_hosts = array(
    'microsoft' => 'smtp.office365.com',
    'google'    => 'smtp.gmail.com',
    'googleapi' => 'smtp.gmail.com',
);
?>

<!-- Provider Selection -->
<div class="simple-smtp-dkim-card">
    <h2><?php esc_html_e('Email Provider', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_provider"><?php esc_html_e('Provider', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label>
            </th>
            <td>
                <select name="simple_smtp_dkim_oauth_provider" id="simple_smtp_dkim_oauth_provider">
                    <option value=""><?php esc_html_e('— Select a provider —', 'simple-smtp-dkim'); ?></option>
                    <option value="microsoft" <?php selected($provider, 'microsoft'); ?>>Microsoft 365 / Outlook</option>
                    <option value="google" <?php selected($provider, 'google'); ?>>Google / Gmail (League OAuth2)</option>
                    <option value="googleapi" <?php selected($provider, 'googleapi'); ?>>Google / Gmail (Google API Client)</option>
                </select>
                <p class="description" id="oauth-provider-help"></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_smtp_address"><?php esc_html_e('SMTP / Envelope Address', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('The email address used as the SMTP envelope sender (mailFrom / reverse-path). Usually your mailbox address.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="email" name="simple_smtp_dkim_oauth_smtp_address" id="simple_smtp_dkim_oauth_smtp_address" value="<?php echo esc_attr($smtp_address); ?>" class="regular-text" placeholder="you@yourdomain.com" data-validate="email" aria-describedby="smtp-addr-feedback">
                <span class="smtp-field-feedback" id="smtp-addr-feedback" aria-live="polite"></span>
            </td>
        </tr>
    </table>
</div>

<!-- Grant Type & Auth Method -->
<div class="simple-smtp-dkim-card smtp-oauth-fields">
    <h2><?php esc_html_e('Authentication Method', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_grant_type"><?php esc_html_e('Grant Type', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('authorization_code: requires user consent + refresh token (most common). client_credentials: app-only access, no user interaction (service accounts).', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <select name="simple_smtp_dkim_oauth_grant_type" id="simple_smtp_dkim_oauth_grant_type">
                    <option value="authorization_code" <?php selected($grant_type, 'authorization_code'); ?>><?php esc_html_e('Authorization Code (user consent)', 'simple-smtp-dkim'); ?></option>
                    <option value="client_credentials" <?php selected($grant_type, 'client_credentials'); ?>><?php esc_html_e('Client Credentials (app-only / service account)', 'simple-smtp-dkim'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Credential Type', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Client Secret is simpler. Certificate (X.509) is more secure and required by some organizations.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <fieldset><legend class="screen-reader-text"><?php esc_html_e('Credential type', 'simple-smtp-dkim'); ?></legend>
                    <label><input type="radio" name="simple_smtp_dkim_oauth_auth_method" value="secret" <?php checked($auth_method, 'secret'); ?>> <?php esc_html_e('Client Secret', 'simple-smtp-dkim'); ?></label><br>
                    <label><input type="radio" name="simple_smtp_dkim_oauth_auth_method" value="certificate" <?php checked($auth_method, 'certificate'); ?>> <?php esc_html_e('X.509 Certificate', 'simple-smtp-dkim'); ?></label>
                </fieldset>
            </td>
        </tr>
    </table>
</div>

<!-- Client Credentials -->
<div class="simple-smtp-dkim-card smtp-oauth-fields">
    <h2><?php esc_html_e('Client Credentials', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="simple_smtp_dkim_oauth_client_id"><?php esc_html_e('Client ID', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_client_id" id="simple_smtp_dkim_oauth_client_id" value="<?php echo esc_attr($client_id); ?>" class="large-text code" placeholder="<?php esc_attr_e('Application (client) ID', 'simple-smtp-dkim'); ?>">
            </td>
        </tr>

        <!-- Client Secret -->
        <tr class="smtp-oauth-secret-field">
            <th scope="row"><label for="simple_smtp_dkim_oauth_client_secret"><?php esc_html_e('Client Secret', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="password" name="simple_smtp_dkim_oauth_client_secret" id="simple_smtp_dkim_oauth_client_secret" value="" class="large-text code" autocomplete="new-password" placeholder="<?php echo $has_secret ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Enter client secret', 'simple-smtp-dkim'); ?>">
                <?php if ($has_secret): ?>
                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Saved (encrypted).', 'simple-smtp-dkim'); ?></p>
                <?php endif; ?>
            </td>
        </tr>

        <!-- Certificate Private Key -->
        <tr class="smtp-oauth-cert-field">
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_cert_private_key"><?php esc_html_e('Certificate Private Key', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('PEM-encoded private key including -----BEGIN PRIVATE KEY----- and -----END PRIVATE KEY----- markers.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <textarea name="simple_smtp_dkim_oauth_cert_private_key" id="simple_smtp_dkim_oauth_cert_private_key" rows="4" class="large-text code" placeholder="<?php echo $has_cert_key ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----', 'simple-smtp-dkim'); ?>"></textarea>
                <?php if ($has_cert_key): ?>
                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Saved (encrypted).', 'simple-smtp-dkim'); ?></p>
                <?php endif; ?>
            </td>
        </tr>

        <!-- Certificate Thumbprint -->
        <tr class="smtp-oauth-cert-field">
            <th scope="row"><label for="simple_smtp_dkim_oauth_cert_thumbprint"><?php esc_html_e('Certificate Thumbprint', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_cert_thumbprint" id="simple_smtp_dkim_oauth_cert_thumbprint" value="<?php echo esc_attr($cert_thumbprint); ?>" class="large-text code" placeholder="<?php esc_attr_e('SHA-1 thumbprint (hex)', 'simple-smtp-dkim'); ?>">
            </td>
        </tr>
    </table>
</div>

<!-- Refresh Token (authorization_code only) -->
<div class="simple-smtp-dkim-card smtp-oauth-fields smtp-oauth-authcode-field">
    <h2><?php esc_html_e('Refresh Token', 'simple-smtp-dkim'); ?></h2>
    <p><?php esc_html_e('Required for authorization_code grant. Obtained during the initial OAuth2 consent flow.', 'simple-smtp-dkim'); ?></p>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="simple_smtp_dkim_oauth_refresh_token"><?php esc_html_e('Refresh Token', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <textarea name="simple_smtp_dkim_oauth_refresh_token" id="simple_smtp_dkim_oauth_refresh_token" rows="3" class="large-text code" autocomplete="off" placeholder="<?php echo $has_refresh ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Paste your refresh token', 'simple-smtp-dkim'); ?>"></textarea>
                <?php if ($has_refresh): ?>
                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Saved (encrypted).', 'simple-smtp-dkim'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<!-- Microsoft-specific -->
<div class="simple-smtp-dkim-card smtp-oauth-fields smtp-oauth-microsoft-field">
    <h2><?php esc_html_e('Microsoft Settings', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_tenant"><?php esc_html_e('Tenant ID', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Azure AD tenant GUID. Found in Azure Portal → Azure Active Directory → Properties.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_tenant" id="simple_smtp_dkim_oauth_tenant" value="<?php echo esc_attr($tenant); ?>" class="large-text code" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
            </td>
        </tr>
    </table>
</div>

<!-- Google-specific -->
<div class="simple-smtp-dkim-card smtp-oauth-fields smtp-oauth-google-field">
    <h2><?php esc_html_e('Google Settings', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_hosted_domain"><?php esc_html_e('Hosted Domain', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Google Workspace domain (e.g., yourdomain.com). Leave blank for personal @gmail.com accounts.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_hosted_domain" id="simple_smtp_dkim_oauth_hosted_domain" value="<?php echo esc_attr($hosted_domain); ?>" class="regular-text" placeholder="yourdomain.com">
            </td>
        </tr>
    </table>
</div>

<!-- Google API specific (only when provider = googleapi) -->
<div class="simple-smtp-dkim-card smtp-oauth-fields smtp-oauth-googleapi-field">
    <h2><?php esc_html_e('Google API Client Settings', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_project_id"><?php esc_html_e('Project ID', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Google Cloud project ID. Found in Google Cloud Console.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_project_id" id="simple_smtp_dkim_oauth_project_id" value="<?php echo esc_attr($project_id); ?>" class="regular-text" placeholder="my-project-12345">
            </td>
        </tr>
        <tr class="smtp-oauth-svc-field">
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_service_account"><?php esc_html_e('Service Account Name', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Only for client_credentials grant. The service account email prefix.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="text" name="simple_smtp_dkim_oauth_service_account" id="simple_smtp_dkim_oauth_service_account" value="<?php echo esc_attr($svc_account); ?>" class="regular-text" placeholder="my-service-account">
            </td>
        </tr>
        <tr class="smtp-oauth-svc-field">
            <th scope="row">
                <label for="simple_smtp_dkim_oauth_impersonate"><?php esc_html_e('Impersonate (send as)', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Google Workspace email to send on behalf of. Must be a Workspace account, not @gmail.com. Defaults to the SMTP address.', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <input type="email" name="simple_smtp_dkim_oauth_impersonate" id="simple_smtp_dkim_oauth_impersonate" value="<?php echo esc_attr($impersonate); ?>" class="regular-text" placeholder="user@yourdomain.com">
            </td>
        </tr>
    </table>
</div>

<!-- Setup Guide -->
<div class="simple-smtp-dkim-card smtp-oauth-fields">
    <details class="smtp-advanced-details">
        <summary><h2 class="smtp-inline-heading"><?php esc_html_e('Setup Guide', 'simple-smtp-dkim'); ?></h2></summary>
        <div class="smtp-advanced-inner">
            <div class="smtp-oauth-guide smtp-oauth-guide-microsoft">
                <h3>Microsoft 365 / Outlook</h3>
                <ol>
                    <li><?php esc_html_e('Go to <strong>Azure Portal → App registrations → New registration</strong>', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Set redirect URI to your site URL', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Under <strong>Certificates & secrets</strong>, create a Client Secret or upload a Certificate', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Under <strong>API permissions</strong>, add <code>SMTP.Send</code> (delegated) for authorization_code, or <code>Mail.Send</code> (application) for client_credentials', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Copy the <strong>Application (client) ID</strong> and <strong>Directory (tenant) ID</strong>', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Obtain a Refresh Token using the OAuth2 authorization flow', 'simple-smtp-dkim'); ?></li>
                </ol>
            </div>
            <div class="smtp-oauth-guide smtp-oauth-guide-google">
                <h3>Google / Gmail</h3>
                <ol>
                    <li><?php esc_html_e('Go to <strong>Google Cloud Console → APIs & Services → Credentials</strong>', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Create an <strong>OAuth 2.0 Client ID</strong> (Web application)', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Set redirect URI to your site URL', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Enable the <strong>Gmail API</strong> in your project', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('Copy the <strong>Client ID</strong> and <strong>Client Secret</strong>', 'simple-smtp-dkim'); ?></li>
                    <li><?php esc_html_e('For service accounts: enable <strong>domain-wide delegation</strong> in Google Workspace Admin', 'simple-smtp-dkim'); ?></li>
                </ol>
            </div>
        </div>
    </details>
</div>
