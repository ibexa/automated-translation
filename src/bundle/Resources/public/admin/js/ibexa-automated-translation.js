((global, doc) => {
    const TRANSLATOR_SELECT_SELECTOR = '#add-translation_translatorAlias';
    const BASE_LANGUAGE_SELECT_SELECTOR = '#add-translation_base_language';
    const translatorSelect = doc.querySelector(TRANSLATOR_SELECT_SELECTOR);
    const baseLanguageSelect = doc.querySelector(BASE_LANGUAGE_SELECT_SELECTOR);

    if (baseLanguageSelect && translatorSelect) {
        baseLanguageSelect.addEventListener('change', () => {
            translatorSelect.disabled = !baseLanguageSelect.value;

            const translationSelectWrapper = translatorSelect.closest('.ibexa-dropdown');

            if (translationSelectWrapper) {
                translationSelectWrapper.classList.toggle('ibexa-dropdown--disabled', !baseLanguageSelect.value);
            }
        });
    }
}) (window, document);
