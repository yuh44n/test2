<?php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Paiements - Hôtel Élégance</title>
    <link rel="stylesheet" href="main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
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
            <input type="text" placeholder="Rechercher un paiement..." class="search-input">
            <select class="filter-select">
              <option value="all">Tous les paiements</option>
              <option value="completed">Complétés</option>
              <option value="pending">En attente</option>
              <option value="refunded">Remboursés</option>
            </select>
            <button class="btn filter-btn">Filtrer</button>
          </div>
          <button class="btn add-btn"><i class="bx bx-plus"></i> Nouveau paiement</button>
        </div>
        
        <div class="paiements-table">
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
              <tr>
                <td>#PAY-2025-001</td>
                <td>Martin Dupont</td>
                <td>#RES-2025-001</td>
                <td>450 €</td>
                <td>Carte bancaire</td>
                <td>10/05/2025</td>
                <td><span class="status-badge completed">Complété</span></td>
                <td class="actions">
                  <button class="btn-icon"><i class="bx bx-receipt"></i></button>
                  <button class="btn-icon"><i class="bx bx-show"></i></button>
                </td>
              </tr>
              <tr>
                <td>#PAY-2025-002</td>
                <td>Sophie Laurent</td>
                <td>#RES-2025-002</td>
                <td>600 €</td>
                <td>PayPal</td>
                <td>12/05/2025</td>
                <td><span class="status-badge pending">En attente</span></td>
                <td class="actions">
                  <button class="btn-icon"><i class="bx bx-receipt"></i></button>
                  <button class="btn-icon"><i class="bx bx-show"></i></button>
                </td>
              </tr>
              <tr>
                <td>#PAY-2025-003</td>
                <td>Jean Moreau</td>
                <td>#RES-2025-003</td>
                <td>750 €</td>
                <td>Virement bancaire</td>
                <td>15/05/2025</td>
                <td><span class="status-badge completed">Complété</span></td>
                <td class="actions">
                  <button class="btn-icon"><i class="bx bx-receipt"></i></button>
                  <button class="btn-icon"><i class="bx bx-show"></i></button>
                </td>
              </tr>
              <tr>
                <td>#PAY-2025-004</td>
                <td>Marie Leclerc</td>
                <td>#RES-2025-004</td>
                <td>300 €</td>
                <td>Carte bancaire</td>
                <td>09/05/2025</td>
                <td><span class="status-badge refunded">Remboursé</span></td>
                <td class="actions">
                  <button class="btn-icon"><i class="bx bx-receipt"></i></button>
                  <button class="btn-icon"><i class="bx bx-show"></i></button>
                </td>
              </tr>
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

    <script src="main.js"></script>
  </body>
</html>
