import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

function fadePageTransition(url, action = 'navigate') {
    const body = document.body;
    body.classList.add('page-fade-out');
    setTimeout(() => {
        if (action === 'navigate') {
            window.location.href = url;
        } else if (action === 'replace') {
            window.location.replace(url);
        }
    }, 180);
}

function initSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    if (!sidebar || !toggle) return;

    const STORAGE_KEY = 'sp_sidebar_expanded';
    const isExpanded = localStorage.getItem(STORAGE_KEY) === '1';
    sidebar.classList.toggle('is-expanded', isExpanded);
    toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

    toggle.addEventListener('click', () => {
        const expanded = sidebar.classList.toggle('is-expanded');
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        localStorage.setItem(STORAGE_KEY, expanded ? '1' : '0');
    });
}

function initVariableExpenseForm() {
    const toggle = document.getElementById('add-expense-toggle');
    const form = document.getElementById('variable-expense-form');
    if (!toggle || !form) return;

    toggle.addEventListener('click', () => {
        const isOpen = form.classList.toggle('is-open');
        toggle.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
            form.querySelector('select, input')?.focus();
        }
    });

    const categorySelect = document.getElementById('variable-category');
    const amountStep = document.getElementById('variable-amount-step');
    const amountInput = document.getElementById('variable-amount');
    const uploadStep = document.getElementById('variable-upload-step');

    if (categorySelect && amountStep) {
        categorySelect.addEventListener('change', () => {
            const hasCategory = categorySelect.value.trim() !== '';
            amountStep.classList.toggle('is-visible', hasCategory);
            if (!hasCategory) {
                uploadStep?.classList.remove('is-visible');
            } else {
                amountInput?.focus();
            }
        });
    }

    if (amountInput && uploadStep) {
        amountInput.addEventListener('input', () => {
            const hasAmount = parseFloat(amountInput.value) > 0;
            uploadStep.classList.toggle('is-visible', hasAmount);
        });
    }
}

function initStatsToggle() {
    const toggle = document.getElementById('stats-toggle');
    const charts = document.getElementById('stats-charts');
    if (!toggle || !charts) return;

    toggle.addEventListener('click', () => {
        const isOpen = charts.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}

function initSurplusForm() {
    const toggle = document.getElementById('add-surplus-toggle');
    const form = document.getElementById('surplus-form');
    if (!toggle || !form) return;

    toggle.addEventListener('click', () => {
        const isOpen = form.style.display !== 'none';
        form.style.display = isOpen ? 'none' : 'block';
        toggle.classList.toggle('is-open', !isOpen);
        toggle.setAttribute('aria-expanded', (!isOpen).toString());
        if (!isOpen) {
            form.querySelector('input')?.focus();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('page-fade-in');
    initSidebar();
    initVariableExpenseForm();
    initStatsToggle();
    initSurplusForm();

    document.querySelectorAll('a[href]').forEach((link) => {
        // Links with their own Alpine click handling (e.g. the header
        // notification/message dropdown toggles) manage their own click
        // behavior. Attaching this global handler too meant BOTH fired:
        // Alpine opened the panel, then this listener forced a navigation
        // to the href a moment later — so the dropdown appeared to never
        // open and the panel content never showed.
        if (link.hasAttribute('data-no-fade') || link.hasAttribute('@click.prevent')) return;

        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href') || '';
            if (href.startsWith('#') || href.startsWith('javascript:')) return;
            if (href.startsWith('http://') || href.startsWith('https://')) return;
            event.preventDefault();
            fadePageTransition(href);
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const submitButton = form.querySelector('button[type="submit"]');
            const fields = form.querySelectorAll('input, select, textarea');
            let hasError = false;

            fields.forEach((field) => {
                const value = field.value.trim();
                const name = field.name;
                const errorNode = form.querySelector(`.error-${name}`) || form.querySelector(`[data-error-for="${name}"]`);

                if (field.hasAttribute('required') && !value) {
                    hasError = true;
                    field.classList.add('is-invalid');
                    if (errorNode) errorNode.textContent = 'Ce champ est requis.';
                } else {
                    field.classList.remove('is-invalid');
                    if (errorNode) errorNode.textContent = '';
                }
            });

            if (hasError) {
                event.preventDefault();
                submitButton?.setAttribute('disabled', 'disabled');
                setTimeout(() => submitButton?.removeAttribute('disabled'), 1200);
            }
        });
    });
});