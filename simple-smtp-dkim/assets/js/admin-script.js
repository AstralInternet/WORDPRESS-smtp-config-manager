/**
 * Simple SMTP & DKIM — Admin Script
 *
 * Implements the redesigned admin UI behaviours:
 * - animated 7-step delivery diagnostic wired to the real AJAX endpoints
 * - guided setup wizard (saves through the regular admin-post handler)
 * - log list slide-over, filters, clipboard, conditional fields
 *
 * @package Simple_SMTP_DKIM
 */
(function($) {
    'use strict';

    if (!$('.simple-smtp-dkim-wrap').length) {
        return;
    }

    var S = window.simpleSMTPDKIM || {};
    var T = S.strings || {};
    var DIAG_KEYS = ['resolve', 'connect', 'tls', 'auth', 'spf', 'dkim', 'send'];

    function sprintf1(tpl, value) {
        return String(tpl || '').replace('%s', value);
    }

    function delay(ms) {
        return new Promise(function(resolve) { setTimeout(resolve, ms); });
    }

    /* =====================================================================
       Toast
       ===================================================================== */

    var toastTimer = null;
    function showToast(msg) {
        var wrap = $('#ssd-toast-wrap');
        if (!wrap.length) { return; }
        $('#ssd-toast-msg').text(msg);
        wrap.removeClass('ssd-hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() { wrap.addClass('ssd-hidden'); }, 2200);
    }

    /* =====================================================================
       Buttons — loading state
       ===================================================================== */

    function setLoading(btn, loading) {
        btn = $(btn);
        if (loading) {
            btn.prop('disabled', true).addClass('is-loading');
            if (!btn.children('.ssd-spin').length) {
                btn.prepend('<span class="ssd-spin" aria-hidden="true"></span>');
            }
        } else {
            btn.prop('disabled', false).removeClass('is-loading');
            btn.children('.ssd-spin').remove();
        }
    }

    /* =====================================================================
       Modals — open/close, escape, focus trap
       ===================================================================== */

    var openModals = [];

    function openModal(overlay) {
        overlay = $(overlay);
        overlay.data('ssd-last-focus', document.activeElement);
        overlay.removeClass('ssd-hidden');
        openModals.push(overlay);
        $('body').css('overflow', 'hidden');
        var dialog = overlay.find('[role="dialog"]').first();
        (dialog.length ? dialog : overlay).trigger('focus');
    }

    function closeModal(overlay) {
        overlay = $(overlay);
        overlay.addClass('ssd-hidden');
        openModals = openModals.filter(function(m) { return m[0] !== overlay[0]; });
        if (!openModals.length) {
            $('body').css('overflow', '');
        }
        var last = overlay.data('ssd-last-focus');
        if (last && last.focus) { last.focus(); }
    }

    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape' || !openModals.length) { return; }
        var top = openModals[openModals.length - 1];
        if (top.is('#ssd-log-overlay')) {
            closeLogDetail();
        } else {
            closeModal(top);
        }
    });

    // Focus trap inside any visible ssd dialog
    $(document).on('keydown', '.ssd-overlay, .ssd-slideover', function(e) {
        if (e.key !== 'Tab') { return; }
        var focusable = $(this).find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
            .filter(':visible:not(:disabled)');
        if (!focusable.length) { return; }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault(); last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault(); first.focus();
        }
    });

    /* =====================================================================
       Inline field validation
       ===================================================================== */

    var validators = {
        host: function(v) {
            if (!v) { return { ok: false, msg: T.fieldRequired }; }
            if (/^[a-zA-Z0-9][a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(v)) { return { ok: true, msg: '✓' }; }
            if (/^\d{1,3}(\.\d{1,3}){3}$/.test(v)) { return { ok: true, msg: '✓ IP' }; }
            return { ok: false, msg: T.invalidHost };
        },
        port: function(v) {
            var n = parseInt(v, 10);
            if (!v || isNaN(n)) { return { ok: false, msg: T.fieldRequired }; }
            if (n < 1 || n > 65535) { return { ok: false, msg: T.portRange }; }
            var common = { 25: 'SMTP', 465: 'SSL', 587: 'TLS', 2525: 'Alt' };
            return { ok: true, msg: common[n] ? '✓ ' + common[n] : '✓' };
        },
        email: function(v) {
            if (!v) { return { ok: true, msg: '' }; }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? { ok: true, msg: '✓' } : { ok: false, msg: T.invalidEmail };
        },
        required: function(v) {
            return v ? { ok: true, msg: '✓' } : { ok: false, msg: T.fieldRequired };
        }
    };

    function validateField(el) {
        var type = el.data('validate');
        if (!type || !validators[type]) { return; }
        var result = validators[type](el.val());
        var fb = $('#' + el.attr('aria-describedby'));
        if (fb.length) {
            fb.text(result.msg)
              .toggleClass('ok', result.ok)
              .toggleClass('bad', !result.ok && result.msg !== '');
        }
        el.toggleClass('ok', result.ok && !!el.val())
          .toggleClass('bad', !result.ok && !!el.val());
    }

    $(document).on('input change', '[data-validate]', function() {
        validateField($(this));
    });
    $('[data-validate]').each(function() {
        if ($(this).val()) { validateField($(this)); }
    });

    /* =====================================================================
       Conditional visibility
       ===================================================================== */

    // Port auto-adjust when encryption changes (main form)
    var PORT_MAP = { tls: 587, ssl: 465, '': 25 };
    $('#simple_smtp_dkim_secure').on('change', function() {
        var port = PORT_MAP[this.value];
        if (port) { $('#simple_smtp_dkim_port').val(port).trigger('input'); }
    });

    function toggleAuth() {
        $('.ssd-auth-fields').toggle($('#simple_smtp_dkim_auth').is(':checked'));
    }
    $('#simple_smtp_dkim_auth').on('change', toggleAuth);
    if ($('#simple_smtp_dkim_auth').length) { toggleAuth(); }

    function toggleDkim() {
        $('.ssd-dkim-fields').toggle($('#simple_smtp_dkim_dkim_enabled').is(':checked'));
    }
    $('#simple_smtp_dkim_dkim_enabled').on('change', toggleDkim);
    if ($('#simple_smtp_dkim_dkim_enabled').length) { toggleDkim(); }

    // Radio cards: visual selected state
    $(document).on('change', '.ssd-rc input[type="radio"]', function() {
        var name = $(this).attr('name');
        $('.ssd-rc input[name="' + name + '"]').each(function() {
            $(this).closest('.ssd-rc').toggleClass('sel', this.checked);
        });
    });

    // DKIM storage method
    $('input[name="simple_smtp_dkim_dkim_storage_method"]').on('change', function() {
        var val = $('input[name="simple_smtp_dkim_dkim_storage_method"]:checked').val();
        $('.ssd-storage-database').toggle(val === 'database');
        $('.ssd-storage-file').toggle(val === 'file');
    });

    // Advanced tab: uninstall warning banner
    $('#simple_smtp_dkim_delete_on_uninstall').on('change', function() {
        $('#ssd-uninstall-warning').toggleClass('ssd-hidden', !this.checked);
    });

    /* =====================================================================
       OAuth2 — conditional fields
       ===================================================================== */

    function toggleOAuthFields() {
        var providerEl = $('#simple_smtp_dkim_oauth_provider');
        if (!providerEl.length) { return; }

        var provider   = providerEl.val();
        var grantType  = $('#simple_smtp_dkim_oauth_grant_type').val();
        var authMethod = $('input[name="simple_smtp_dkim_oauth_auth_method"]:checked').val();
        var hasProvider = !!provider;

        $('.ssd-oauth-fields').toggle(hasProvider);
        if (!hasProvider) { return; }

        $('.ssd-oauth-microsoft-field').toggle(provider === 'microsoft');
        $('.ssd-oauth-google-field').toggle(provider === 'google' || provider === 'googleapi');
        $('.ssd-oauth-googleapi-field').toggle(provider === 'googleapi');
        $('.ssd-oauth-authcode-field').toggle(grantType === 'authorization_code');
        $('.ssd-oauth-svc-field').toggle(grantType === 'client_credentials' && provider === 'googleapi');
        $('.ssd-oauth-secret-field').toggle(authMethod === 'secret');
        $('.ssd-oauth-cert-field').toggle(authMethod === 'certificate');

        var helpTexts = {
            microsoft: 'smtp.office365.com — 587 (TLS)',
            google:    'smtp.gmail.com — 587 (TLS)',
            googleapi: 'smtp.gmail.com — 587 (TLS) — Google API Client'
        };
        $('#ssd-oauth-provider-help').text(helpTexts[provider] || '');

        $('.ssd-oauth-guide-microsoft').toggle(provider === 'microsoft');
        $('.ssd-oauth-guide-google').toggle(provider === 'google' || provider === 'googleapi');
    }
    $(document).on('change', '#simple_smtp_dkim_oauth_provider, #simple_smtp_dkim_oauth_grant_type, input[name="simple_smtp_dkim_oauth_auth_method"]', toggleOAuthFields);
    toggleOAuthFields();

    /* =====================================================================
       Copy to clipboard
       ===================================================================== */

    $(document).on('click', '.ssd-copy', function() {
        var btn = $(this);
        var target = $('#' + btn.data('copy-target'));
        var text = target.is('textarea, input') ? target.val() : target.text();

        function done() {
            btn.addClass('copied');
            setTimeout(function() { btn.removeClass('copied'); }, 1600);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var ta = $('<textarea>').val(text).appendTo('body').trigger('select');
            try { document.execCommand('copy'); } catch (e) { /* noop */ }
            ta.remove();
            done();
        }
    });

    /* =====================================================================
       Diagnostic engine — drives the 7 steps against real AJAX endpoints
       ===================================================================== */

    function DiagRun(container) {
        this.box = $(container);
        this.aborted = false;
    }

    DiagRun.prototype.step = function(key) {
        return this.box.find('.ssd-diag-step[data-step="' + key + '"]');
    };

    DiagRun.prototype.reset = function() {
        var self = this;
        DIAG_KEYS.forEach(function(key) {
            self.step(key)
                .removeClass('running ok warn err')
                .addClass('idle')
                .find('.ssd-ds-note').text(T.waiting || '…');
        });
    };

    DiagRun.prototype.set = function(key, state, note) {
        var row = this.step(key);
        row.removeClass('idle running ok warn err').addClass(state);
        if (typeof note === 'string' && note !== '') {
            row.find('.ssd-ds-note').text(note);
        } else if (state === 'running') {
            row.find('.ssd-ds-note').text(T.checking || '…');
        }
    };

    DiagRun.prototype.markUnreached = function(fromIndex) {
        for (var i = fromIndex; i < DIAG_KEYS.length; i++) {
            this.set(DIAG_KEYS[i], 'idle', T.notReached || '—');
        }
    };

    // Guess which connection step failed from the server error message.
    function guessFailingStep(message) {
        var m = (message || '').toLowerCase();
        if (/getaddrinfo|could not resolve|name or service|dns|hostname/.test(m)) { return 0; }
        if (/starttls|certificate|ssl|tls/.test(m)) { return 2; }
        if (/auth|535|username|password|credential|login/.test(m)) { return 3; }
        return 1; // generic connection failure
    }

    function collectMailerSettings() {
        var pw = $('#simple_smtp_dkim_password').val();
        var hasSaved = String($('#simple_smtp_dkim_password').data('has-saved-password')) === '1';
        return {
            host: $('#simple_smtp_dkim_host').val(),
            port: $('#simple_smtp_dkim_port').val(),
            secure: $('#simple_smtp_dkim_secure').val(),
            auth: $('#simple_smtp_dkim_auth').is(':checked'),
            username: $('#simple_smtp_dkim_username').val(),
            password: pw,
            useSaved: (!pw && hasSaved)
        };
    }

    /**
     * Run the full diagnostic inside `container`.
     * Resolves with { success, debug } once finished.
     */
    function runDiagnostic(container, settings, recipient) {
        var run = new DiagRun(container);
        run.reset();

        var connPromise = $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_test_connection',
            nonce: S.nonces.test_connection,
            host: settings.host,
            port: settings.port,
            secure: settings.secure,
            auth: settings.auth ? 'true' : 'false',
            username: settings.username,
            password: settings.password,
            use_saved_password: settings.useSaved ? 'true' : 'false'
        }).then(function(resp) {
            return resp;
        }, function() {
            return $.Deferred().resolve({ success: false, data: { message: T.networkError } }).promise();
        });

        var state = { failed: false, debug: '' };

        function connStep(index, dwell, okNote) {
            return function() {
                if (state.failed) { return Promise.resolve(); }
                run.set(DIAG_KEYS[index], 'running');
                return Promise.all([delay(dwell), connPromise]).then(function(results) {
                    var resp = results[1] || {};
                    var data = resp.data || {};
                    if (data.debug) { state.debug = data.debug; }

                    if (resp.success) {
                        var note = okNote;
                        if (index === 2 && !settings.secure) { note = T.tlsNone; }
                        if (index === 3 && !settings.auth) { note = T.authSkipped; }
                        run.set(DIAG_KEYS[index], 'ok', note);
                        return;
                    }
                    var failIdx = guessFailingStep(data.message);
                    if (index < failIdx) {
                        run.set(DIAG_KEYS[index], 'ok', okNote);
                        return;
                    }
                    state.failed = true;
                    state.failMessage = data.message || T.stepFailed;
                    run.set(DIAG_KEYS[index], 'err', state.failMessage);
                    run.markUnreached(index + 1);
                });
            };
        }

        function spfStep() {
            if (state.failed) { return Promise.resolve(); }
            run.set('spf', 'running');
            return Promise.all([delay(700), connPromise]).then(function(results) {
                var resp = results[1] || {};
                var spf = (resp.data || {}).spf_check;
                if (!resp.success) { return; }
                if (spf && spf.found && spf.authorized === true) {
                    run.set('spf', 'ok', spf.message);
                } else if (spf && spf.found) {
                    run.set('spf', 'ok', spf.message);
                } else {
                    run.set('spf', 'warn', (spf && spf.message) || T.spfMissing);
                }
            });
        }

        function dkimStep() {
            if (state.failed) { return Promise.resolve(); }
            run.set('dkim', 'running');
            return delay(650).then(function() {
                var d = S.dkim || {};
                if (d.enabled && d.verified) {
                    run.set('dkim', 'ok', sprintf1(T.dkimActive, (d.selector || 'default') + '._domainkey'));
                } else if (d.enabled) {
                    run.set('dkim', 'warn', T.dkimPending);
                } else {
                    run.set('dkim', 'warn', T.dkimOff);
                }
            });
        }

        function sendStep() {
            if (state.failed) { return Promise.resolve(); }
            run.set('send', 'running');
            var sendPromise = $.post(S.ajaxUrl, {
                action: 'simple_smtp_dkim_send_test_email',
                nonce: S.nonces.send_test_email,
                to_email: recipient,
                use_temp_settings: 'true',
                host: settings.host,
                port: settings.port,
                secure: settings.secure,
                auth: settings.auth ? 'true' : 'false',
                username: settings.username,
                password: settings.password,
                use_saved_password: settings.useSaved ? 'true' : 'false'
            }).then(function(resp) {
                return resp;
            }, function() {
                return $.Deferred().resolve({ success: false, data: { message: T.networkError } }).promise();
            });

            return Promise.all([delay(900), sendPromise]).then(function(results) {
                var resp = results[1] || {};
                var data = resp.data || {};
                if (resp.success) {
                    run.set('send', 'ok', T.sendOk);
                } else {
                    state.failed = true;
                    state.failMessage = data.message || T.stepFailed;
                    run.set('send', 'err', state.failMessage);
                }
            });
        }

        var chain = Promise.resolve()
            .then(connStep(0, 650, T.dnsResolved))
            .then(connStep(1, 750, sprintf1(T.connOk, settings.port)))
            .then(connStep(2, 700, T.tlsOk))
            .then(connStep(3, 850, T.authOk))
            .then(spfStep)
            .then(dkimStep)
            .then(sendStep);

        return chain.then(function() {
            return { success: !state.failed, debug: state.debug, message: state.failMessage || '' };
        });
    }

    /* =====================================================================
       Diagnostic modal (Mailer tab)
       ===================================================================== */

    var diagOverlay = $('#ssd-diag-overlay');
    var diagRunning = false;

    function startDiagModal() {
        if (diagRunning) { return; }
        var recipient = $('#ssd-diag-to').val();
        if (!recipient || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient)) {
            showToast(T.invalidEmail);
            $('#ssd-diag-to').trigger('focus');
            return;
        }
        var settings = collectMailerSettings();
        if (!settings.host) {
            $('#simple_smtp_dkim_host').addClass('bad').trigger('focus');
            return;
        }

        $('#ssd-diag-recipient').text(recipient);
        $('#ssd-diag-summary-ok, #ssd-diag-summary-err, #ssd-diag-debug-toggle, #ssd-diag-rerun').addClass('ssd-hidden');
        $('#ssd-diag-debug').addClass('ssd-hidden').text('');
        $('#ssd-diag-debug-toggle').removeClass('open').attr('aria-expanded', 'false');
        $('#ssd-diag-done').removeClass('done ssd-btn-primary').addClass('ssd-btn-ghost');

        if (diagOverlay.hasClass('ssd-hidden')) { openModal(diagOverlay); }

        diagRunning = true;
        runDiagnostic(diagOverlay, settings, recipient).then(function(result) {
            diagRunning = false;
            if (result.success) {
                $('#ssd-diag-summary-ok').removeClass('ssd-hidden');
            } else {
                $('#ssd-diag-summary-err').removeClass('ssd-hidden');
            }
            if (result.debug) {
                $('#ssd-diag-debug').text(result.debug);
                $('#ssd-diag-debug-toggle').removeClass('ssd-hidden');
            }
            $('#ssd-diag-rerun').removeClass('ssd-hidden');
            $('#ssd-diag-done').addClass('done ssd-btn-primary').removeClass('ssd-btn-ghost');
        });
    }

    $('#ssd-open-diagnostic').on('click', startDiagModal);
    $('#ssd-diag-rerun').on('click', startDiagModal);
    $(document).on('click', '[data-ssd-close="diag"]', function() { closeModal(diagOverlay); });
    diagOverlay.on('click', function(e) {
        if ($(e.target).is(diagOverlay)) { closeModal(diagOverlay); }
    });

    // Debug log toggle
    $('#ssd-diag-debug-toggle').on('click', function() {
        var open = !$(this).hasClass('open');
        $(this).toggleClass('open', open).attr('aria-expanded', open ? 'true' : 'false');
        $('#ssd-diag-debug').toggleClass('ssd-hidden', !open);
    });

    /* =====================================================================
       Setup wizard (Dashboard)
       ===================================================================== */

    var wizOverlay = $('#ssd-wizard-overlay');
    if (wizOverlay.length) {
        var wiz = {
            pane: 0,
            provider: null,
            testPassed: false,
            testRunning: false
        };

        function wizGoTo(pane) {
            wiz.pane = pane;
            wizOverlay.find('.ssd-wiz-pane').removeClass('active');
            wizOverlay.find('.ssd-wiz-pane[data-wiz-pane="' + pane + '"]').addClass('active');

            // dots + bars
            wizOverlay.find('[data-wiz-dot]').each(function() {
                var i = parseInt($(this).data('wiz-dot'), 10);
                $(this).toggleClass('active', i === pane).toggleClass('done', i < pane);
            });
            wizOverlay.find('[data-wiz-bar]').each(function() {
                var i = parseInt($(this).data('wiz-bar'), 10);
                $(this).toggleClass('filled', i < pane);
            });
            $('#ssd-wiz-progress').toggleClass('ssd-hidden', pane === 3);

            $('#ssd-wiz-prev').toggleClass('ssd-hidden', pane === 0 || pane === 3);
            $('#ssd-wiz-next').toggleClass('ssd-hidden', pane === 3);
            $('#ssd-wiz-finish').toggleClass('ssd-hidden', pane !== 3);
            wizUpdateNext();
        }

        function wizUpdateNext() {
            var ok = false;
            if (wiz.pane === 0) {
                ok = !!wiz.provider;
            } else if (wiz.pane === 1) {
                ok = $('#ssd-wiz-host').val() && $('#ssd-wiz-username').val() &&
                     ($('#ssd-wiz-password').val() || wiz.provider !== 'custom');
            } else if (wiz.pane === 2) {
                ok = wiz.testPassed;
            }
            $('#ssd-wiz-next').prop('disabled', !ok);
            $('#ssd-wiz-next-label').text(
                wiz.pane === 2 && !wiz.testPassed ? (T.wizTestFirst || '…') : (T.wizContinue || '…')
            );
        }

        $('#ssd-open-wizard').on('click', function() {
            openModal(wizOverlay);
            wizGoTo(wiz.pane);
        });
        $(document).on('click', '[data-ssd-close="wizard"]', function() { closeModal(wizOverlay); });
        wizOverlay.on('click', function(e) {
            if ($(e.target).is(wizOverlay)) { closeModal(wizOverlay); }
        });

        // Provider selection
        wizOverlay.on('click', '.ssd-prov', function() {
            wizOverlay.find('.ssd-prov').removeClass('sel');
            $(this).addClass('sel');
            wiz.provider = $(this).data('provider');
            var host = $(this).data('host');
            if (host) { $('#ssd-wiz-host').val(host); }
            wizUpdateNext();
        });

        // Credentials inputs gate the Continue button
        wizOverlay.on('input change', '#ssd-wiz-host, #ssd-wiz-username, #ssd-wiz-password', wizUpdateNext);

        // Port auto-adjust in the wizard
        $('#ssd-wiz-secure').on('change', function() {
            var port = PORT_MAP[this.value];
            if (port) { $('#ssd-wiz-port').val(port); }
        });

        $('#ssd-wiz-prev').on('click', function() {
            if (wiz.pane > 0) { wizGoTo(wiz.pane - 1); }
        });
        $('#ssd-wiz-next').on('click', function() {
            if (wiz.pane < 3) { wizGoTo(wiz.pane + 1); }
        });

        // Test pane
        $('#ssd-wiz-run-test').on('click', function() {
            if (wiz.testRunning) { return; }
            wiz.testRunning = true;
            wiz.testPassed = false;
            wizUpdateNext();

            $('#ssd-wiz-test-intro').addClass('ssd-hidden');
            $('#ssd-wiz-test-steps').removeClass('ssd-hidden');

            var settings = {
                host: $('#ssd-wiz-host').val(),
                port: $('#ssd-wiz-port').val(),
                secure: $('#ssd-wiz-secure').val(),
                auth: !!$('#ssd-wiz-username').val(),
                username: $('#ssd-wiz-username').val(),
                password: $('#ssd-wiz-password').val(),
                useSaved: false
            };
            var recipient = $('#ssd-wiz-from-email').val() || S.adminEmail || '';

            runDiagnostic($('#ssd-wiz-test-steps'), settings, recipient).then(function(result) {
                wiz.testRunning = false;
                if (result.success) {
                    wiz.testPassed = true;
                } else {
                    showToast(result.message || T.error);
                    // Let the user adjust and retry.
                    $('#ssd-wiz-test-intro').removeClass('ssd-hidden');
                }
                wizUpdateNext();
            });
        });

        // Finish: copy wizard values into the hidden admin-post form and submit
        $('#ssd-wiz-finish').on('click', function() {
            $('#ssd-wizf-host').val($('#ssd-wiz-host').val());
            $('#ssd-wizf-port').val($('#ssd-wiz-port').val());
            $('#ssd-wizf-secure').val($('#ssd-wiz-secure').val());
            $('#ssd-wizf-username').val($('#ssd-wiz-username').val());
            $('#ssd-wizf-password').val($('#ssd-wiz-password').val());
            $('#ssd-wizf-from-email').val($('#ssd-wiz-from-email').val());
            $('#ssd-wizf-from-name').val($('#ssd-wiz-from-name').val());
            if (!$('#ssd-wiz-username').val()) {
                $('#ssd-wizf-auth').prop('disabled', true);
            }
            $('#ssd-wizard-form')[0].submit();
        });
    }

    /* =====================================================================
       DKIM — generate, view, validate
       ===================================================================== */

    function showDnsBlock(name, value) {
        $('#ssd-dns-name').text(name);
        $('#ssd-dns-value').text(value);
        $('#ssd-dns-block, #ssd-dns-saved-note').removeClass('ssd-hidden');
    }

    $('#ssd-generate-dkim').on('click', function() {
        var btn = $(this);
        var domain = $('#simple_smtp_dkim_dkim_domain').val();
        var selector = $('#simple_smtp_dkim_dkim_selector').val();
        if (!domain || !selector) {
            showToast(T.enterDomain);
            $(domain ? '#simple_smtp_dkim_dkim_selector' : '#simple_smtp_dkim_dkim_domain').trigger('focus');
            return;
        }
        setLoading(btn, true);
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_generate_dkim_keys',
            nonce: S.nonces.generate_dkim,
            domain: domain,
            selector: selector
        }, function(resp) {
            setLoading(btn, false);
            if (resp.success) {
                showDnsBlock(resp.data.dns_record_name, resp.data.dns_record_value);
                $('#ssd-saved-public-key').val((resp.data.dns_record_value || '').replace('v=DKIM1; k=rsa; p=', ''));
                showToast(resp.data.message || 'OK');
            } else {
                showToast((resp.data || {}).message || T.error);
            }
        }).fail(function() {
            setLoading(btn, false);
            showToast(T.networkError);
        });
    });

    $('#ssd-view-dkim').on('click', function() {
        var pubKey = $('#ssd-saved-public-key').val();
        var domain = $('#simple_smtp_dkim_dkim_domain').val();
        var selector = $('#simple_smtp_dkim_dkim_selector').val();
        if (pubKey && domain && selector) {
            showDnsBlock(selector + '._domainkey.' + domain, 'v=DKIM1; k=rsa; p=' + pubKey);
        }
    });

    $('#ssd-validate-dkim').on('click', function() {
        var btn = $(this);
        var result = $('#ssd-dkim-result');
        setLoading(btn, true);
        result.addClass('ssd-hidden');
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_validate_dkim',
            nonce: S.nonces.validate_dkim,
            dkim_domain: $('#simple_smtp_dkim_dkim_domain').val(),
            dkim_selector: $('#simple_smtp_dkim_dkim_selector').val(),
            storage_method: $('input[name="simple_smtp_dkim_dkim_storage_method"]:checked').val() || 'database',
            file_path: $('#simple_smtp_dkim_dkim_file_path').val()
        }, function(resp) {
            setLoading(btn, false);
            result.removeClass('ssd-hidden ok err')
                .addClass(resp.success ? 'ok' : 'err')
                .html((resp.data || {}).message || T.error);
        }).fail(function() {
            setLoading(btn, false);
            result.removeClass('ssd-hidden ok err').addClass('err').text(T.networkError);
        });
    });

    /* =====================================================================
       Logs — slide-over detail, delete all
       ===================================================================== */

    var logOverlay = $('#ssd-log-overlay');
    var logPanel = $('#ssd-log-detail');

    function closeLogDetail() {
        logPanel.addClass('ssd-hidden');
        closeModal(logOverlay);
    }

    $(document).on('click', '.ssd-open-log', function() {
        var row = $(this);
        var isOk = row.data('status') === 'ok';

        logPanel.toggleClass('is-ok', isOk).toggleClass('is-err', !isOk);
        $('#ssd-so-to').text(row.data('to'));
        $('#ssd-so-from').text(row.data('from') || '—');
        $('#ssd-so-subject').text(row.data('subject') || '—');
        $('#ssd-so-date').text(row.data('date'));
        $('#ssd-so-dkim').html(
            String(row.data('dkim')) === '1'
                ? '<span class="ssd-dkim-tag yes">DKIM ✓</span>'
                : '<span class="ssd-dkim-tag no">—</span>'
        );
        $('#ssd-so-error-text').text(row.data('error') || '');

        var frame = $('#ssd-so-frame');
        var iframe = $('#ssd-so-iframe');
        iframe.attr('srcdoc', '');
        if (String(row.data('has-body')) === '1') {
            frame.removeClass('empty');
            $.post(S.ajaxUrl, {
                action: 'simple_smtp_dkim_view_email',
                nonce: S.nonces.view_email,
                log_id: row.data('log-id')
            }, function(resp) {
                if (resp.success) {
                    iframe.attr('srcdoc', (resp.data || {}).email_body || '');
                } else {
                    frame.addClass('empty');
                }
            }).fail(function() {
                frame.addClass('empty');
            });
        } else {
            frame.addClass('empty');
        }

        openModal(logOverlay);
        logPanel.removeClass('ssd-hidden').trigger('focus');
    });

    $(document).on('click', '[data-ssd-close="logdetail"]', closeLogDetail);
    logOverlay.on('click', closeLogDetail);

    $('#ssd-delete-all-logs').on('click', function() {
        if (!window.confirm(T.confirmDelete)) { return; }
        var btn = $(this);
        setLoading(btn, true);
        $.post(S.ajaxUrl, {
            action: 'simple_smtp_dkim_delete_all_logs',
            nonce: S.nonces.delete_logs
        }, function(resp) {
            if (resp.success) {
                window.location.reload();
            } else {
                setLoading(btn, false);
                showToast((resp.data || {}).message || T.error);
            }
        }).fail(function() {
            setLoading(btn, false);
            showToast(T.networkError);
        });
    });

})(jQuery);
