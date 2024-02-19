(function (global, doc) {
    const modal = doc.querySelector('#add-translation-modal');
    const form = modal.querySelector('form[name="add-translation"]');
    const container = form.querySelector('.ibexa-automated-translation-services-container');
    const error = container.querySelector('.ibexa-automated-translation-error');
    const sourceLabel = container.querySelector('.ibexa-field-edit--ezboolean .ibexa-data-source__label');

    const toggleClass = (event) => {
        const target = event.target;
        const input = target.querySelector('input[type="checkbox"]');

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

        const translationAvailable =
            (typeof sourceLang === 'undefined' || sourceLang.indexOf(languagesMapping[serviceAlias]) !== -1) &&
            targetLang.indexOf(targetLang, languagesMapping[serviceAlias]) !== -1;

        if (!translationAvailable) {
            error?.classList.remove('invisible');

            if (sourceLabel.classList.contains('is-checked')) {
                sourceLabel.click();

                return false;
            }
        }

        return true;
    };

    form.addEventListener('click', () => error?.classList.add('invisible'));
    sourceLabel?.addEventListener('click', toggleClass);

    container.querySelectorAll('.ibexa-data-source__label.is-checked').forEach(() => {
        form.querySelector('.ibexa-btn--create-translation').removeAttribute('disabled');
    });

    container.querySelectorAll('.ibexa-dropdown__source select option:checked').forEach(() => {
        form.querySelector('.ibexa-btn--create-translation').removeAttribute('disabled');
    });

    form.addEventListener('submnit', submitForm);
})(window, window.document);
