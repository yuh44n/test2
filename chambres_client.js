// Fonctions pour gérer les modals
function openReserveModal(id, idType, numero, tarif) {
    document.getElementById('reserve_id_chambre').value = id;
    
    // Mettre à jour les informations de la chambre dans la modal
    const chambreInfo = document.getElementById('chambreInfo');
    chambreInfo.innerHTML = `
        <h4>Chambre n°${numero} (Type ${idType})</h4>
        <p>Tarif: ${tarif} € par nuit</p>
    `;
    
    // Réinitialiser les dates
    const today = new Date().toISOString().split('T')[0];
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    document.getElementById('date_arrivee').value = today;
    document.getElementById('date_depart').value = tomorrow.toISOString().split('T')[0];
    
    // Calculer le prix initial
    calculateTotalPrice(tarif);
    
    // Afficher la modal
    document.getElementById('reserveModal').style.display = 'block';
    
    // Ajouter des écouteurs d'événements pour les dates
    document.getElementById('date_arrivee').addEventListener('change', function() {
        calculateTotalPrice(tarif);
    });
    
    document.getElementById('date_depart').addEventListener('change', function() {
        calculateTotalPrice(tarif);
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fermer la modal si l'utilisateur clique en dehors
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Fonction pour calculer le prix total en fonction des dates
function calculateTotalPrice(tarif) {
    const dateArrivee = new Date(document.getElementById('date_arrivee').value);
    const dateDepart = new Date(document.getElementById('date_depart').value);
    
    // Calculer la différence en jours
    const diffTime = Math.abs(dateDepart - dateArrivee);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Mettre à jour le prix total
    const prixTotal = diffDays * tarif;
    document.getElementById('prixTotal').textContent = prixTotal;
    
    // Si la date de départ est antérieure à la date d'arrivée, afficher un message d'erreur
    if (dateDepart <= dateArrivee) {
        document.getElementById('prixTotal').textContent = "Erreur: La date de départ doit être postérieure à la date d'arrivée";
    }
}

// Fonction pour filtrer les chambres
function filterRooms() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value;
    const priceFilter = document.getElementById('priceFilter').value;
    
    const cards = document.querySelectorAll('.chambre-card');
    
    cards.forEach(card => {
        const type = card.getAttribute('data-type');
        const price = parseFloat(card.getAttribute('data-price'));
        const text = card.textContent.toLowerCase();
        
        let typeMatch = true;
        let priceMatch = true;
        let textMatch = true;
        
        if (typeFilter && type !== typeFilter) {
            typeMatch = false;
        }
        
        if (priceFilter && price >= parseFloat(priceFilter)) {
            priceMatch = false;
        }
        
        if (searchInput && !text.includes(searchInput)) {
            textMatch = false;
        }
        
        if (typeMatch && priceMatch && textMatch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Vérifier s'il y a des chambres visibles
    const visibleCards = document.querySelectorAll('.chambre-card[style="display: "]');
    const noChambres = document.querySelector('.no-chambres') || document.createElement('div');
    
    if (visibleCards.length === 0) {
        noChambres.className = 'no-chambres';
        noChambres.innerHTML = '<p>Aucune chambre ne correspond à votre recherche.</p>';
        
        if (!document.querySelector('.no-chambres')) {
            document.getElementById('chambresGrid').appendChild(noChambres);
        }
    } else if (document.querySelector('.no-chambres')) {
        document.querySelector('.no-chambres').remove();
    }
}

// Ajouter une validation pour les dates
document.addEventListener('DOMContentLoaded', function() {
    const dateArrivee = document.getElementById('date_arrivee');
    const dateDepart = document.getElementById('date_depart');
    
    if (dateArrivee && dateDepart) {
        dateArrivee.addEventListener('change', function() {
            // Mettre à jour la date minimale de départ (au moins 1 jour après l'arrivée)
            const minDepart = new Date(this.value);
            minDepart.setDate(minDepart.getDate() + 1);
            dateDepart.min = minDepart.toISOString().split('T')[0];
            
            // Si la date de départ est antérieure à la nouvelle date minimale, la mettre à jour
            if (new Date(dateDepart.value) <= new Date(this.value)) {
                dateDepart.value = minDepart.toISOString().split('T')[0];
            }
        });
    }
});