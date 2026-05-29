/**
 * SIABSoal SMPN 2 Tasikmalaya - App Scripts
 * Theme toggle, font size toggle, mobile sidebar, dropdown, alerts, and loading state.
 */

(function () {
    'use strict';

    const THEME_KEY = 'siabsoal_theme';
    const FONT_KEY = 'siabsoal_font_size';

    function getStoredTheme() {
        return localStorage.getItem(THEME_KEY) || 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(THEME_KEY, theme);

        const icon = document.getElementById('theme-icon');
        if (icon) {
            icon.textContent = theme === 'dark' ? 'Light' : 'Dark';
        }
    }

    function getStoredFontSize() {
        return localStorage.getItem(FONT_KEY) || 'normal';
    }

    function applyFontSize(size) {
        document.documentElement.setAttribute('data-font-size', size);
        localStorage.setItem(FONT_KEY, size);

        const label = document.getElementById('font-size-label');
        if (label) {
            label.textContent = size === 'besar' ? 'Aa+' : 'Aa';
        }
    }

    applyTheme(getStoredTheme());
    applyFontSize(getStoredFontSize());

    document.addEventListener('DOMContentLoaded', function () {
        const themeBtn = document.getElementById('btn-toggle-theme');
        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                applyTheme(getStoredTheme() === 'dark' ? 'light' : 'dark');
            });
        }

        const fontBtn = document.getElementById('btn-toggle-font');
        if (fontBtn) {
            fontBtn.addEventListener('click', function () {
                applyFontSize(getStoredFontSize() === 'besar' ? 'normal' : 'besar');
            });
        }

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const toggleBtn = document.getElementById('btn-toggle-sidebar');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        const dropdownBtn = document.getElementById('user-dropdown-btn');
        const dropdownMenu = document.getElementById('user-dropdown-menu');

        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function () {
                dropdownMenu.classList.remove('show');
            });
        }

        document.querySelectorAll('.alert .alert-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const alert = this.closest('.alert');
                if (alert) alert.style.display = 'none';
            });
        });

        document.querySelectorAll('.alert-success').forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity .3s';
                alert.style.opacity = '0';
                setTimeout(function () {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
                    event.preventDefault();
                    showActionConfirm({
                        title: form.dataset.confirmTitle || 'Konfirmasi',
                        message: form.dataset.confirm,
                        confirmText: form.dataset.confirmButton || 'Ya, proses',
                        onConfirm: function () {
                            form.dataset.confirmed = 'true';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                applyLoadingToForm(form);
                                form.submit();
                            }
                        }
                    });
                    return;
                }

                if (form.dataset.confirmed === 'true') {
                    delete form.dataset.confirmed;
                }

                if (form.dataset.loading !== undefined) {
                    applyLoadingToForm(form);
                }
            });
        });

        document.querySelectorAll('a[data-loading]').forEach(function (link) {
            link.addEventListener('click', function () {
                applyLoadingToButton(link, link.dataset.loadingText || 'Mengexport...');
            });
        });
    });

    function applyLoadingToForm(form) {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;

        applyLoadingToButton(btn, form.dataset.loadingText || btn.dataset.loadingText || 'Memproses...');
    }

    function applyLoadingToButton(element, text) {
        if (element.classList.contains('is-loading')) return;

        element.style.minWidth = element.offsetWidth + 'px';
        element.classList.add('is-loading');
        element.setAttribute('aria-disabled', 'true');
        element.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>' + text + '</span>';

        if ('disabled' in element) {
            element.disabled = true;
        }
    }

    function showActionConfirm(options) {
        const modal = document.getElementById('modal-confirm-action');
        const titleEl = document.getElementById('confirm-action-title');
        const messageEl = document.getElementById('confirm-action-message');
        const confirmBtn = document.getElementById('btn-confirm-action');
        const cancelBtn = document.getElementById('btn-cancel-action');

        if (!modal || !confirmBtn) {
            if (options.onConfirm) options.onConfirm();
            return;
        }

        if (titleEl) titleEl.innerHTML = '<i class="bi bi-question-circle-fill text-primary" style="margin-right:6px"></i>' + options.title;
        if (messageEl) messageEl.textContent = options.message;
        confirmBtn.textContent = options.confirmText || 'Ya, proses';

        modal.classList.add('active');

        const cleanup = function () {
            modal.classList.remove('active');
            confirmBtn.onclick = null;
            if (cancelBtn) cancelBtn.onclick = null;
        };

        confirmBtn.onclick = function () {
            cleanup();
            if (options.onConfirm) options.onConfirm();
        };

        if (cancelBtn) {
            cancelBtn.onclick = cleanup;
        }
    }

    window.confirmDelete = function (formId, itemName) {
        const modal = document.getElementById('modal-confirm-delete');
        const nameEl = document.getElementById('delete-item-name');
        const confirmBtn = document.getElementById('btn-confirm-delete');

        if (nameEl) nameEl.textContent = itemName || 'item ini';
        if (modal) modal.classList.add('active');

        if (confirmBtn) {
            confirmBtn.onclick = function () {
                const form = document.getElementById(formId);
                if (form) form.submit();
            };
        }
    };

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    };
})();
