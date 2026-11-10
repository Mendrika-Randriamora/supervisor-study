import 'bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || 'Confirmer cette action ?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-no-copy]').forEach((element) => {
        ['copy', 'cut', 'paste'].forEach((eventName) => {
            element.addEventListener(eventName, (event) => event.preventDefault());
        });

        element.addEventListener('contextmenu', (event) => event.preventDefault());

        element.addEventListener('drop', (event) => event.preventDefault());
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.querySelector('i')?.classList.toggle('bi-eye', !isPassword);
            button.querySelector('i')?.classList.toggle('bi-eye-slash', isPassword);
        });
    });

    document.querySelectorAll('[data-file-preview]').forEach((input) => {
        const target = document.querySelector(input.dataset.filePreview);
        if (!target) return;

        input.addEventListener('change', () => {
            target.innerHTML = '';
            Array.from(input.files || []).forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.className = 'img-thumbnail';
                img.style.maxHeight = '110px';
                img.style.width = 'auto';
                target.appendChild(img);
            });
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });
});
