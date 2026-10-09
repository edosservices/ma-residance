import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-password]');

    if (!button) {
        return;
    }

    const input = document.getElementById(button.dataset.togglePassword);

    if (!input) {
        return;
    }

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    button.querySelector('i')?.classList.toggle('bi-eye', !show);
    button.querySelector('i')?.classList.toggle('bi-eye-slash', show);
});

document.addEventListener('input', (event) => {
    const filter = event.target.closest('[data-thread-filter]');

    if (!filter) {
        return;
    }

    const query = filter.value.trim().toLocaleLowerCase();

    filter.closest('.inbox-list')?.querySelectorAll('[data-thread-row]').forEach((row) => {
        row.hidden = query !== '' && !row.textContent.toLocaleLowerCase().includes(query);
    });
});

let deferredInstall = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstall = event;
    document.querySelectorAll('[data-pwa-install]').forEach((button) => {
        button.hidden = false;
        button.classList.remove('d-none');
    });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-pwa-install]');

    if (!button || !deferredInstall) {
        return;
    }

    deferredInstall.prompt();
    await deferredInstall.userChoice;
    deferredInstall = null;
    document.querySelectorAll('[data-pwa-install]').forEach((item) => {
        item.hidden = true;
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });

    document.querySelectorAll('[data-open-on-load]').forEach((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });

    const ios = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    if (ios && !standalone) {
        document.querySelectorAll('[data-pwa-ios]').forEach((hint) => hint.classList.remove('d-none'));
    }

    if (import.meta.env.PROD && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
});
