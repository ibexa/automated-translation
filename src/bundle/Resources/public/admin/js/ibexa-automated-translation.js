((doc) => {
    const translationModals = doc.querySelectorAll('.ibexa-translation');

    // Store the user's last checked state per modal (for checkbox mode)
    const userCheckedStateMap = new Map();

    translationModals.forEach((modal) => {
        const translatorSelect = modal.querySelector('.ibexa-automated-translation-services-container__input');
        const baseLanguageSelect = modal.querySelector('.ibexa-translation__language-wrapper--base-language');
        const languageSelect = modal.querySelector('.ibexa-translation__language-wrapper--language');

        userCheckedStateMap.set(modal, true);

        const getSupportedLanguages = (element, value) => {
            const attr = element.getAttribute(
                `data-supported-translation-languages-${value}`
            ) || '';

            return new Set(attr ? attr.trim().split(/\s+/) : []);
        };

        const isLanguageSupported = (supportedSet, baseLang, targetLang) => {
            return (
                baseLang &&
                targetLang &&
                supportedSet.has(baseLang) &&
                supportedSet.has(targetLang)
            );
        };

        const updateCheckbox = (modal, translatorSelect, baseLang, targetLang) => {
            const checkbox = translatorSelect.closest('.ibexa-input--checkbox');

            if (!checkbox) {
                return;
            }

            const supportedSet = getSupportedLanguages(checkbox, checkbox.value);
            const shouldEnable = isLanguageSupported(supportedSet, baseLang, targetLang);

            if (!checkbox.disabled) {
                userCheckedStateMap.set(modal, checkbox.checked);
            }

            checkbox.disabled = !shouldEnable;

            if (shouldEnable) {
                const stored = userCheckedStateMap.get(modal);
                
                checkbox.checked = stored === undefined ? true : stored;
            } else {
                checkbox.checked = false;
            }
        };

        const updateDropdown = (translatorSelect, baseLang, targetLang) => {
            const dropdownWrapper = translatorSelect.closest('.ibexa-dropdown');
            const NO_SERVICE = 'no_service';

            if (!dropdownWrapper) {
                return;
            }

            const dropdown = ibexa.helpers.objectInstances.getInstance(dropdownWrapper);
            
            if (!dropdown) {
                return;
            }

            const options = dropdown.itemsListContainer.querySelectorAll('.ibexa-dropdown__item');

            options.forEach((option) => {
                const value = option.dataset.value;
                if (!value) return;

                if (value === NO_SERVICE) {
                    dropdown.enableOption(value);
                    return;
                }

                const supportedSet = getSupportedLanguages(translatorSelect, value);
                const isEnabled = isLanguageSupported(supportedSet, baseLang, targetLang);

                if (isEnabled) {
                    dropdown.enableOption(value);
                } else {
                    dropdown.disableOption(value);
                }
            });

            const currentValue = translatorSelect.value;

            if (currentValue && currentValue !== NO_SERVICE && dropdown.isOptionDisabled(currentValue)) {
                dropdown.clearCurrentSelection(false);
                dropdown.selectOption(NO_SERVICE);
            }
        };

        const handleLanguageChange = () => {
            const baseLang = baseLanguageSelect?.value;
            const targetLang = languageSelect?.value;

            updateCheckbox(modal, translatorSelect, baseLang, targetLang);
            updateDropdown(translatorSelect, baseLang, targetLang);
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
