import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });

    document.querySelectorAll('[data-open-on-load]').forEach((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });
});
