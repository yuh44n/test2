<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Vérifier si l'ID du paiement est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: paiements.php");
    exit();
}

$id_paiement = intval($_GET['id']);

// Récupérer les détails du paiement
$sql = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, ch.NUMERO, ch.TYPE 
        FROM paiement p 
        LEFT JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
        LEFT JOIN client c ON r.ID_CLIENT = c.ID_CLIENT 
        LEFT JOIN chambre ch ON r.ID_CHAMBRE = ch.ID_CHAMBRE
        WHERE p.ID_PAIEMENT = ?";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_paiement);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Paiement non trouvé
        header("Location: paiements.php");
        exit();
    }
    
    $paiement = $result->fetch_assoc();
    
} catch (Exception $e) {
    // En cas d'erreur, rediriger vers la page des paiements
    header("Location: paiements.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Détails du paiement #<?php echo $id_paiement; ?> - Hôtel Élégance</title>
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
          <li><a href="index.php">Accueil</a></li>
          <li><a href="chambres.php">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php" class="active">Paiements</a></li>
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
        <h2>Détails du paiement #<?php echo $id_paiement; ?></h2>
        <p>Informations complètes sur la transaction</p>
      </section>
      
      <section class="admin-dashboard-content">
        <div class="admin-back-button">
          <a href="paiements.php" class="admin-btn"><i class="bx bx-arrow-back"></i> Retour aux paiements</a>
        </div>
        
        <div class="admin-detail-card">
          <div class="admin-detail-header">
            <h3>Paiement #<?php echo $paiement['ID_PAIEMENT']; ?></h3>
            <span class="admin-status-badge admin-complété">Complété</span>
          </div>
          
          <div class="admin-detail-content">
            <div class="admin-detail-section">
              <h4>Informations de paiement</h4>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Montant</div>
                <div class="admin-detail-value"><?php echo number_format($paiement['MONTANT'], 0, ',', ' '); ?> €</div>
              </div>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Date de paiement</div>
                <div class="admin-detail-value"><?php echo date('d/m/Y', strtotime($paiement['DATE_PAIEMENT'])); ?></div>
              </div>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Méthode de paiement</div>
                <div class="admin-detail-value"><?php echo isset($paiement['METHODE_PAIEMENT']) ? htmlspecialchars($paiement['METHODE_PAIEMENT']) : 'Carte bancaire'; ?></div>
              </div>
            </div>
            
            <div class="admin-detail-section">
              <h4>Informations client</h4>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Nom</div>
                <div class="admin-detail-value"><?php echo htmlspecialchars($paiement['PRENOM'] . ' ' . $paiement['NOM']); ?></div>
              </div>
            </div>
            
            <div class="admin-detail-section">
              <h4>Informations réservation</h4>
              <div class="admin-detail-row">
                <div class="admin-detail-label">ID Réservation</div>
                <div class="admin-detail-value">#<?php echo $paiement['ID_RESERVATION']; ?></div>
              </div>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Chambre</div>
                <div class="admin-detail-value"><?php echo isset($paiement['NUMERO']) ? 'Chambre ' . $paiement['NUMERO'] . ' (' . $paiement['TYPE'] . ')' : 'Non spécifiée'; ?></div>
              </div>
              <div class="admin-detail-row">
                <div class="admin-detail-label">Période</div>
                <div class="admin-detail-value">
                  <?php 
                  if (isset($paiement['DATE_ARRIVEE']) && isset($paiement['DATE_DEPART'])) {
                      echo date('d/m/Y', strtotime($paiement['DATE_ARRIVEE'])) . ' au ' . date('d/m/Y', strtotime($paiement['DATE_DEPART']));
                  } else {
                      echo 'Non spécifiée';
                  }
                  ?>
                </div>
              </div>
            </div>
          </div>
          
          <div class="admin-detail-actions">
            <a href="editer_paiement.php?id=<?php echo $paiement['ID_PAIEMENT']; ?>" class="admin-btn"><i class="bx bx-edit"></i> Modifier</a>
            <button class="admin-btn admin-btn-danger" onclick="confirmerSuppression(<?php echo $paiement['ID_PAIEMENT']; ?>)"><i class="bx bx-trash"></i> Supprimer</button>
            <button class="admin-btn" onclick="window.print()"><i class="bx bx-printer"></i> Imprimer</button>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer class="admin-footer">
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
    <script>
      // Fonction pour confirmer la suppression
      function confirmerSuppression(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
          location.href = 'supprimer_paiement.php?id=' + id;
        }
      }
    </script>
  </body>
</html>