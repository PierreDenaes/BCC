import '../styles/page/profile.scss';

document.addEventListener('DOMContentLoaded', function () {
    const isCompanyCheckbox = document.getElementById('profile_isCompany');
    const companyFields = document.getElementById('companyFields');

    if (isCompanyCheckbox && companyFields) {
        function toggleCompanyFields() {
            companyFields.classList.toggle('d-none', !isCompanyCheckbox.checked);
        }
        toggleCompanyFields();
        isCompanyCheckbox.addEventListener("change", toggleCompanyFields);
    }

    // Gestion des flash messages
    const flashMessage = document.querySelector(".alert-success");
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.opacity = "0";
            setTimeout(() => flashMessage.remove(), 500);
        }, 3000);
    }

    // Aperçu dynamique de l'avatar
    const avatarInput = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', event => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => avatarPreview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });
    }
});