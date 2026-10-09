import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

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

document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
        return;
    }

    const name = input.closest('.file-field')?.querySelector('[data-file-name]');

    if (name) {
        name.textContent = input.files?.[0]?.name || 'Aucun fichier';
    }
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

    document.querySelectorAll('.file-example input[type="file"]').forEach((input) => {
        if (input.dataset.bound) {
            return;
        }
        input.dataset.bound = '1';
        input.addEventListener('change', () => {
            const label = input.parentElement.querySelector('[data-file-label]');
            const name = input.files && input.files[0] ? input.files[0].name : '';
            if (!label) {
                return;
            }
            label.textContent = name || label.dataset.empty || label.textContent;
            input.parentElement.classList.toggle('is-filled', name !== '');
        });
    });

    const ios = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    if (ios && !standalone) {
        document.querySelectorAll('[data-pwa-ios]').forEach((hint) => hint.classList.remove('d-none'));
    }

    if (import.meta.env.PROD && 'serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    const syncNavFade = () => {
        document.querySelectorAll('.app-nav-scroll').forEach((scroller) => {
            const frame = scroller.closest('.app-nav-frame');

            if (!frame) {
                return;
            }

            const box = scroller.getBoundingClientRect();
            const partial = [...scroller.querySelectorAll('.app-nav-link, .app-nav-label')].find((item) => {
                const rect = item.getBoundingClientRect();

                return rect.top < box.bottom - 1 && rect.bottom > box.bottom + 1;
            });
            const cover = partial ? Math.ceil(box.bottom - partial.getBoundingClientRect().top) : 0;
            const overflow = scroller.scrollHeight > scroller.clientHeight + 2;
            const atEnd = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4;

            frame.style.setProperty('--nav-cover', `${cover}px`);
            frame.classList.toggle('is-overflowing', overflow && !atEnd && cover > 1);
        });
    };

    document.querySelectorAll('.app-nav-scroll').forEach((scroller) => {
        scroller.addEventListener('scroll', syncNavFade, { passive: true });
    });
    window.addEventListener('resize', syncNavFade);
    syncNavFade();
});
