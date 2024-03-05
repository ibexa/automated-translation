(function (global, doc) {
    const modal = doc.querySelector('#add-translation-modal');
    const form = modal.querySelector('form[name="add-translation"]');
    const container = form.querySelector('.ibexa-automated-translation-services-container');
    const error = container.querySelector('.ibexa-automated-translation-error');
    const sourceLabel = container.querySelector('.ibexa-field-edit--ezboolean .ibexa-data-source__label');

    const toggleClass = ({ target }) => {
        const input = target.querySelector('.ibexa-input--checkbox');

        if (input.checked) {
            input.removeAttribute('checked');
            target.classList.remove('is-checked');
        } else {
            target.classList.add('is-checked');
            input.setAttribute('checked', true);
        }
    };

    const submitForm = () => {
        const targetLang = form.querySelector('select[name="add-translation[base_language]"]').value;
        const sourceLang = form.querySelector('select[name="add-translation[language]"]').value;
        const { languagesMapping } = container.dataset;
        const serviceSelector = form.querySelector('#add-translation_translatorAlias');
        const serviceAlias = serviceSelector.value;

        if ((serviceSelector.type === 'checkbox' && !serviceSelector.checked) || !serviceAlias.length) {
            return true;
        }
        const serviceLang = languagesMapping[serviceAlias];
        const translationAvailable = (typeof sourceLang === 'undefined'  || sourceLang.includes(serviceLang)) && targetLang.includes(serviceLang);

        if (!translationAvailable) {
            error?.classList.remove('invisible');

            if (sourceLabel.classList.contains('is-checked')) {
                sourceLabel.click();

                return false;
            }
        }

        return true;
    };

    form.addEventListener('click', () => error?.classList.add('invisible'), false);
    sourceLabel?.addEventListener('click', toggleClass, false);

    if (container.querySelector('.ibexa-data-source__label.is-checked')) {
        form.querySelector('.ibexa-btn--create-translation').removeAttribute('disabled');
    }

    if (container.querySelector('.ibexa-data-source__label.is-checked')
        || container.querySelector('.ibexa-dropdown__source select option:checked')) {
        form.querySelector('.ibexa-btn--create-translation').removeAttribute('disabled');
    }

    form.addEventListener('submnit', submitForm, false);
})(window, window.document);
