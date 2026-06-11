<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Mailer sub-tab: OAuth2 (Microsoft 365, Google Gmail)
 *
 * Fields are shown/hidden dynamically based on provider and grant type.
 * The OAuth transport itself ships in a later release; settings can be
 * prepared and saved already (see tab-mailer.php for the disabled toggle).
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$provider        = get_option('simple_smtp_dkim_oauth_provider', '');
$grant_type      = get_option('simple_smtp_dkim_oauth_grant_type', 'authorization_code');
$client_id       = get_option('simple_smtp_dkim_oauth_client_id', '');
$smtp_address    = get_option('simple_smtp_dkim_oauth_smtp_address', '');
$tenant          = get_option('simple_smtp_dkim_oauth_tenant', '');
$hosted_domain   = get_option('simple_smtp_dkim_oauth_hosted_domain', '');
$project_id      = get_option('simple_smtp_dkim_oauth_project_id', '');
$svc_account     = get_option('simple_smtp_dkim_oauth_service_account', '');
$impersonate     = get_option('simple_smtp_dkim_oauth_impersonate', '');
$cert_thumbprint = get_option('simple_smtp_dkim_oauth_cert_thumbprint', '');
$auth_method     = get_option('simple_smtp_dkim_oauth_auth_method', 'secret');
$has_secret      = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_client_secret', ''));
$has_refresh     = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_refresh_token', ''));
$has_cert_key    = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_oauth_cert_private_key', ''));

$redirect_uri = admin_url('options-general.php?page=simple-smtp-dkim&oauth=callback');
?>

<!-- OAuth hero -->
<div class="ssd-card ssd-hero">
    <div class="ssd-card-head">
        <?php Simple_SMTP_DKIM_Helpers::icon('shieldcheck', 18); ?>
        <h2><?php esc_html_e('OAuth2 connection', 'simple-smtp-dkim'); ?></h2>
    </div>
    <p class="ssd-lead"><?php esc_html_e('The modern, safest method: no password is stored. You authorize sending through your account and a revocable token is used instead.', 'simple-smtp-dkim'); ?></p>
    <div class="ssd-muted-note">
        <?php Simple_SMTP_DKIM_Helpers::icon('info', 14); ?>
        <span><?php esc_html_e('OAuth2 sending is coming in a future update. You can already prepare and save your application settings below.', 'simple-smtp-dkim'); ?></span>
    </div>
</div>

