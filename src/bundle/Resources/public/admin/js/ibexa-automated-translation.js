((doc) => {
    const translationModals = doc.querySelectorAll('.ibexa-translation');

    // Store the user's last checked state per modal (for checkbox mode)
    const userCheckedStateMap = new Map();

    translationModals.forEach((modal) => {
        const translatorSelect = modal.querySelector('.ibexa-automated-translation-services-container__input');
        const baseLanguageSelect = modal.querySelector('.ibexa-translation__language-wrapper--base-language');
        const languageSelect = modal.querySelector('.ibexa-translation__language-wrapper--language');

        userCheckedStateMap.set(modal, true);

        const handleLanguageChange = () => {
            const baseLang = baseLanguageSelect?.value;
            const targetLang = languageSelect?.value;
            const translationCheckbox = translatorSelect.closest('.ibexa-input--checkbox');

            if (translationCheckbox) {
                const supportedLanguages = translationCheckbox.getAttribute(
                    `data-supported-translation-languages-${translationCheckbox.value}`
                ) || '';

                const supportedList = supportedLanguages.split(' ');

                const shouldBeEnabled =
                    baseLang &&
                    targetLang &&
                    supportedList.includes(baseLang) &&
                    supportedList.includes(targetLang);

                // Store user state before disabling
                if (!translationCheckbox.disabled) {
                    userCheckedStateMap.set(modal, translationCheckbox.checked);
                }

                translationCheckbox.disabled = !shouldBeEnabled;

                if (shouldBeEnabled) {
                    const storedState = userCheckedStateMap.get(modal);
                    translationCheckbox.checked = storedState === undefined ? true : storedState;
                } else {
                    translationCheckbox.checked = false;
                }
            }

            const dropdownWrapper = translatorSelect.closest('.ibexa-dropdown');
            const NO_SERVICE = 'no_service';

            if (dropdownWrapper) {
                const dropdownInstance = ibexa.helpers.objectInstances.getInstance(dropdownWrapper);

                if (!dropdownInstance) {
                    return;
                }

                const options = dropdownInstance.itemsListContainer.querySelectorAll('.ibexa-dropdown__item');

                let hasAnyEnabled = false;

                options.forEach((option) => {
                    let value = option.dataset.value;

                    if (!value) {
                        return;
                    }

                    // Always allow "no-service"
                    if (value === NO_SERVICE) {
                        dropdownInstance.enableOption(value);
                        hasAnyEnabled = true;
                        return;
                    }

                    const supportedLanguagesAttr = translatorSelect.getAttribute(
                        `data-supported-translation-languages-${value}`
                    ) || '';

                    const supportedList = supportedLanguagesAttr.split(' ');

                    const isEnabled =
                        baseLang &&
                        targetLang &&
                        supportedList.includes(baseLang) &&
                        supportedList.includes(targetLang);

                    if (isEnabled) {
                        dropdownInstance.enableOption(value);
                        hasAnyEnabled = true;
                    } else {
                        dropdownInstance.disableOption(value);
                    }
                });

                // Disable whole dropdown if nothing is selectable
                dropdownWrapper.classList.toggle('ibexa-dropdown--disabled', !hasAnyEnabled);

                const selectElement = translatorSelect;
                const currentValue = selectElement.value;

                if (currentValue && currentValue !== NO_SERVICE) {
                    const options = dropdownInstance.itemsListContainer.querySelectorAll('.ibexa-dropdown__item');

                    const currentOption = [...options].find(
                        (opt) => opt.dataset.value === currentValue
                    );

                    const isNowDisabled = dropdownInstance.isOptionDisabled(currentValue);

                    if (isNowDisabled) {
                        dropdownInstance.clearCurrentSelection(false);
                        dropdownInstance.selectOption(NO_SERVICE);
                    }
                }
            }
        };

        if (baseLanguageSelect && languageSelect && translatorSelect) {
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
