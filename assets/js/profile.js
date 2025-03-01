import '../styles/page/profile.scss';
document.addEventListener('DOMContentLoaded', function () {
    const isCompanyCheckbox = document.getElementById('profile_isCompany');
    const companyNameInput = document.getElementById('profile_companyName');
    const siretNumberInput = document.getElementById('profile_siretNumber');

    let flashMessage = document.querySelector(".alert-success");
    if (flashMessage) {
        setTimeout(function () {
            flashMessage.style.transition = "opacity 0.5s";
            flashMessage.style.opacity = "0";
            setTimeout(() => flashMessage.remove(), 500);
        }, 3000);
    }

    function toggleCompanyFields() {
        if (!companyNameInput || !siretNumberInput) {
            return; // Empêche l'erreur si les champs ne sont pas présents
        }

        const companyNameField = companyNameInput.closest('.mb-3');
        const siretNumberField = siretNumberInput.closest('.mb-3');

        if (isCompanyCheckbox.checked) {
            companyNameField.style.display = '';
            siretNumberField.style.display = '';
        } else {
            companyNameField.style.display = 'none';
            siretNumberField.style.display = 'none';
        }
    }

    if (isCompanyCheckbox) {
        toggleCompanyFields();
        isCompanyCheckbox.addEventListener('change', toggleCompanyFields);
    }
});