import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'albumSelect',
        'artistRow',
        'newArtistRow',
        'newAlbumRow',
        'releaseYearRow',
        'reuseSummary',
        'artistHint',
        'albumHint',
    ];

    connect() {
        this.sync();
    }

    sync() {
        const hasExistingAlbum = this.albumSelectTarget.value !== '';
        const selectedAlbumLabel = this.albumSelectTarget.selectedOptions[0]?.textContent?.trim() ?? '';
        const fieldRows = [
            this.artistRowTarget,
            this.newArtistRowTarget,
            this.newAlbumRowTarget,
            this.releaseYearRowTarget,
        ];

        fieldRows.forEach((row) => {
            this.setRowState(row, !hasExistingAlbum);
        });

        if (hasExistingAlbum) {
            this.reuseSummaryTarget.textContent = selectedAlbumLabel
                ? `Album sélectionné : ${selectedAlbumLabel}. L’artiste, l’année et la chanson seront réutilisés.`
                : 'Un album existant est sélectionné. L’artiste, l’année et la chanson seront réutilisés.';
            this.artistHintTarget.textContent = 'L’artiste est déjà porté par l’album choisi.';
            this.albumHintTarget.textContent = 'Tu peux laisser les autres champs de création vides.';
        } else {
            this.reuseSummaryTarget.textContent = 'Aucun album sélectionné : tu crées un nouvel album, donc l’artiste et l’année deviennent nécessaires.';
            this.artistHintTarget.textContent = 'Utilisé seulement si tu crées un nouvel album.';
            this.albumHintTarget.textContent = 'Choisir un album existant réutilise automatiquement l’artiste et l’année.';
        }
    }

    setRowState(row, enabled) {
        row.classList.toggle('is-disabled', !enabled);

        row.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = !enabled;
        });
    }
}
