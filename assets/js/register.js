import '../styles/page/register.scss';

document.addEventListener("DOMContentLoaded", function () {
    const togglePassword = document.querySelectorAll('.toggle-password');
    togglePassword.forEach(toggle => {
        toggle.addEventListener('click', function (e) { // Get the target input
            const passwordField = this.previousElementSibling; // Sélectionne le champ de mot de passe précédent
            if (passwordField) { // Toggle the type attribute
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                // Toggle the eye / eye slash icon
                this.classList.toggle('lni-eye-slash');
            }
        });
    });

    const form = document.querySelector('form');
    form.addEventListener('submit', function (e) {
        const password = document.getElementById('plainPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
        }
    });
});