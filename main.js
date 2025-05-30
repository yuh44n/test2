const navToggle = document.getElementById('navToggle');
const navbar = document.querySelector('.navbar');
const mainContent = document.querySelector('main');

document.addEventListener('DOMContentLoaded', function() {
  function updatePageContent(newContent) {
      document.getElementById('content-container').innerHTML = newContent;
      document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
          if (!document.querySelector(`link[href="${link.href}"]`)) {
              document.head.appendChild(link.cloneNode(true));
          }
      });
  }
});

// Fonction pour gérer la navigation et les animations
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Récupérer l'URL de la page à charger
            const pageUrl = this.getAttribute('href');
            
            // Activer le lien actif
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            
            // Animer la sortie du contenu actuel
            mainContent.classList.add('fade-out');
            
            // Charger le nouveau contenu après l'animation de sortie
            setTimeout(() => {
                loadContent(pageUrl);
            }, 300);
            
            // Fermer le menu mobile si ouvert
            if (navbar.classList.contains('active')) {
                navbar.classList.remove('active');
            }
        });
    });
}

// Fonction pour charger le contenu d'une page via AJAX
function loadContent(url) {
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de réseau');
            }
            return response.text();
        })
        .then(html => {
            // Extraire le contenu <main> de la page chargée
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('main').innerHTML;
            
            // Insérer le nouveau contenu
            mainContent.innerHTML = newContent;
            
            // Animer l'entrée du nouveau contenu
            mainContent.classList.remove('fade-out');
            mainContent.classList.add('fade-in');
            
            // Mettre à jour le titre de la page
            document.title = doc.title;
            
            // Mettre à jour l'URL sans recharger la page
            window.history.pushState({ path: url }, '', url);
            
            // Réinitialiser les classes d'animation
            setTimeout(() => {
                mainContent.classList.remove('fade-in');
            }, 500);
        })
        .catch(error => {
            console.error('Erreur lors du chargement de la page:', error);
            mainContent.innerHTML = `
                <div class="error-message">
                    <h2>Erreur lors du chargement</h2>
                    <p>Impossible de charger la page demandée.</p>
                </div>
            `;
            mainContent.classList.remove('fade-out');
        });
}

// Gestion du bouton retour du navigateur
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.path) {
        loadContent(e.state.path);
    }
});

// Fermer le menu quand on clique à l'extérieur
document.addEventListener('click', (event) => {
    const isNavbarClick = navbar.contains(event.target);
    const isNavToggleClick = navToggle.contains(event.target);
    
    if (!isNavbarClick && !isNavToggleClick && navbar.classList.contains('active')) {
        navbar.classList.remove('active');
    }
});

// Toggle menu mobile
navToggle.addEventListener('click', () => {
    navbar.classList.toggle('active');
});

// Initialiser la page actuelle et capturer l'état initial
function initPage() {
    // Sauvegarder l'état initial de la page
    const currentPath = window.location.pathname;
    window.history.replaceState({ path: currentPath }, '', currentPath);
    
    // Activer le lien de navigation correspondant à la page actuelle
    const navLinks = document.querySelectorAll('.nav-links a');
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.endsWith(linkPath)) {
            link.classList.add('active');
        } else if (currentPath === '/' && linkPath === 'index.html') {
            link.classList.add('active');
        }
    });
    
    setupNavigation();
}

