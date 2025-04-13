// Gestion des descriptions longues (Voir plus / Voir moins)
document.querySelectorAll('.toggle-description').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.dataset.id;
        const action = this.dataset.action;

        const preview = document.querySelector(`.description-preview[data-id="${id}"]`);
        const full = document.getElementById(`desc-${id}`);

        if (action === "show") {
            preview.style.display = "none";
            full.style.display = "block";
        } else {
            preview.style.display = "block";
            full.style.display = "none";
        }
    });
});

// Mise en évidence des lignes du tableau au survol
document.querySelectorAll('.tasks-list table.styled-table tbody tr').forEach(row => {
    row.addEventListener('mouseenter', () => {
        row.style.backgroundColor = '#f9f9f9';
    });

    row.addEventListener('mouseleave', () => {
        row.style.backgroundColor = ''; // Réinitialiser la couleur
    });
});

// Gestion dynamique des statuts (optionnel si nécessaire)
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function () {
        const form = this.closest('form');
        form.submit(); // Soumettre le formulaire automatiquement à chaque changement de statut
    });
});