<div class="ssd-grid-2">
    <!-- Provider -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('globe', 18); ?>
            <h2><?php esc_html_e('Provider', 'simple-smtp-dkim'); ?></h2>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_oauth_provider"><?php esc_html_e('Email provider', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
            <select name="simple_smtp_dkim_oauth_provider" id="simple_smtp_dkim_oauth_provider" class="ssd-sel">
                <option value=""><?php esc_html_e('— Select a provider —', 'simple-smtp-dkim'); ?></option>
                <option value="microsoft" <?php selected($provider, 'microsoft'); ?>>Microsoft 365 / Outlook</option>
                <option value="google" <?php selected($provider, 'google'); ?>>Google / Gmail (League OAuth2)</option>
                <option value="googleapi" <?php selected($provider, 'googleapi'); ?>>Google / Gmail (Google API Client)</option>
            </select>
            <p class="ssd-desc" id="ssd-oauth-provider-help"></p>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_oauth_smtp_address">
                <?php esc_html_e('Envelope (SMTP) address', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('The email address used as the SMTP envelope sender (mailFrom / reverse-path). Usually your mailbox address.', 'simple-smtp-dkim')); ?>
            </label>
            <input type="email" name="simple_smtp_dkim_oauth_smtp_address" id="simple_smtp_dkim_oauth_smtp_address"
                   value="<?php echo esc_attr($smtp_address); ?>" class="ssd-inp" placeholder="you@yourdomain.com"
                   data-validate="email" aria-describedby="ssd-oauth-addr-feedback">
            <span class="ssd-inp-feedback" id="ssd-oauth-addr-feedback" aria-live="polite"></span>
        </div>
    </div>

    <!-- Authorization method -->
    <div class="ssd-card ssd-oauth-fields">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('key', 18); ?>
            <h2><?php esc_html_e('Authorization method', 'simple-smtp-dkim'); ?></h2>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_oauth_grant_type">
                <?php esc_html_e('Grant type', 'simple-smtp-dkim'); ?>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Authorization Code: user consent + refresh token (most common). Client Credentials: app-only access, no user interaction (service accounts).', 'simple-smtp-dkim')); ?>
            </label>
            <select name="simple_smtp_dkim_oauth_grant_type" id="simple_smtp_dkim_oauth_grant_type" class="ssd-sel">
                <option value="authorization_code" <?php selected($grant_type, 'authorization_code'); ?>><?php esc_html_e('Authorization Code (user consent)', 'simple-smtp-dkim'); ?></option>
                <option value="client_credentials" <?php selected($grant_type, 'client_credentials'); ?>><?php esc_html_e('Client Credentials (app-only / service account)', 'simple-smtp-dkim'); ?></option>
            </select>
        </div>
        <div class="ssd-field">
            <label id="ssd-oauth-method-label"><?php esc_html_e('Credential type', 'simple-smtp-dkim'); ?></label>
            <div class="ssd-radio-cards cols-2" role="radiogroup" aria-labelledby="ssd-oauth-method-label">
                <label class="ssd-rc <?php echo $auth_method === 'secret' ? 'sel' : ''; ?>">
                    <input type="radio" name="simple_smtp_dkim_oauth_auth_method" value="secret" <?php checked($auth_method, 'secret'); ?>>
                    <span class="ssd-rc-dot" aria-hidden="true"></span>
                    <span>
                        <span class="ssd-rc-title" style="display:block;"><?php esc_html_e('Client secret', 'simple-smtp-dkim'); ?></span>
                        <span class="ssd-rc-desc" style="display:block;"><?php esc_html_e('Simpler', 'simple-smtp-dkim'); ?></span>
                    </span>
                </label>
                <label class="ssd-rc <?php echo $auth_method === 'certificate' ? 'sel' : ''; ?>">
                    <input type="radio" name="simple_smtp_dkim_oauth_auth_method" value="certificate" <?php checked($auth_method, 'certificate'); ?>>
                    <span class="ssd-rc-dot" aria-hidden="true"></span>
                    <span>
                        <span class="ssd-rc-title" style="display:block;"><?php esc_html_e('X.509 certificate', 'simple-smtp-dkim'); ?></span>
                        <span class="ssd-rc-desc" style="display:block;"><?php esc_html_e('More secure', 'simple-smtp-dkim'); ?></span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Application credentials -->