// Exécuter au chargement de la page
window.addEventListener('DOMContentLoaded', initPage);
// Variables globales pour PayPal
      let paypalButtons = {};
      
      // Fonction pour sélectionner le mode de paiement
      function selectPaymentMethod(method, reservationId) {
        // Réinitialiser toutes les méthodes
        document.querySelectorAll('.payment-method').forEach(el => {
          el.classList.remove('selected');
        });
        
        // Sélectionner la méthode choisie
        event.target.closest('.payment-method').classList.add('selected');
        
        // Mettre à jour le champ caché
        document.getElementById('paymentMethod' + reservationId).value = method;
        
        // Afficher/masquer les sections appropriées
        const cashSection = document.getElementById('cashPayment' + reservationId);
        const paypalSection = document.getElementById('paypalPayment' + reservationId);
        
        if (method === 'cash') {
          cashSection.style.display = 'block';
          paypalSection.style.display = 'none';
        } else if (method === 'paypal') {
          cashSection.style.display = 'none';
          paypalSection.style.display = 'block';
          
          // Initialiser PayPal si pas encore fait
          if (!paypalButtons[reservationId]) {
            initializePayPal(reservationId);
          }
        }
      }
      
      // Fonction pour initialiser PayPal
      function initializePayPal(reservationId) {
        const reservationCard = document.querySelector(`[data-reservation-id="${reservationId}"]`);
        const montant = reservationCard ? reservationCard.dataset.montant : 0;
        
        paypal.Buttons({
          createOrder: function(data, actions) {
            return actions.order.create({
              purchase_units: [{
                amount: {
                  value: montant,
                  currency_code: 'EUR'
                },
                description: `Paiement réservation #${reservationId}`
              }]
            });
          },
          onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
              // Soumettre le formulaire avec les détails PayPal
              const form = document.getElementById('paymentForm' + reservationId);
              
              // Ajouter les détails PayPal au formulaire
              const paypalDetails = document.createElement('input');
              paypalDetails.type = 'hidden';
              paypalDetails.name = 'paypal_details';
              paypalDetails.value = JSON.stringify(details);
              form.appendChild(paypalDetails);
              
              // Soumettre le formulaire
              form.submit();
            });
          },
          onError: function(err) {
            console.error('Erreur PayPal:', err);
            alert('Une erreur est survenue lors du paiement PayPal. Veuillez réessayer.');
          }
        }).render('#paypal-button-container-' + reservationId);
        
        paypalButtons[reservationId] = true;
      }
      
      // Fonction pour filtrer les paiements
      function filterPaiements() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#paiementsTable tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          const methodCell = row.querySelector('td:nth-child(<?php echo $isAdmin ? "5" : "4"; ?>)');
          const method = methodCell ? methodCell.textContent.trim() : '';
          
          let showRow = true;
          
          // Filtrage par recherche
          if (searchInput && !text.includes(searchInput)) {
            showRow = false;
          }
          
          // Filtrage par statut/méthode
          if (statusFilter !== 'all' && !method.includes(statusFilter)) {
            showRow = false;
          }
          
          row.style.display = showRow ? '' : 'none';
        });
      }
      
      // Fonction pour voir les détails d'un paiement
      function viewPaymentDetails(paymentId) {
        // Cette fonction pourrait ouvrir une modal avec plus de détails
        alert('Fonctionnalité à implémenter : Détails du paiement #' + paymentId);
      }
      
      // Fonction pour imprimer un reçu
      function printReceipt(paymentId) {
        // Ouvrir une nouvelle fenêtre pour l'impression
        window.open('print_receipt.php?id=' + paymentId, '_blank');
      }
      
      // Filtrage en temps réel
      document.getElementById('searchInput').addEventListener('keyup', filterPaiements);
      document.getElementById('statusFilter').addEventListener('change', filterPaiements);
      
      // Remplir les options du filtre avec les méthodes de paiement disponibles
      document.addEventListener('DOMContentLoaded', function() {
        const statusFilter = document.getElementById('statusFilter');
        const methods = new Set();
        
        // Collecter toutes les méthodes de paiement
        document.querySelectorAll('#paiementsTable tbody tr').forEach(row => {
          const methodCell = row.querySelector('td:nth-child(<?php echo $isAdmin ? "5" : "4"; ?>)');
          if (methodCell) {
            const method = methodCell.textContent.trim().split('\n')[1]?.trim();
            if (method) methods.add(method);
          }
        });
        
        // Ajouter les options au select
        methods.forEach(method => {
          const option = document.createElement('option');
          option.value = method;
          option.textContent = method;
          statusFilter.appendChild(option);
        });
      });