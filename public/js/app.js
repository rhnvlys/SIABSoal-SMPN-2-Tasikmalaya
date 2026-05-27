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

        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.classList.contains('is-loading')) {
                    btn.classList.add('is-loading');
                    btn.innerHTML = '<span class="btn-text">' + btn.innerHTML + '</span>';
                    btn.style.minWidth = btn.offsetWidth + 'px';
                }
            });
        });

        document.querySelectorAll('a[data-loading]').forEach(function (link) {
            link.addEventListener('click', function () {
                link.classList.add('is-loading');
                link.innerHTML = '<span class="btn-text">' + link.innerHTML + '</span>';
            });
        });
    });

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
