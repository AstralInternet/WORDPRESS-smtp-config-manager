/**
 * Simple SMTP & DKIM — Admin Script
 * @package Simple_SMTP_DKIM
 */
(function($) {
    'use strict';

    var S = simpleSMTPDKIM;

    /* =====================================================================
       Real-time form validation (Point 3)
       ===================================================================== */

    var validators = {
        host: function(v) {
            if (!v) return {ok:false, msg:'Required'};
            if (/^[a-zA-Z0-9][a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v)) return {ok:true, msg:'✓'};
            if (/^\d{1,3}(\.\d{1,3}){3}$/.test(v)) return {ok:true, msg:'✓ IP'};
            return {ok:false, msg:'Invalid hostname'};
        },
        port: function(v) {
            var n = parseInt(v, 10);
            if (!v || isNaN(n)) return {ok:false, msg:'Required'};
            if (n < 1 || n > 65535) return {ok:false, msg:'1–65535'};
            var common = {25:'SMTP', 465:'SSL', 587:'TLS', 2525:'Alt'};
            return {ok:true, msg: common[n] ? '✓ ' + common[n] : '✓'};
        },
        email: function(v) {
            if (!v) return {ok:true, msg:''};
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? {ok:true, msg:'✓'} : {ok:false, msg:'Invalid email'};
        },
        required: function(v) {
            return v ? {ok:true, msg:'✓'} : {ok:false, msg:'Required'};
        }
    };

    function validateField(el) {
        var type = el.data('validate');
        if (!type || !validators[type]) return;
        var result = validators[type](el.val());
        var fb = el.siblings('.smtp-field-feedback').first();
        if (!fb.length) fb = $('#' + el.attr('aria-describedby'));
        if (!fb.length) return;
        fb.text(result.msg)
          .toggleClass('smtp-feedback-ok', result.ok)
          .toggleClass('smtp-feedback-error', !result.ok && result.msg !== '');
        el.toggleClass('smtp-input-valid', result.ok && !!el.val())
          .toggleClass('smtp-input-invalid', !result.ok && !!el.val());
    }

    $(document).on('input change', '[data-validate]', function() {
        validateField($(this));
    });

    // Auto-adjust port when encryption changes
    $('#simple_smtp_dkim_secure').on('change', function() {
        var map = {tls: 587, ssl: 465, '': 25};
        var port = map[this.value];
        if (port) {
            $('#simple_smtp_dkim_port').val(port).trigger('input');
        }
    });

    // Show/hide auth fields
    function toggleAuth() {
        var show = $('#simple_smtp_dkim_auth').is(':checked');
        $('.smtp-auth-field').toggle(show);
    }
    $('#simple_smtp_dkim_auth').on('change', toggleAuth);
    toggleAuth();

    // Show/hide DKIM fields
    function toggleDkim() {
        var show = $('#simple_smtp_dkim_dkim_enabled').is(':checked');
        $('.smtp-dkim-field').toggle(show);
    }
    $('#simple_smtp_dkim_dkim_enabled').on('change', toggleDkim);
    toggleDkim();

    // Storage method toggle
    $('input[name="simple_smtp_dkim_dkim_storage_method"]').on('change', function() {
        var val = $(this).val();
        $('.smtp-storage-database').toggle(val === 'database');
        $('.smtp-storage-file').toggle(val === 'file');
    });

    // Initial field validation on load
    $('[data-validate]').each(function() {
        if ($(this).val()) validateField($(this));
    });

    /* =====================================================================
       Test Connection (Point 7 — inline)
       ===================================================================== */

    function setButtonLoading(btn, loading, text) {
        btn.prop('disabled', loading);
        if (loading) {
            btn.data('original-text', btn.html());
            btn.html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> ' + text);
        } else {
            btn.html(btn.data('original-text'));
        }
    }

    function showResult(container, success, message) {
        container.show()
            .toggleClass('smtp-result-success', success)
            .toggleClass('smtp-result-error', !success)
            .html((success ? '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' : '<span class="dashicons dashicons-warning" aria-hidden="true"></span> ') + message);
    }

    $('#smtp-test-connection').on('click', function() {
        var btn = $(this);
        var result = $('#smtp-test-result');
        var debug = $('#smtp-test-debug');
        var useSaved = $('#simple_smtp_dkim_password').data('has-saved-password') === '1' || $('#simple_smtp_dkim_password').data('has-saved-password') === 1;
        var pw = $('#simple_smtp_dkim_password').val();

        setButtonLoading(btn, true, S.strings.testing);
        result.hide();
        debug.hide();

        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_test_connection',
            nonce: S.nonces.test_connection,
            host: $('#simple_smtp_dkim_host').val(),
            port: $('#simple_smtp_dkim_port').val(),
            secure: $('#simple_smtp_dkim_secure').val(),
            auth: $('#simple_smtp_dkim_auth').is(':checked'),
            username: $('#simple_smtp_dkim_username').val(),
            password: pw,
            use_saved_password: (!pw && useSaved) ? 'true' : 'false'
        }, function(resp) {
            setButtonLoading(btn, false);
            var ok = resp.success;
            var data = resp.data || {};
            showResult(result, ok, data.message || 'Unknown error');
            if (data.debug) {
                debug.show().find('.smtp-debug-content').text(data.debug);
            }
        }).fail(function() {
            setButtonLoading(btn, false);
            showResult(result, false, 'Network error.');
        });
    });

    /* =====================================================================
       Send Test Email (Point 7 — inline next to Test Connection)
       ===================================================================== */

    $('#smtp-send-test-email').on('click', function() {
        var btn = $(this);
        var result = $('#smtp-test-result');
        var to = $('#smtp_test_email_to').val();
        if (!to || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(to)) {
            showResult(result, false, 'Enter a valid email address.');
            return;
        }
        var useSaved = $('#simple_smtp_dkim_password').data('has-saved-password') === '1' || $('#simple_smtp_dkim_password').data('has-saved-password') === 1;
        var pw = $('#simple_smtp_dkim_password').val();

        setButtonLoading(btn, true, S.strings.sending);
        result.hide();

        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_send_test_email',
            nonce: S.nonces.send_test_email,
            to_email: to,
            use_temp_settings: 'true',
            host: $('#simple_smtp_dkim_host').val(),
            port: $('#simple_smtp_dkim_port').val(),
            secure: $('#simple_smtp_dkim_secure').val(),
            auth: $('#simple_smtp_dkim_auth').is(':checked'),
            username: $('#simple_smtp_dkim_username').val(),
            password: pw || (useSaved ? '' : ''),
            use_saved_password: (!pw && useSaved) ? 'true' : 'false'
        }, function(resp) {
            setButtonLoading(btn, false);
            showResult(result, resp.success, (resp.data || {}).message || 'Error');
        }).fail(function() {
            setButtonLoading(btn, false);
            showResult(result, false, 'Network error.');
        });
    });

    // Debug toggle
    $(document).on('click', '.smtp-debug-toggle', function() {
        var content = $(this).next('.smtp-debug-content');
        var expanded = content.is(':visible');
        content.slideToggle(200);
        $(this).attr('aria-expanded', !expanded);
        $(this).text(expanded ? simpleSMTPDKIM.strings.error.replace('Error','Show Debug Info') : 'Hide Debug Info');
    });

    /* =====================================================================
       DKIM Key Generation (Point 4)
       ===================================================================== */

    function showDkimDns(name, value) {
        $('#smtp-dns-record-name').text(name);
        $('#smtp-dns-record-value').val(value);
        $('#smtp-dkim-generated-result').slideDown(300);
        $('#smtp-dkim-generate-section .button').hide();
    }

    $('#smtp-generate-dkim-keys').on('click', function() {
        var btn = $(this);
        var domain = $('#simple_smtp_dkim_dkim_domain').val();
        var selector = $('#simple_smtp_dkim_dkim_selector').val();
        if (!domain || !selector) {
            alert('Enter a domain and selector first.');
            return;
        }
        setButtonLoading(btn, true, 'Generating...');
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_generate_dkim_keys',
            nonce: S.nonces.generate_dkim,
            domain: domain,
            selector: selector
        }, function(resp) {
            setButtonLoading(btn, false);
            if (resp.success) {
                showDkimDns(resp.data.dns_record_name, resp.data.dns_record_value);
            } else {
                alert((resp.data || {}).message || 'Error');
            }
        }).fail(function() {
            setButtonLoading(btn, false);
            alert('Network error.');
        });
    });

    // View saved keys
    $('#smtp-view-dkim-keys').on('click', function() {
        var pubKey = $('#smtp-saved-public-key').val();
        var domain = $('#smtp-saved-dkim-domain').val();
        var selector = $('#smtp-saved-dkim-selector').val();
        if (pubKey && domain && selector) {
            showDkimDns(selector + '._domainkey.' + domain, 'v=DKIM1; k=rsa; p=' + pubKey);
        }
    });

    $('#smtp-close-dkim-display').on('click', function() {
        $('#smtp-dkim-generated-result').slideUp(300);
        $('#smtp-dkim-generate-section .button').show();
    });

    /* =====================================================================
       DKIM Validation
       ===================================================================== */

    $('#smtp-validate-dkim').on('click', function() {
        var btn = $(this);
        var result = $('#smtp-dkim-result');
        setButtonLoading(btn, true, S.strings.validating);
        result.hide();
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_validate_dkim',
            nonce: S.nonces.validate_dkim,
            dkim_domain: $('#simple_smtp_dkim_dkim_domain').val(),
            dkim_selector: $('#simple_smtp_dkim_dkim_selector').val(),
            storage_method: $('input[name="simple_smtp_dkim_dkim_storage_method"]:checked').val() || 'database',
            file_path: $('#simple_smtp_dkim_dkim_file_path').val()
        }, function(resp) {
            setButtonLoading(btn, false);
            result.show()
                .toggleClass('smtp-result-success', resp.success)
                .toggleClass('smtp-result-error', !resp.success)
                .html((resp.data || {}).message || 'Error');
        }).fail(function() {
            setButtonLoading(btn, false);
            showResult(result, false, 'Network error.');
        });
    });

    /* =====================================================================
       Copy to Clipboard
       ===================================================================== */

    $(document).on('click', '.smtp-copy-btn', function() {
        var btn = $(this);
        var targetId = btn.data('copy-target');
        var target = $('#' + targetId);
        var text = target.is('textarea, input') ? target.val() : target.text();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                var orig = btn.text();
                btn.text(S.strings.copied);
                setTimeout(function() { btn.text(orig); }, 2000);
            });
        } else {
            // Fallback
            var ta = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            ta.remove();
            var orig = btn.text();
            btn.text(S.strings.copied);
            setTimeout(function() { btn.text(orig); }, 2000);
        }
    });

    /* =====================================================================
       Logs — Delete All
       ===================================================================== */

    $('#smtp-delete-all-logs').on('click', function() {
        if (!confirm(S.strings.confirmDelete)) return;
        var btn = $(this);
        setButtonLoading(btn, true, 'Deleting...');
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_delete_all_logs',
            nonce: S.nonces.delete_logs
        }, function(resp) {
            setButtonLoading(btn, false);
            if (resp.success) location.reload();
            else alert((resp.data || {}).message || 'Error');
        }).fail(function() {
            setButtonLoading(btn, false);
            alert('Network error.');
        });
    });

    /* =====================================================================
       Logs — Export CSV (client-side)
       ===================================================================== */

    $('#smtp-export-logs').on('click', function() {
        var rows = [['Date', 'To', 'Subject', 'Status', 'DKIM', 'Error']];
        $('.smtp-logs-table tbody tr').each(function() {
            var cells = $(this).find('td');
            rows.push([
                cells.eq(0).text().trim(),
                cells.eq(1).text().trim(),
                cells.eq(2).text().trim().split('\n')[0].trim(),
                cells.eq(3).text().trim(),
                cells.eq(4).find('.dashicons-yes-alt').length ? 'Yes' : 'No',
                cells.eq(2).find('.smtp-error-message').text().trim()
            ]);
        });
        var csv = rows.map(function(r) {
            return r.map(function(c) { return '"' + c.replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        var blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'email-logs-' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    });

    /* =====================================================================
       Logs — View Email Modal (a11y — Point 6)
       ===================================================================== */

    var lastFocus = null;

    function openModal(modal) {
        lastFocus = document.activeElement;
        modal.show().attr('aria-hidden', 'false');
        modal.find('.smtp-modal-close').focus();
        $('body').css('overflow', 'hidden');
    }

    function closeModal(modal) {
        modal.hide().attr('aria-hidden', 'true');
        $('body').css('overflow', '');
        if (lastFocus) lastFocus.focus();
    }

    $(document).on('click', '.smtp-view-email', function() {
        var logId = $(this).data('log-id');
        var modal = $('#smtp-email-view-modal');
        modal.find('.smtp-email-meta span').text('Loading...');
        openModal(modal);
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_view_email',
            nonce: S.nonces.view_email,
            log_id: logId
        }, function(resp) {
            if (resp.success) {
                var d = resp.data;
                $('#smtp-email-to').text(d.to_email);
                $('#smtp-email-from').text(d.from_email);
                $('#smtp-email-subject').text(d.subject);
                $('#smtp-email-date').text(d.timestamp);
                var iframe = document.getElementById('smtp-email-iframe');
                var doc = iframe.contentDocument || iframe.contentWindow.document;
                doc.open();
                doc.write(d.email_body || '<p>No content</p>');
                doc.close();
            }
        });
    });

    $(document).on('click', '.smtp-modal-close, .smtp-modal-overlay', function() {
        closeModal($('#smtp-email-view-modal'));
    });

    // Escape key closes modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = $('#smtp-email-view-modal');
            if (modal.is(':visible')) closeModal(modal);
        }
    });

    // Trap focus inside modal
    $(document).on('keydown', '#smtp-email-view-modal', function(e) {
        if (e.key !== 'Tab') return;
        var modal = $(this);
        var focusable = modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
        var first = focusable.first();
        var last = focusable.last();
        if (e.shiftKey && document.activeElement === first[0]) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last[0]) {
            e.preventDefault();
            first.focus();
        }
    });

})(jQuery);

    /* =====================================================================
       OAuth2 — Conditional field visibility
       ===================================================================== */

    function toggleOAuthFields() {
        var provider  = $('#simple_smtp_dkim_oauth_provider').val();
        var grantType = $('#simple_smtp_dkim_oauth_grant_type').val();
        var authMethod = $('input[name="simple_smtp_dkim_oauth_auth_method"]:checked').val();

        // Show all oauth field cards only if a provider is selected
        var hasProvider = !!provider;
        $('.smtp-oauth-fields').toggle(hasProvider);

        if (!hasProvider) return;

        // Provider-specific sections
        $('.smtp-oauth-microsoft-field').toggle(provider === 'microsoft');
        $('.smtp-oauth-google-field').toggle(provider === 'google' || provider === 'googleapi');
        $('.smtp-oauth-googleapi-field').toggle(provider === 'googleapi');

        // Grant type: refresh token only for authorization_code
        $('.smtp-oauth-authcode-field').toggle(grantType === 'authorization_code');

        // Service account fields only for client_credentials + googleapi
        $('.smtp-oauth-svc-field').toggle(grantType === 'client_credentials' && provider === 'googleapi');

        // Auth method: secret vs certificate
        $('.smtp-oauth-secret-field').toggle(authMethod === 'secret');
        $('.smtp-oauth-cert-field').toggle(authMethod === 'certificate');

        // Provider help text
        var helpTexts = {
            microsoft: 'SMTP host: smtp.office365.com — Port: 587 (TLS)',
            google: 'SMTP host: smtp.gmail.com — Port: 587 (TLS)',
            googleapi: 'SMTP host: smtp.gmail.com — Port: 587 (TLS) — Uses Google API Client'
        };
        $('#oauth-provider-help').text(helpTexts[provider] || '');

        // Setup guide sections
        $('.smtp-oauth-guide-microsoft').toggle(provider === 'microsoft');
        $('.smtp-oauth-guide-google').toggle(provider === 'google' || provider === 'googleapi');
    }

    // Bind events
    $('#simple_smtp_dkim_oauth_provider, #simple_smtp_dkim_oauth_grant_type').on('change', toggleOAuthFields);
    $('input[name="simple_smtp_dkim_oauth_auth_method"]').on('change', toggleOAuthFields);

    // Initial state
    toggleOAuthFields();

