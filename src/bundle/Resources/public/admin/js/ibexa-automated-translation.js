((doc) => {
    const translationModals = doc.querySelectorAll('.ibexa-translation');

    translationModals.forEach((modal) => {
        const translatorSelect = modal.querySelector('.ibexa-automated-translation-services-container__input');
        const baseLanguageSelect = modal.querySelector('.ibexa-translation__language-wrapper--base-language');

        if (baseLanguageSelect && translatorSelect) {
            baseLanguageSelect.addEventListener('change', () => {
                translatorSelect.disabled = !baseLanguageSelect.value;

                const translationSelectWrapper = translatorSelect.closest('.ibexa-dropdown');

                if (translationSelectWrapper) {
                    translationSelectWrapper.classList.toggle('ibexa-dropdown--disabled', !baseLanguageSelect.value);
                }
            });
        }
    });
})(document);
