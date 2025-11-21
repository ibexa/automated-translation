((doc) => {
    const translationModals = doc.querySelectorAll('.ibexa-translation');

    // Store the user's last checked state per modal, so that user's preference can be restored when checkbox is re-enabled
    const userCheckedStateMap = new Map();

    translationModals.forEach((modal) => {
        const translatorSelect = modal.querySelector('.ibexa-automated-translation-services-container__input');
        const baseLanguageSelect = modal.querySelector('.ibexa-translation__language-wrapper--base-language');
        const languageSelect = modal.querySelector('.ibexa-translation__language-wrapper--language');

        // Initialize userCheckedState to true for this modal
        userCheckedStateMap.set(modal, true);

        const handleLanguageChange = () => {
            const translationCheckbox = translatorSelect.closest('.ibexa-input--checkbox');
            if (translationCheckbox) {
                const supportedLanguages = translationCheckbox.getAttribute(
                    `data-supported-translation-languages-${translationCheckbox.value}`
                );
                const shouldBeEnabled = (
                    baseLanguageSelect.value  &&
                    supportedLanguages.includes(baseLanguageSelect.value) &&
                    supportedLanguages.includes(languageSelect.value)
                );

                // If checkbox is currently enabled, store its state before potentially disabling it
                if (!translationCheckbox.disabled) {
                    userCheckedStateMap.set(modal, translationCheckbox.checked);
                }

                translationCheckbox.disabled = !shouldBeEnabled;

                // Restore the user's last checked state, whether it was checked or unchecked
                if (shouldBeEnabled) {
                    const storedState = userCheckedStateMap.get(modal);
                    translationCheckbox.checked = storedState == undefined ? true : storedState;
                } else {
                    translationCheckbox.checked = false;
                }
            }

            const translationSelectWrapper = translatorSelect.closest('.ibexa-dropdown');
            if (translationSelectWrapper) {
                translationSelectWrapper.classList.toggle('ibexa-dropdown--disabled', !baseLanguageSelect.value);
            }
        };

        if (baseLanguageSelect && languageSelect && translatorSelect) {
            // Initialize the checkbox state and visibility on page load ( enable if possible by current language selections)
            const translationCheckbox = translatorSelect.closest('.ibexa-input--checkbox');
            if (translationCheckbox && !translationCheckbox.disabled) {
                translationCheckbox.checked = true;
            }
            handleLanguageChange();

            baseLanguageSelect.addEventListener('change', handleLanguageChange);
            languageSelect.addEventListener('change', handleLanguageChange);
        }
    });
}) (document);
