<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupération des paiements (exemple - à adapter selon votre base de données)
$paiements = [
    [
        'id' => 'PAY-2025-001',
        'client' => 'Martin Dupont',
        'reservation' => 'RES-2025-001',
        'montant' => 450,
        'methode' => 'Carte bancaire',
        'date' => '10/05/2025',
        'statut' => 'Complété'
    ],
    [
        'id' => 'PAY-2025-002',
        'client' => 'Sophie Laurent',
        'reservation' => 'RES-2025-002',
        'montant' => 600,
        'methode' => 'PayPal',
        'date' => '12/05/2025',
        'statut' => 'En attente'
    ],
    [
        'id' => 'PAY-2025-003',
        'client' => 'Jean Moreau',
        'reservation' => 'RES-2025-003',
        'montant' => 750,
        'methode' => 'Virement bancaire',
        'date' => '15/05/2025',
        'statut' => 'Complété'
    ],
    [
        'id' => 'PAY-2025-004',
        'client' => 'Marie Leclerc',
        'reservation' => 'RES-2025-004',
        'montant' => 300,
        'methode' => 'Carte bancaire',
        'date' => '09/05/2025',
        'statut' => 'Remboursé'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Paiements - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <style>
      .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      
      .summary-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 20px;
        display: flex;
        align-items: center;
      }
      
      .summary-icon {
        font-size: 36px;
        color: #7494ec;
        margin-right: 15px;
      }
      
      .summary-info h3 {
        margin: 0;
        font-size: 24px;
        color: #333;
      }
      
      .summary-info p {
        margin: 5px 0 0;
        color: #666;
      }
      
      .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
      }
      
      .completed {
        background-color: #d4edda;
        color: #155724;
      }
      
      .pending {
        background-color: #fff3cd;
        color: #856404;
      }
      
      .refunded {
        background-color: #f8d7da;
        color: #721c24;
      }
      
      .btn-icon {
        background: none;
        border: none;
        font-size: 18px;
        color: #7494ec;
        cursor: pointer;
        padding: 5px;
      }
      
      .btn-icon:hover {
        color: #5a7dd3;
      }
      
      .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
      }
      
      .pagination-btn {
        background: none;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 10px;
        margin: 0 5px;
        cursor: pointer;
      }
      
      .pagination-info {
        margin: 0 10px;
        color: #666;
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
          <li><a href="index.php">Accueil</a></li>
          <li><a href="chambres.php">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php" class="active">Paiements</a></li>
        </ul>
        <div class="user-actions">
          <?php if (isLoggedIn()): ?>
            <span class="welcome-msg">Bienvenue, <?php echo htmlspecialchars($_SESSION['PRENOM']); ?></span>
            <a href="../logout.php" class="btn logout-btn">Déconnexion</a>
          <?php else: ?>
            <a href="../login.html" class="btn login-btn">Connexion</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Contenu principal -->
    <main>
      <section class="page-header">
        <h2>Gestion des Paiements</h2>
        <p>Suivi et historique des transactions</p>
      </section>
      
      <section class="paiements-content">
        <div class="dashboard-summary">
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="summary-info">
              <h3>8,750 €</h3>
              <p>Revenus du mois</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-credit-card"></i>
            </div>
            <div class="summary-info">
              <h3>43</h3>
              <p>Transactions</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-time"></i>
            </div>
            <div class="summary-info">
              <h3>5</h3>
              <p>En attente</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-check-circle"></i>
            </div>
            <div class="summary-info">
              <h3>38</h3>
              <p>Complétés</p>
            </div>
          </div>
        </div>
        
        <div class="paiements-controls">
          <div class="search-filter">
            <input type="text" placeholder="Rechercher un paiement..." class="search-input" id="searchInput" onkeyup="filterPaiements()">
            <select class="filter-select" id="statusFilter" onchange="filterPaiements()">
              <option value="all">Tous les paiements</option>
              <option value="Complété">Complétés</option>
              <option value="En attente">En attente</option>
              <option value="Remboursé">Remboursés</option>
            </select>
          </div>
          <button class="btn add-btn" onclick="location.href='ajouter_paiement.php'"><i class="bx bx-plus"></i> Nouveau paiement</button>
        </div>
        
        <div class="paiements-table">
          <table id="paiementsTable">
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
              <?php foreach ($paiements as $paiement): ?>
              <tr data-status="<?php echo $paiement['statut']; ?>">
                <td>#<?php echo $paiement['id']; ?></td>
                <td><?php echo $paiement['client']; ?></td>
                <td>#<?php echo $paiement['reservation']; ?></td>
                <td><?php echo $paiement['montant']; ?> €</td>
                <td><?php echo $paiement['methode']; ?></td>
                <td><?php echo $paiement['date']; ?></td>
                <td>
                  <span class="status-badge <?php echo strtolower($paiement['statut']); ?>">
                    <?php echo $paiement['statut']; ?>
                  </span>
                </td>
                <td class="actions">
                  <button class="btn-icon" onclick="location.href='voir_facture.php?id=<?php echo $paiement['id']; ?>'"><i class="bx bx-receipt"></i></button>
                  <button class="btn-icon" onclick="location.href='voir_paiement.php?id=<?php echo $paiement['id']; ?>'"><i class="bx bx-show"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <div class="pagination">
          <button class="pagination-btn"><i class="bx bx-chevron-left"></i></button>
          <span class="pagination-info">Page 1 sur 3</span>
          <button class="pagination-btn"><i class="bx bx-chevron-right"></i></button>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
    <script>
      // Fonction pour filtrer les paiements
      function filterPaiements() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#paiementsTable tbody tr');
        
        rows.forEach(row => {
          const rowText = row.textContent.toLowerCase();
          const rowStatus = row.getAttribute('data-status');
          
          let showRow = rowText.includes(searchInput);
          
          if (statusFilter !== 'all' && rowStatus !== statusFilter) {
            showRow = false;
          }
          
          row.style.display = showRow ? '' : 'none';
        });
      }
    </script>
  </body>
</html>
