<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupération des réservations
$sql = "SELECT r.*, c.NUMERO_CHAMBRE, cl.NOM, cl.PRENOM 
        FROM reservation r 
        JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
        JOIN client cl ON r.ID_CLIENT = cl.ID_CLIENT
        ORDER BY r.DATE_RESERVATION DESC";
$result = $conn->query($sql);

// Convertir les résultats en tableau
$reservations = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Réservations - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link rel="stylesheet" href="admin.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <!-- Header Section avec logo et navigation -->
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
          <li><a href="reservations.php" class="active">Réservations</a></li>
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
        <h2>Gestion des Réservations</h2>
        <p>Liste et détails des réservations clients</p>
      </section>
      
      <section class="admin-reservations-content">
        <div class="admin-controls">
          <div class="admin-search-filter">
            <input type="text" placeholder="Rechercher une réservation..." class="admin-search-input" id="searchInput" onkeyup="filterReservations()">
            <select class="admin-filter-select" id="statusFilter" onchange="filterReservations()">
              <option value="all">Toutes les réservations</option>
              <option value="Confirmée">Confirmées</option>
              <option value="En attente">En attente</option>
              <option value="Annulée">Annulées</option>
            </select>
          </div>
          <button class="admin-btn admin-add-btn" onclick="location.href='ajouter_reservation.php'"><i class="bx bx-plus"></i> Nouvelle réservation</button>
        </div>
        
        <div class="admin-data-table">
          <table id="reservationsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Chambre</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $reservation): ?>
              <tr data-status="<?php echo $reservation['STATUT_RESERVATION']; ?>">
                <td>#<?php echo $reservation['ID_RESERVATION']; ?></td>
                <td><?php echo $reservation['PRENOM'] . ' ' . $reservation['NOM']; ?></td>
                <td><?php echo $reservation['NUMERO_CHAMBRE']; ?></td>
                <td><?php echo date('d/m/Y', strtotime($reservation['DATE_ARRIVEE'])); ?></td>
                <td><?php echo date('d/m/Y', strtotime($reservation['DATE_DEPART'])); ?></td>
                <td>
                  <span class="admin-status-badge admin-<?php echo strtolower($reservation['STATUT_RESERVATION']); ?>">
                    <?php echo $reservation['STATUT_RESERVATION']; ?>
                  </span>
                </td>
                <td class="admin-actions">
                  <button class="admin-btn-icon" onclick="location.href='editer_reservation.php?id=<?php echo $reservation['ID_RESERVATION']; ?>'"><i class="bx bx-edit"></i></button>
                  <button class="admin-btn-icon" onclick="location.href='voir_reservation.php?id=<?php echo $reservation['ID_RESERVATION']; ?>'"><i class="bx bx-show"></i></button>
                  <button class="admin-btn-icon" onclick="confirmerSuppression(<?php echo $reservation['ID_RESERVATION']; ?>)"><i class="bx bx-trash"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
              
              <?php if (count($reservations) == 0): ?>
              <tr>
                <td colspan="7" style="text-align: center;">Aucune réservation trouvée</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <div class="admin-pagination">
          <button class="admin-pagination-btn"><i class="bx bx-chevron-left"></i></button>
          <span class="admin-pagination-info">Page 1 sur 5</span>
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
      // Fonction pour filtrer les réservations
      function filterReservations() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#reservationsTable tbody tr');
        
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
      
      // Fonction pour confirmer la suppression
      function confirmerSuppression(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')) {
          window.location.href = 'supprimer_reservation.php?id=' + id;
        }
      }
    </script>
  </body>
</html>
