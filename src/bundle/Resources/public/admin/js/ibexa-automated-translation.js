((doc) => {
    const translationModals = doc.querySelectorAll('.ibexa-translation');

    translationModals.forEach((modal) => {
        const translatorSelect = modal.querySelector('.ibexa-automated-translation-services-container__input');
        const baseLanguageSelect = modal.querySelector('.ibexa-translation__language-wrapper--base-language');
        const languageSelect = modal.querySelector('.ibexa-translation__language-wrapper--language');

        const handleLanguageChange = () => {
            const translationCheckbox = translatorSelect.closest('.ibexa-input--checkbox');
            if (translationCheckbox) {
                const supportedLanguages = translationCheckbox.getAttribute(
                    `data-supported-translation-languages-${translationCheckbox.value}`
                );
                translationCheckbox.disabled = !(
                    baseLanguageSelect.value  &&
                    supportedLanguages.includes(baseLanguageSelect.value) &&
                    supportedLanguages.includes(languageSelect.value)
                );
                translationCheckbox.checked = !translationCheckbox.disabled;

            }

            const translationSelectWrapper = translatorSelect.closest('.ibexa-dropdown');
            if (translationSelectWrapper) {
                translationSelectWrapper.classList.toggle('ibexa-dropdown--disabled', !baseLanguageSelect.value);
            }
        };

        if (baseLanguageSelect && languageSelect && translatorSelect) {
            baseLanguageSelect.addEventListener('change', handleLanguageChange);
            languageSelect.addEventListener('change', handleLanguageChange);
        }
    });
}) (document);
