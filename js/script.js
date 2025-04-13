// Gestion des notifications (fermeture automatique)
document.querySelectorAll('.notification').forEach(notification => {
    const closeBtn = notification.querySelector('.close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
    }

    // Optionnel : fermer automatiquement après 5 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500); // Supprimer après la transition
    }, 5000);
});

// Confirmation avant suppression (général)
document.querySelectorAll('form[action*="delete.php"]').forEach(form => {
    form.addEventListener('submit', function (e) {
        const confirmMessage = "Êtes-vous sûr de vouloir supprimer cet élément ?";
        if (!confirm(confirmMessage)) {
            e.preventDefault(); // Annuler la soumission du formulaire
        }
    });
});

// Animation au clic sur un bouton principal
document.querySelectorAll('.btn-primary').forEach(button => {
    button.addEventListener('click', function () {
        this.classList.add('clicked');
        setTimeout(() => this.classList.remove('clicked'), 300); // Animation temporaire
    });
});
