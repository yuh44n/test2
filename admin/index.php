<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Récupérer quelques statistiques pour le tableau de bord
$conn->set_charset("utf8");

// Nombre total de chambres
$sql_chambres = "SELECT COUNT(*) as total FROM chambre";
$result_chambres = $conn->query($sql_chambres);
$total_chambres = $result_chambres->fetch_assoc()['total'];

// Nombre de réservations actives
$sql_reservations = "SELECT COUNT(*) as total FROM reservation WHERE STATUT_RESERVATION = 'Confirmée'";
$result_reservations = $conn->query($sql_reservations);
$total_reservations = $result_reservations->fetch_assoc()['total'];

// Revenus du mois
$sql_revenus = "SELECT SUM(MONTANT) as total FROM paiement WHERE MONTH(DATE_PAIEMENT) = MONTH(CURRENT_DATE())";
$result_revenus = $conn->query($sql_revenus);
$revenus_mois = $result_revenus->fetch_assoc()['total'] ?: 0;

// Taux d'occupation
$sql_occupation = "SELECT COUNT(*) as occupees FROM chambre WHERE STATUT_CHAMBRE = 'Occupée'";
$result_occupation = $conn->query($sql_occupation);
$chambres_occupees = $result_occupation->fetch_assoc()['occupees'];
$taux_occupation = ($total_chambres > 0) ? round(($chambres_occupees / $total_chambres) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Administration - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link rel="stylesheet" href="admin.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <header>
      <div class="admin-logo">
        <i class="bx bx-hotel"></i>
        <h1>Hôtel Élégance</h1>
      </div>
      <div class="admin-nav-toggle" id="navToggle">
        <i class="bx bx-menu"></i>
      </div>
      <nav class="admin-navbar">
        <ul class="admin-nav-links">
          <li><a href="index.php" class="active">Accueil</a></li>
          <li><a href="chambres.php">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php">Paiements</a></li>
        </ul>
        <div class="admin-user-actions">
          <?php if (isLoggedIn()): ?>
            <span class="admin-welcome-msg">Bienvenue, <?php echo htmlspecialchars($_SESSION['PRENOM']); ?></span>
            <a href="../logout.php" class="admin-btn admin-logout-btn">Déconnexion</a>
          <?php else: ?>
            <a href="../login.html" class="admin-btn admin-login-btn">Connexion</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Contenu principal -->
    <main>
      <section class="admin-page-header">
        <h2>Tableau de Bord Administration</h2>
        <p>Bienvenue dans votre espace de gestion hôtelière</p>
      </section>
      
      <section class="admin-dashboard-content">
        <div class="admin-dashboard-summary">
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-building-house"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $total_chambres; ?></h3>
              <p>Chambres au total</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-calendar-check"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $total_reservations; ?></h3>
              <p>Réservations actives</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo number_format($revenus_mois, 0, ',', ' '); ?> €</h3>
              <p>Revenus du mois</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-pie-chart-alt"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $taux_occupation; ?>%</h3>
              <p>Taux d'occupation</p>
            </div>
          </div>
        </div>
        
        <div class="admin-shortcuts">
          <h3>Accès rapides</h3>
          <div class="admin-item-grid">
            <div class="admin-item-card">
              <div class="admin-item-image">
                <i class="bx bx-building" style="font-size: 4rem; color: #667eea;"></i>
              </div>
              <div class="admin-item-details">
                <h4 class="admin-item-title">Gestion des Chambres</h4>
                <p class="admin-item-info">Ajouter, modifier ou supprimer des chambres</p>
                <div class="admin-item-actions">
                  <a href="chambres.php" class="admin-btn">Accéder</a>
                </div>
              </div>
            </div>
            
            <div class="admin-item-card">
              <div class="admin-item-image">
                <i class="bx bx-calendar" style="font-size: 4rem; color: #667eea;"></i>
              </div>
              <div class="admin-item-details">
                <h4 class="admin-item-title">Gestion des Réservations</h4>
                <p class="admin-item-info">Consulter et gérer les réservations clients</p>
                <div class="admin-item-actions">
                  <a href="reservations.php" class="admin-btn">Accéder</a>
                </div>
              </div>
            </div>
            
            <div class="admin-item-card">
              <div class="admin-item-image">
                <i class="bx bx-credit-card" style="font-size: 4rem; color: #667eea;"></i>
              </div>
              <div class="admin-item-details">
                <h4 class="admin-item-title">Gestion des Paiements</h4>
                <p class="admin-item-info">Suivre et gérer les transactions financières</p>
                <div class="admin-item-actions">
                  <a href="paiements.php" class="admin-btn">Accéder</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer class="admin-footer">
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
  </body>
</html>
