// Navigation toggle pour mobile/responsive
const navToggle = document.getElementById('navToggle');
const navbar = document.querySelector('.navbar');
const mainContent = document.querySelector('main');

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