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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });

    document.querySelectorAll('[data-open-on-load]').forEach((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });
});
