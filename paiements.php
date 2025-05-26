<?php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header("Location: login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Déterminer si l'utilisateur est admin
$isAdmin = isAdmin();
$currentUserId = $_SESSION['ID_CLIENT'];

// Requêtes pour récupérer les données de paiement
if ($isAdmin) {
    // Admin voit tous les paiements
    $sql_paiements = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, r.ID_CLIENT
                      FROM paiement p 
                      JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                      JOIN client c ON r.ID_CLIENT = c.ID_CLIENT
                      ORDER BY p.DATE_PAIEMENT DESC";
    
    // Statistiques pour admin
    $sql_stats = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(MONTANT) as revenus_mois,
                    COUNT(*) as completes
                  FROM paiement 
                  WHERE MONTH(DATE_PAIEMENT) = MONTH(CURRENT_DATE()) 
                  AND YEAR(DATE_PAIEMENT) = YEAR(CURRENT_DATE())";
} else {
    // Utilisateur normal voit seulement ses paiements
    $sql_paiements = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, r.ID_CLIENT
                      FROM paiement p 
                      JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                      JOIN client c ON r.ID_CLIENT = c.ID_CLIENT
                      WHERE r.ID_CLIENT = ? 
                      ORDER BY p.DATE_PAIEMENT DESC";
    
    // Statistiques pour utilisateur
    $sql_stats = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(p.MONTANT) as total_paye,
                    COUNT(*) as completes
                  FROM paiement p
                  JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION
                  WHERE r.ID_CLIENT = ?";
}

// Exécuter les requêtes
try {
    // Récupérer les statistiques
    if ($isAdmin) {
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->execute();
    } else {
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->bind_param("i", $currentUserId);
        $stmt_stats->execute();
    }
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    // Récupérer les paiements
    if ($isAdmin) {
        $stmt_paiements = $conn->prepare($sql_paiements);
        $stmt_paiements->execute();
    } else {
        $stmt_paiements = $conn->prepare($sql_paiements);
        $stmt_paiements->bind_param("i", $currentUserId);
        $stmt_paiements->execute();
    }
    $result_paiements = $stmt_paiements->get_result();
    $paiements = $result_paiements->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    // En cas d'erreur, utiliser des données par défaut
    $stats = [
        'total_transactions' => 0,
        'revenus_mois' => 0,
        'total_paye' => 0,
        'completes' => 0
    ];
    $paiements = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $isAdmin ? 'Gestion des Paiements' : 'Mes Paiements'; ?> - Hôtel Élégance</title>
    <link rel="stylesheet" href="main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <style>
      /* Enhanced Paiements Page Styling */
      .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 4rem 2rem;
        margin-bottom: 3rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
      }

      .page-header h2 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      }

      .page-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin: 0;
      }

      .paiements-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
      }

      /* Dashboard Summary Cards */
      .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
      }

      .summary-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
        position: relative;
        overflow: hidden;
      }

      .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
      }

      .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      }

      .summary-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        background: linear-gradient(135deg, #667eea, #764ba2);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      }

      .summary-info h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: #2c3e50;
      }

      .summary-info p {
        margin: 0;
        color: #7f8c8d;
        font-weight: 500;
      }

      /* Controls Section */
      .paiements-controls {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
      }

      .search-filter {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
      }

      .search-input, .filter-select {
        padding: 0.8rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        min-width: 200px;
      }

      .search-input:focus, .filter-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      .btn {
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
      }

      .filter-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
      }

      .add-btn {
        background: linear-gradient(135deg, #56ab2f, #a8e6cf);
        color: white;
      }

      .add-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(86, 171, 47, 0.3);
      }

      /* Table Styling */
      .paiements-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        /* Remove table-layout: fixed */
      }

      thead {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      th, td {
        padding: 1.2rem;
        text-align: left;
        border-bottom: 1px solid #f8f9fa;
      }

      th {
        font-weight: 600;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      tbody tr {
        transition: all 0.3s ease;
      }

      tbody tr:hover {
        background: #f8f9fa;
        /* Remove: transform: scale(1.01); */
      }

      /* Action Buttons */
      .actions {
        display: flex;
        gap: 0.5rem;
      }

      .btn-icon {
        width: 35px;
        height: 35px;
        border: none;
        border-radius: 8px;
        background: #f8f9fa;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .btn-icon:hover {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      }

      /* No data message */
      .no-data {
        text-align: center;
        padding: 3rem;
        color: #7f8c8d;
      }

      .no-data i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #bdc3c7;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .page-header {
          padding: 2rem 1rem;
        }

        .page-header h2 {
          font-size: 2rem;
        }

        .paiements-content {
          padding: 0 1rem;
        }

        .dashboard-summary {
          grid-template-columns: 1fr;
          gap: 1rem;
        }

        .paiements-controls {
          flex-direction: column;
          align-items: stretch;
        }

        .search-filter {
          flex-direction: column;
        }

        .search-input, .filter-select {
          min-width: auto;
          width: 100%;
        }

        .paiements-table {
          overflow-x: auto;
        }

        table {
          min-width: 800px;
        }

        th, td {
          padding: 0.8rem;
          font-size: 0.9rem;
        }
      }

      /* Animation for page load */
      .summary-card, .paiements-controls, .paiements-table {
        animation: fadeInUp 0.6s ease-out;
      }

      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Hover effects for table rows */
      tbody tr {
        position: relative;
      }

      tbody tr::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        transform: scaleY(0);
        transition: transform 0.3s ease;
      }

      tbody tr:hover::before {
        transform: scaleY(1);
      }
    </style>
  </head>
  <body>
    <!-- Header Section avec logo et navigation -->
    <header>
      <div class="logo">
        <i class="bx bx-hotel"></i>
        <h1>Hôtel Élégance</h1>
      </div>
      <div class="nav-toggle" id="navToggle">
        <i class="bx bx-menu"></i>
      </div>
      <nav class="navbar">
        <ul class="nav-links">
          <li><a href="<?php echo $isAdmin ? 'admin/index.php' : 'indexC.php'; ?>">Accueil</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/chambres.php' : 'chambres.php'; ?>">Chambres</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/reservations.php' : 'reservations.php'; ?>">Réservations</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/paiements.php' : 'paiements.php'; ?>" class="active">Paiements</a></li>
        </ul>
        <div class="user-actions">
          <?php if (isLoggedIn()): ?>
            <span class="welcome-msg">Bienvenue, <?php echo htmlspecialchars($_SESSION['PRENOM']); ?></span>
            <a href="logout.php" class="btn logout-btn">Déconnexion</a>
          <?php else: ?>
            <a href="login.html" class="btn login-btn">Connexion</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Contenu principal -->
    <main>
      <section class="page-header">
        <h2><?php echo $isAdmin ? 'Gestion des Paiements' : 'Mes Paiements'; ?></h2>
        <p><?php echo $isAdmin ? 'Suivi et historique des transactions' : 'Historique de vos paiements'; ?></p>
      </section>
      
      <section class="paiements-content">
        <div class="dashboard-summary">
          <?php if ($isAdmin): ?>
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo number_format($stats['revenus_mois'] ?? 0, 0, ',', ' '); ?> €</h3>
              <p>Revenus du mois</p>
            </div>
          </div>
          <?php else: ?>
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo number_format($stats['total_paye'] ?? 0, 0, ',', ' '); ?> €</h3>
              <p>Total payé</p>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-credit-card"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo $stats['total_transactions'] ?? 0; ?></h3>
              <p>Transactions</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-check-circle"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo $stats['completes'] ?? 0; ?></h3>
              <p>Complétés</p>
            </div>
          </div>
        </div>
        
        <div class="paiements-controls">
          <div class="search-filter">
            <input type="text" placeholder="Rechercher un paiement..." class="search-input" id="searchInput">
            <select class="filter-select" id="statusFilter">
              <option value="all">Tous les paiements</option>
            </select>
            <button class="btn filter-btn" onclick="filterPaiements()">Filtrer</button>
          </div>
          <?php if ($isAdmin): ?>
          <button class="btn add-btn"><i class="bx bx-plus"></i> Nouveau paiement</button>
          <?php endif; ?>
        </div>
        
        <div class="paiements-table">
          <?php if (!empty($paiements)): ?>
          <table id="paiementsTable">
            <thead>
              <tr>
              <th></th>
              <th>ID</th>
                <th>Client</th>
                <th>Réservation</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Date</th>
                
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paiements as $paiement): ?>
              <tr>
                <td>#PAY-<?php echo str_pad($paiement['ID_PAIEMENT'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars(($paiement['PRENOM'] ?? '') . ' ' . ($paiement['NOM'] ?? '')); ?></td>
                <td>#RES-<?php echo str_pad($paiement['ID_RESERVATION'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo number_format($paiement['MONTANT'], 0, ',', ' '); ?> €</td>
                <td><?php echo htmlspecialchars($paiement['MOYEN_PAIEMENT'] ?? ''); ?></td>
                <td><?php echo date('d/m/Y', strtotime($paiement['DATE_PAIEMENT'])); ?></td>
                
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="no-data">
            <i class="bx bx-credit-card"></i>
            <h3>Aucun paiement trouvé</h3>
            <p><?php echo $isAdmin ? 'Aucune transaction enregistrée dans le système.' : 'Vous n\'avez effectué aucun paiement pour le moment.'; ?></p>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="main.js"></script>
    <script>
      // Fonction pour filtrer les paiements
      function filterPaiements() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#paiementsTable tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          
          if (text.includes(searchInput)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      }
      
      // Filtrage en temps réel
      document.getElementById('searchInput').addEventListener('keyup', filterPaiements);
    </script>
  </body>
</html>