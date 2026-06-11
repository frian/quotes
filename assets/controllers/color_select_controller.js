import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'label', 'menu', 'select', 'swatch'];

    connect() {
        this.sync();
        this.closeOnOutsideClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        document.addEventListener('click', this.closeOnOutsideClick);
    }

    disconnect() {
        document.removeEventListener('click', this.closeOnOutsideClick);
    }

    toggle() {
        this.menuTarget.hidden = !this.menuTarget.hidden;
    }

    choose(event) {
        const option = event.currentTarget;

        this.selectTarget.value = option.dataset.colorValue;
        this.selectTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.close();
    }

    sync() {
        const selected = this.element.querySelector(`[data-color-value="${this.selectTarget.value}"]`);

        this.labelTarget.textContent = selected?.dataset.colorLabel || this.selectTarget.options[0]?.textContent || '';
        this.swatchTarget.className = 'color-select__swatch';

        if (selected?.dataset.colorClass) {
            this.swatchTarget.classList.add(selected.dataset.colorClass);
        }
    }

    close() {
        this.menuTarget.hidden = true;
    }
}
