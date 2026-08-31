
import Alpine from 'alpinejs';

function generateSecurePassword(length = 12) {
    const lower = 'abcdefghijklmnopqrstuvwxyz';
    const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%&*_-?';
    const all = lower + upper + numbers + symbols;

    const pick = (chars) => chars[Math.floor(Math.random() * chars.length)];

    const required = [pick(lower), pick(upper), pick(numbers), pick(symbols)];
    while (required.length < length) {
        required.push(pick(all));
    }

    return required.sort(() => Math.random() - 0.5).join('');
}

document.addEventListener('alpine:init', () => {
    Alpine.data('passwordFields', (passwordId, confirmId) => ({
        visible: false,
        generate() {
            const password = generateSecurePassword(12);
            const passwordInput = document.getElementById(passwordId);
            const confirmInput = document.getElementById(confirmId);

            if (passwordInput) {
                passwordInput.value = password;
            }
            if (confirmInput) {
                confirmInput.value = password;
            }

            this.visible = true;
        },
        toggle() {
            this.visible = !this.visible;
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