<div class="ssd-card ssd-oauth-fields">
    <div class="ssd-card-head">
        <?php Simple_SMTP_DKIM_Helpers::icon('lock', 18); ?>
        <h2><?php esc_html_e('Application credentials', 'simple-smtp-dkim'); ?></h2>
    </div>
    <div class="ssd-field">
        <label for="simple_smtp_dkim_oauth_client_id"><?php esc_html_e('Client ID (Application ID)', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
        <input type="text" name="simple_smtp_dkim_oauth_client_id" id="simple_smtp_dkim_oauth_client_id"
               value="<?php echo esc_attr($client_id); ?>" class="ssd-inp mono" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
    </div>

    <div class="ssd-field ssd-oauth-secret-field">
        <label for="simple_smtp_dkim_oauth_client_secret"><?php esc_html_e('Client secret', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
        <input type="password" name="simple_smtp_dkim_oauth_client_secret" id="simple_smtp_dkim_oauth_client_secret" value=""
               class="ssd-inp mono" autocomplete="new-password"
               placeholder="<?php echo $has_secret ? esc_attr__('•••••••• (saved — leave blank to keep)', 'simple-smtp-dkim') : esc_attr__('Enter the client secret', 'simple-smtp-dkim'); ?>">
        <p class="ssd-desc"><?php esc_html_e('Encrypted with AES-256 before storage.', 'simple-smtp-dkim'); ?></p>
    </div>

    <div class="ssd-field ssd-oauth-cert-field">
        <label for="simple_smtp_dkim_oauth_cert_private_key">
            <?php esc_html_e('Certificate private key', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('PEM-encoded private key including -----BEGIN PRIVATE KEY----- and -----END PRIVATE KEY----- markers.', 'simple-smtp-dkim')); ?>
        </label>
        <textarea name="simple_smtp_dkim_oauth_cert_private_key" id="simple_smtp_dkim_oauth_cert_private_key" rows="4"
                  class="ssd-inp mono" placeholder="<?php echo $has_cert_key ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('-----BEGIN PRIVATE KEY-----', 'simple-smtp-dkim'); ?>"></textarea>
    </div>

    <div class="ssd-field ssd-oauth-cert-field">
        <label for="simple_smtp_dkim_oauth_cert_thumbprint">
            <?php esc_html_e('Certificate thumbprint', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('SHA-1 thumbprint (hex) of the X.509 certificate.', 'simple-smtp-dkim')); ?>
        </label>
        <input type="text" name="simple_smtp_dkim_oauth_cert_thumbprint" id="simple_smtp_dkim_oauth_cert_thumbprint"
               value="<?php echo esc_attr($cert_thumbprint); ?>" class="ssd-inp mono" placeholder="A1B2C3…">
    </div>

    <div class="ssd-field ssd-oauth-authcode-field">
        <label for="simple_smtp_dkim_oauth_refresh_token">
            <?php esc_html_e('Refresh token', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Required for the authorization_code grant. Obtained during the initial OAuth2 consent flow.', 'simple-smtp-dkim')); ?>
        </label>
        <textarea name="simple_smtp_dkim_oauth_refresh_token" id="simple_smtp_dkim_oauth_refresh_token" rows="3"
                  class="ssd-inp mono" autocomplete="off"
                  placeholder="<?php echo $has_refresh ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Paste your refresh token', 'simple-smtp-dkim'); ?>"></textarea>
        <p class="ssd-desc"><?php esc_html_e('Encrypted with AES-256 before storage.', 'simple-smtp-dkim'); ?></p>
    </div>
</div>

<div class="ssd-grid-2 ssd-oauth-fields">
    <!-- Microsoft-specific -->
    <div class="ssd-card ssd-oauth-microsoft-field">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('settings', 18); ?>
            <h2><?php esc_html_e('Microsoft settings', 'simple-smtp-dkim'); ?></h2>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_oauth_tenant">
                <?php esc_html_e('Tenant ID', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Azure AD tenant GUID. Found in Azure Portal → Azure Active Directory → Properties.', 'simple-smtp-dkim')); ?>
            </label>
            <input type="text" name="simple_smtp_dkim_oauth_tenant" id="simple_smtp_dkim_oauth_tenant"
                   value="<?php echo esc_attr($tenant); ?>" class="ssd-inp mono" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
        </div>
    </div>

    <!-- Google-specific -->
    <div class="ssd-card ssd-oauth-google-field">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('settings', 18); ?>
            <h2><?php esc_html_e('Google settings', 'simple-smtp-dkim'); ?></h2>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_oauth_hosted_domain">
                <?php esc_html_e('Hosted domain', 'simple-smtp-dkim'); ?>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Google Workspace domain (e.g., yourdomain.com). Leave blank for personal @gmail.com accounts.', 'simple-smtp-dkim')); ?>
            </label>
            <input type="text" name="simple_smtp_dkim_oauth_hosted_domain" id="simple_smtp_dkim_oauth_hosted_domain"
                   value="<?php echo esc_attr($hosted_domain); ?>" class="ssd-inp" placeholder="yourdomain.com">
        </div>
    </div>

    <!-- Redirect URI -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('globe', 18); ?>
            <h2><?php esc_html_e('Redirect URI', 'simple-smtp-dkim'); ?></h2>
        </div>
        <p class="ssd-desc" style="margin:0 0 10px;"><?php esc_html_e('Add this URI to your application configuration at the provider:', 'simple-smtp-dkim'); ?></p>
        <div class="ssd-dns-block" style="margin-top:0;">
            <div class="ssd-dns-row">
                <div class="ssd-dns-val">
                    <code id="ssd-oauth-redirect-uri"><?php echo esc_html($redirect_uri); ?></code>
                    <?php Simple_SMTP_DKIM_Helpers::render_copy_button('ssd-oauth-redirect-uri', __('Copy the redirect URI', 'simple-smtp-dkim')); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google API specific (only when provider = googleapi) -->
<div class="ssd-card ssd-oauth-fields ssd-oauth-googleapi-field">
    <div class="ssd-card-head">
        <?php Simple_SMTP_DKIM_Helpers::icon('settings', 18); ?>
        <h2><?php esc_html_e('Google API Client settings', 'simple-smtp-dkim'); ?></h2>
    </div>
    <div class="ssd-field">
        <label for="simple_smtp_dkim_oauth_project_id">
            <?php esc_html_e('Project ID', 'simple-smtp-dkim'); ?>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Your Google Cloud project ID. Found in Google Cloud Console.', 'simple-smtp-dkim')); ?>
        </label>
        <input type="text" name="simple_smtp_dkim_oauth_project_id" id="simple_smtp_dkim_oauth_project_id"
               value="<?php echo esc_attr($project_id); ?>" class="ssd-inp" placeholder="my-project-12345">
    </div>
    <div class="ssd-field ssd-oauth-svc-field">
        <label for="simple_smtp_dkim_oauth_service_account">
            <?php esc_html_e('Service account name', 'simple-smtp-dkim'); ?>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Only for the client_credentials grant. The service account email prefix.', 'simple-smtp-dkim')); ?>
        </label>
        <input type="text" name="simple_smtp_dkim_oauth_service_account" id="simple_smtp_dkim_oauth_service_account"
               value="<?php echo esc_attr($svc_account); ?>" class="ssd-inp" placeholder="my-service-account">
    </div>
    <div class="ssd-field ssd-oauth-svc-field">
        <label for="simple_smtp_dkim_oauth_impersonate">
            <?php esc_html_e('Impersonate (send as)', 'simple-smtp-dkim'); ?>
            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Google Workspace email to send on behalf of. Must be a Workspace account, not @gmail.com. Defaults to the SMTP address.', 'simple-smtp-dkim')); ?>
        </label>
        <input type="email" name="simple_smtp_dkim_oauth_impersonate" id="simple_smtp_dkim_oauth_impersonate"
               value="<?php echo esc_attr($impersonate); ?>" class="ssd-inp" placeholder="user@yourdomain.com">
    </div>
</div>

<!-- Setup guide -->
<div class="ssd-card ssd-oauth-fields">
    <details class="ssd-details">
        <summary>
            <?php Simple_SMTP_DKIM_Helpers::icon('info', 15); ?>
            <span><?php esc_html_e('Setup guide', 'simple-smtp-dkim'); ?></span>
            <?php Simple_SMTP_DKIM_Helpers::icon('chevron', 16, 'ssd-chev'); ?>
        </summary>
        <div class="ssd-details-inner">
            <div class="ssd-oauth-guide ssd-oauth-guide-microsoft">
                <p class="ssd-section-label">Microsoft 365 / Outlook</p>
                <ol class="ssd-muted-note" style="display:block;padding-left:18px;line-height:1.9;margin:0;">
                    <li><?php echo wp_kses(__('Azure Portal → <strong>App registrations → New registration</strong>', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php esc_html_e('Set the redirect URI shown above', 'simple-smtp-dkim'); ?></li>
                    <li><?php echo wp_kses(__('Under <strong>Certificates &amp; secrets</strong>, create a client secret or upload a certificate', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php echo wp_kses(__('Under <strong>API permissions</strong>, add <code>SMTP.Send</code> (delegated) for authorization_code, or <code>Mail.Send</code> (application) for client_credentials', 'simple-smtp-dkim'), array('strong' => array(), 'code' => array())); ?></li>
                    <li><?php echo wp_kses(__('Copy the <strong>Application (client) ID</strong> and the <strong>Directory (tenant) ID</strong>', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php esc_html_e('Obtain a refresh token using the OAuth2 authorization flow', 'simple-smtp-dkim'); ?></li>
                </ol>
            </div>
            <div class="ssd-oauth-guide ssd-oauth-guide-google">
                <p class="ssd-section-label">Google / Gmail</p>
                <ol class="ssd-muted-note" style="display:block;padding-left:18px;line-height:1.9;margin:0;">
                    <li><?php echo wp_kses(__('Google Cloud Console → <strong>APIs &amp; Services → Credentials</strong>', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php echo wp_kses(__('Create an <strong>OAuth 2.0 Client ID</strong> (Web application)', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php esc_html_e('Set the redirect URI shown above', 'simple-smtp-dkim'); ?></li>
                    <li><?php echo wp_kses(__('Enable the <strong>Gmail API</strong> in your project', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php echo wp_kses(__('Copy the <strong>Client ID</strong> and the <strong>Client Secret</strong>', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                    <li><?php echo wp_kses(__('For service accounts: enable <strong>domain-wide delegation</strong> in Google Workspace Admin', 'simple-smtp-dkim'), array('strong' => array())); ?></li>
                </ol>
            </div>
        </div>
    </details>
</div>
