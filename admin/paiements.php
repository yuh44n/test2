<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupération des paiements depuis la base de données - FIXED: Added MOYEN_PAIEMENT
$sql_paiements = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART 
                  FROM paiement p 
                  LEFT JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                  LEFT JOIN client c ON r.ID_CLIENT = c.ID_CLIENT
                  ORDER BY p.DATE_PAIEMENT DESC";

// Statistiques pour le tableau de bord
$sql_stats = "SELECT 
                COUNT(*) as total_transactions,
                SUM(MONTANT) as revenus_mois,
                COUNT(*) as completes,
                0 as en_attente
              FROM paiement";

// Exécuter les requêtes
// Après l'exécution de la requête
try {
    // Récupérer les statistiques
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    // Récupérer les paiements
    $stmt_paiements = $conn->prepare($sql_paiements);
    $stmt_paiements->execute();
    $result_paiements = $stmt_paiements->get_result();
    $paiements = $result_paiements->fetch_all(MYSQLI_ASSOC);
    
   
    // Si aucun paiement n'est trouvé, vérifier si la table contient des données
    if (count($paiements) == 0) {
        $check_sql = "SELECT COUNT(*) as count FROM paiement";
        $check_result = $conn->query($check_sql);
        $row = $check_result->fetch_assoc();
        echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>
                <p><strong>Débogage:</strong> Nombre total d'enregistrements dans la table paiement: " . $row['count'] . "</p>
              </div>";
    }
    
} catch (Exception $e) {
    // En cas d'erreur, utiliser des données par défaut et afficher l'erreur
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;'>
            <p><strong>Erreur SQL:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
    
    $stats = [
        'total_transactions' => 0,
        'revenus_mois' => 0,
        'completes' => 0,
        'en_attente' => 0
    ];
    $paiements = [];
}

// FIXED: Function to format payment method display
function formatPaymentMethod($method) {
    if (empty($method)) {
        return 'Non spécifié';
    }
    
    switch (strtolower($method)) {
        case 'carte':
            return 'Carte bancaire';
        case 'espèces':
            return 'Espèces';
        case 'cash':
            return 'Espèces';
        case 'virement':
            return 'Virement bancaire';
        case 'cheque':
            return 'Chèque';
        default:
            return htmlspecialchars($method);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Paiements - Hôtel Élégance</title>
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
          <li><a href="clients.php">Clients</a></li>
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
        <h2>Gestion des Paiements</h2>
        <p>Suivi et gestion des transactions financières</p>
      </section>
      
      <?php if (isset($_GET['message'])): ?>
      <div class="admin-success-message">
        <i class="bx bx-check-circle"></i>
        <?php echo htmlspecialchars($_GET['message']); ?>
      </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error'])): ?>
      <div class="admin-error-message">
        <i class="bx bx-error-circle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
      <?php endif; ?>
      
      <section class="admin-dashboard-content">
        <!-- Résumé des paiements -->
        <div class="admin-dashboard-summary">
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo number_format($stats['revenus_mois'] ?? 0, 0, ',', ' '); ?> €</h3>
              <p>Revenus du mois</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-credit-card"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $stats['total_transactions'] ?? 0; ?></h3>
              <p>Transactions totales</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-check-circle"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $stats['completes'] ?? 0; ?></h3>
              <p>Paiements complétés</p>
            </div>
          </div>
          
          <div class="admin-card">
            <div class="admin-icon">
              <i class="bx bx-time"></i>
            </div>
            <div class="admin-info">
              <h3><?php echo $stats['en_attente'] ?? 0; ?></h3>
              <p>Paiements en attente</p>
            </div>
          </div>
        </div>
        
        <!-- Contrôles des paiements -->
        <div class="admin-controls">
          <div class="admin-search-filter">
            <input type="text" placeholder="Rechercher un paiement..." class="admin-search-input" id="searchInput">
            <select class="admin-filter-select" id="statusFilter">
              <option value="all">Tous les paiements</option>
              <option value="Complété">Complétés</option>
              <option value="En attente">En attente</option>
              <option value="Remboursé">Remboursés</option>
            </select>
          </div>
          <button class="admin-btn admin-add-btn" onclick="window.location.href='ajouter_paiement.php'"><i class="bx bx-plus"></i> Nouveau paiement</button>
        </div>
        
        <!-- Tableau des paiements -->
        <div class="admin-data-table">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Réservation</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($paiements) > 0): ?>
                <?php foreach ($paiements as $paiement): ?>
                <tr>
                  <td>#<?php echo $paiement['ID_PAIEMENT']; ?></td>
                  <td><?php echo htmlspecialchars($paiement['PRENOM'] . ' ' . $paiement['NOM']); ?></td>
                  <td>#<?php echo $paiement['ID_RESERVATION']; ?></td>
                  <td><?php echo number_format($paiement['MONTANT'], 0, ',', ' '); ?> €</td>
                  <td><?php echo formatPaymentMethod($paiement['MOYEN_PAIEMENT'] ?? ''); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($paiement['DATE_PAIEMENT'])); ?></td>
                  <td>
                    <span class="admin-status-badge admin-complété">
                      Complété
                    </span>
                  </td>
                  <td class="admin-actions">
                    <button class="admin-btn-icon" onclick="confirmerSuppression(<?php echo $paiement['ID_PAIEMENT']; ?>)"><i class="bx bx-trash"></i></button>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" style="text-align: center;">Aucun paiement trouvé</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <div class="admin-pagination">
          <button class="admin-pagination-btn"><i class="bx bx-chevron-left"></i></button>
          <span class="admin-pagination-info">Page 1 sur 1</span>
          <button class="admin-pagination-btn"><i class="bx bx-chevron-right"></i></button>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer class="admin-footer">
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
    <script>
      // Fonction pour filtrer les paiements
      function filterPaiements() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
          const clientName = row.cells[1].textContent.toLowerCase();
          const reservationId = row.cells[2].textContent.toLowerCase();
          const status = row.querySelector('.admin-status-badge').textContent.trim();
          
          const matchesSearch = clientName.includes(searchInput) || reservationId.includes(searchInput);
          const matchesStatus = statusFilter === 'all' || status === statusFilter;
          
          row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
      }
      
      // Ajouter les écouteurs d'événements
      document.getElementById('searchInput').addEventListener('keyup', filterPaiements);
      document.getElementById('statusFilter').addEventListener('change', filterPaiements);
      
      // Fonction pour confirmer la suppression
      function confirmerSuppression(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
          location.href = 'supprimer_paiement.php?id=' + id;
        }
      }
    </script>
    
  </body>
</html>