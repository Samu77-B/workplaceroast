(function () {
    'use strict';

    var PARTNER_DASHBOARD_URL = 'https://workplaceroast.com/pwa/';
    var WEB3FORMS_ACCESS_KEY = '2edfa525-3092-4690-875d-e41ed318314f';

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showFieldError(fieldName, message, prefix) {
        prefix = prefix || '';
        var field;
        if (fieldName === 'signin_email') {
            field = document.getElementById('signin_email');
        } else {
            field = document.querySelector('#requestAccountForm [name="' + fieldName + '"]');
        }
        var id = prefix + fieldName + 'Error';
        var errorEl = document.getElementById(id);
        if (field) field.style.borderColor = '#dc3545';
        if (errorEl) errorEl.textContent = message;
    }

    function clearFieldError(fieldName, prefix) {
        prefix = prefix || '';
        var field;
        if (fieldName === 'signin_email') {
            field = document.getElementById('signin_email');
        } else {
            field = document.querySelector('#requestAccountForm [name="' + fieldName + '"]');
        }
        var id = prefix + fieldName + 'Error';
        var errorEl = document.getElementById(id);
        if (field) field.style.borderColor = '';
        if (errorEl) errorEl.textContent = '';
    }

    function showRequestMessage(text, type) {
        var el = document.getElementById('requestFormMessage');
        if (!el) return;
        el.textContent = text;
        el.className = 'form__message ' + type;
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function initTabs() {
        var tabs = document.querySelectorAll('.partner__tab');
        var panels = document.querySelectorAll('.partner__panel');
        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var panelId = tab.getAttribute('data-panel');
                tabs.forEach(function (t) {
                    var active = t === tab;
                    t.classList.toggle('partner__tab--active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    var isSignin = p.id === 'panel-signin';
                    var show = (panelId === 'signin' && isSignin) || (panelId === 'request' && p.id === 'panel-request');
                    p.classList.toggle('partner__panel--active', show);
                    p.hidden = !show;
                });
            });
        });
    }

    function initSignIn() {
        var form = document.getElementById('signInForm');
        if (!form) return;

        var emailInput = document.getElementById('signin_email');
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                clearFieldError('signin_email', '');
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var email = emailInput ? emailInput.value.trim() : '';
            clearFieldError('signin_email', '');

            if (email && !isValidEmail(email)) {
                showFieldError('signin_email', 'Please enter a valid email address', '');
                return;
            }

            var url = PARTNER_DASHBOARD_URL;
            if (email) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'email=' + encodeURIComponent(email);
            }
            window.location.assign(url);
        });
    }

    function initRequestForm() {
        var form = document.getElementById('requestAccountForm');
        if (!form) return;

        form.querySelectorAll('input, textarea, select').forEach(function (el) {
            el.addEventListener('input', function () {
                clearFieldError(el.name, 'req_');
            });
            el.addEventListener('blur', function () {
                validateRequestField(el);
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var ok = true;
            ['name', 'email', 'business', 'message'].forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                if (el && !validateRequestField(el)) {
                    ok = false;
                }
            });
            var emailEl = form.querySelector('[name="email"]');
            if (emailEl && emailEl.value.trim() && !isValidEmail(emailEl.value.trim())) {
                showFieldError('email', 'Please enter a valid email address', 'req_');
                ok = false;
            }
            if (!ok) {
                showRequestMessage('Please fix the errors in the form.', 'error');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            var originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending…';

            var fd = new FormData(form);
            var payload = {
                access_key: WEB3FORMS_ACCESS_KEY,
                subject: 'Partner account request — ' + (fd.get('business') || 'Workplace Roast'),
                name: fd.get('name'),
                email: fd.get('email'),
                business: fd.get('business'),
                phone: fd.get('phone') || 'Not provided',
                plan: fd.get('plan') || 'Not specified',
                message: fd.get('message'),
                from_name: fd.get('name'),
                from_email: fd.get('email')
            };

            fetch('https://api.web3forms.com/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to send');
                    });
                })
                .then(function () {
                    showRequestMessage('Thank you! We have received your request and will be in touch soon.', 'success');
                    form.reset();
                    form.querySelectorAll('.form__error').forEach(function (span) {
                        span.textContent = '';
                    });
                    form.querySelectorAll('.form__input, .form__textarea, .form__select').forEach(function (field) {
                        field.style.borderColor = '';
                    });
                })
                .catch(function (err) {
                    console.error(err);
                    showRequestMessage('Something went wrong. Please try again or email info@workplaceroast.com.', 'error');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });
    }

    function validateRequestField(field) {
        var name = field.name;
        var value = (field.value || '').trim();
        clearFieldError(name, 'req_');

        if (name === 'name' || name === 'business' || name === 'message') {
            if (field.hasAttribute('required') && !value) {
                showFieldError(name, 'This field is required', 'req_');
                return false;
            }
        }
        if (name === 'message' && value.length > 0 && value.length < 10) {
            showFieldError(name, 'Please enter at least 10 characters', 'req_');
            return false;
        }
        if (name === 'email') {
            if (!value) {
                showFieldError(name, 'This field is required', 'req_');
                return false;
            }
            if (!isValidEmail(value)) {
                showFieldError(name, 'Please enter a valid email address', 'req_');
                return false;
            }
        }
        return true;
    }


    document.addEventListener('DOMContentLoaded', function () {
        var header = document.getElementById('header');
        if (header && document.body.classList.contains('page-partner')) {
            header.classList.add('scrolled');
        }
        initTabs();
        initSignIn();
        initRequestForm();
    });
})();
