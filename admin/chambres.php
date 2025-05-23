<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupération des chambres depuis la base de données
$sql = "SELECT * FROM chambre"; // Nom de table selon la capture d'écran
$result = $conn->query($sql);

// Traitement des actions (ajout, modification, suppression)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Actions pour ajouter, modifier ou supprimer
        if ($_POST['action'] == 'add') {
            $id_type = $_POST['ID_TYPE_CHAMBRE'];
            $numero = $_POST['NUMERO_CHAMBRE'];
            $tarif = $_POST['TARIF'];
            $statut = $_POST['STATUT_CHAMBRE'];
            
            $sql = "INSERT INTO chambre (ID_TYPE_CHAMBRE, NUMERO_CHAMBRE, TARIF, STATUT_CHAMBRE) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idds", $id_type, $numero, $tarif, $statut);
            
            if ($stmt->execute()) {
                // Redirection pour éviter la résoumission du formulaire
                header("Location: chambres.php?msg=added");
                exit();
            } else {
                echo "Erreur: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['ID_CHAMBRE'];
            $id_type = $_POST['ID_TYPE_CHAMBRE'];
            $numero = $_POST['NUMERO_CHAMBRE'];
            $tarif = $_POST['TARIF'];
            $statut = $_POST['STATUT_CHAMBRE'];
            
            $sql = "UPDATE chambre SET ID_TYPE_CHAMBRE=?, NUMERO_CHAMBRE=?, TARIF=?, STATUT_CHAMBRE=? 
                    WHERE ID_CHAMBRE=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iddsi", $id_type, $numero, $tarif, $statut, $id);
            
            if ($stmt->execute()) {
                header("Location: chambres.php?msg=updated");
                exit();
            } else {
                echo "Erreur: " . $stmt->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['ID_CHAMBRE'];
            
            $sql = "DELETE FROM chambre WHERE ID_CHAMBRE=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: chambres.php?msg=deleted");
                exit();
            } else {
                echo "Erreur: " . $stmt->error;
            }
        }
    }
}

// Récupération des types de chambres pour le formulaire
$sql_types = "SELECT * FROM type_chambre"; // Nom de table selon la capture d'écran
$result_types = $conn->query($sql_types);
$types_chambres = [];
if ($result_types && $result_types->num_rows > 0) {
    while($row = $result_types->fetch_assoc()) {
        $types_chambres[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chambres - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="chambres.css" />
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
          <li><a href="chambres.php" class="active">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php">Paiements</a></li>
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
        <h2>Gestion des Chambres</h2>
        <p>Liste des chambres disponibles dans la base de données</p>
      </section>
      
      <?php
      // Affichage des messages de confirmation
      if (isset($_GET['msg'])) {
          $message = "";
          switch ($_GET['msg']) {
              case 'added':
                  $message = "La chambre a été ajoutée avec succès.";
                  break;
              case 'updated':
                  $message = "La chambre a été modifiée avec succès.";
                  break;
              case 'deleted':
                  $message = "La chambre a été supprimée avec succès.";
                  break;
          }
          if (!empty($message)) {
              echo '<div class="success-message">' . $message . '</div>';
          }
      }
      ?>
      
      <section class="chambres-management">
        <div class="chambres-controls">
          <button class="btn add-btn" onclick="openAddModal()"><i class="bx bx-plus"></i> Ajouter une chambre</button>
          <div class="search-filter">
            <input type="text" id="searchInput" placeholder="Rechercher une chambre..." class="search-input" onkeyup="filterTable()">
            <select id="statusFilter" class="filter-select" onchange="filterTable()">
              <option value="">Tous les statuts</option>
              <option value="Libre">Libre</option>
              <option value="Occupée">Occupée</option>
              <option value="Maintenance">Maintenance</option>
            </select>
          </div>
        </div>
        
        <div class="table-container">
          <table id="chambresTable">
            <thead>
              <tr>
                <th></th>
                <th>Actions</th>
                <th onclick="sortTable(0)">ID_CHAMBRE <i class="bx bx-sort-alt-2"></i></th>
                <th onclick="sortTable(1)">ID_TYPE_CHAMBRE <i class="bx bx-sort-alt-2"></i></th>
                <th onclick="sortTable(2)">NUMERO_CHAMBRE <i class="bx bx-sort-alt-2"></i></th>
                <th onclick="sortTable(3)">TARIF <i class="bx bx-sort-alt-2"></i></th>
                <th onclick="sortTable(4)">STATUT_CHAMBRE <i class="bx bx-sort-alt-2"></i></th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($result && $result->num_rows > 0) {
                  // Afficher les données de chaque ligne
                  while($row = $result->fetch_assoc()) {
                      $statusClass = "";
                      switch ($row["STATUT_CHAMBRE"]) {
                          case "Libre":
                              $statusClass = "status-libre";
                              break;
                          case "Occupée":
                              $statusClass = "status-occupee";
                              break;
                          case "Maintenance":
                              $statusClass = "status-maintenance";
                              break;
                      }
                      
                      echo "<tr>
                              <td><input type='checkbox' class='row-checkbox'></td>
                              <td>
                                <button class='btn btn-small edit-btn' onclick='openEditModal(" . $row["ID_CHAMBRE"] . ", " . $row["ID_TYPE_CHAMBRE"] . ", " . $row["NUMERO_CHAMBRE"] . ", " . $row["TARIF"] . ", \"" . $row["STATUT_CHAMBRE"] . "\")'>
                                  <i class='bx bx-edit'></i> Éditer
                                </button>
                                <button class='btn btn-small delete-btn' onclick='openDeleteModal(" . $row["ID_CHAMBRE"] . ")'>
                                  <i class='bx bx-trash'></i> Supprimer
                                </button>
                              </td>
                              <td>" . $row["ID_CHAMBRE"] . "</td>
                              <td>" . $row["ID_TYPE_CHAMBRE"] . "</td>
                              <td>" . $row["NUMERO_CHAMBRE"] . "</td>
                              <td>" . $row["TARIF"] . " €</td>
                              <td class='" . $statusClass . "'>" . $row["STATUT_CHAMBRE"] . "</td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='7'>Aucune chambre trouvée</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </section>
      
      <!-- Modal pour ajouter une chambre -->
      <div id="addModal" class="modal">
        <div class="modal-content">
          <span class="close" onclick="closeModal('addModal')">&times;</span>
          <h3>Ajouter une chambre</h3>
          <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label for="ID_TYPE_CHAMBRE">Type de chambre</label>
              <select name="ID_TYPE_CHAMBRE" id="ID_TYPE_CHAMBRE" required>
                <?php foreach ($types_chambres as $type): ?>
                <option value="<?php echo $type['ID_TYPE_CHAMBRE']; ?>"><?php echo isset($type['LIBELLE']) ? $type['LIBELLE'] : 'Type ' . $type['ID_TYPE_CHAMBRE']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="NUMERO_CHAMBRE">Numéro de chambre</label>
              <input type="number" name="NUMERO_CHAMBRE" id="NUMERO_CHAMBRE" required>
            </div>
            <div class="form-group">
              <label for="TARIF">Tarif par nuit (€)</label>
              <input type="number" name="TARIF" id="TARIF" step="0.01" required>
            </div>
            <div class="form-group">
              <label for="STATUT_CHAMBRE">Statut</label>
              <select name="STATUT_CHAMBRE" id="STATUT_CHAMBRE" required>
                <option value="Libre">Libre</option>
                <option value="Occupée">Occupée</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn cancel-btn" onclick="closeModal('addModal')">Annuler</button>
              <button type="submit" class="btn submit-btn">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Modal pour modifier une chambre -->
      <div id="editModal" class="modal">
        <div class="modal-content">
          <span class="close" onclick="closeModal('editModal')">&times;</span>
          <h3>Modifier une chambre</h3>
          <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="ID_CHAMBRE" id="edit_ID_CHAMBRE">
            <div class="form-group">
              <label for="edit_ID_TYPE_CHAMBRE">Type de chambre</label>
              <select name="ID_TYPE_CHAMBRE" id="edit_ID_TYPE_CHAMBRE" required>
                <?php foreach ($types_chambres as $type): ?>
                <option value="<?php echo $type['ID_TYPE_CHAMBRE']; ?>"><?php echo isset($type['LIBELLE']) ? $type['LIBELLE'] : 'Type ' . $type['ID_TYPE_CHAMBRE']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="edit_NUMERO_CHAMBRE">Numéro de chambre</label>
              <input type="number" name="NUMERO_CHAMBRE" id="edit_NUMERO_CHAMBRE" required>
            </div>
            <div class="form-group">
              <label for="edit_TARIF">Tarif par nuit (€)</label>
              <input type="number" name="TARIF" id="edit_TARIF" step="0.01" required>
            </div>
            <div class="form-group">
              <label for="edit_STATUT_CHAMBRE">Statut</label>
              <select name="STATUT_CHAMBRE" id="edit_STATUT_CHAMBRE" required>
                <option value="Libre">Libre</option>
                <option value="Occupée">Occupée</option>
                <option value="Maintenance">Maintenance</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn cancel-btn" onclick="closeModal('editModal')">Annuler</button>
              <button type="submit" class="btn submit-btn">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Modal pour confirmer la suppression -->
      <div id="deleteModal" class="modal">
        <div class="modal-content">
          <h3>Confirmation de suppression</h3>
          <p>Êtes-vous sûr de vouloir supprimer cette chambre?</p>
          <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="ID_CHAMBRE" id="delete_ID_CHAMBRE">
            <div class="form-actions">
              <button type="button" class="btn cancel-btn" onclick="closeModal('deleteModal')">Annuler</button>
              <button type="submit" class="btn delete-btn">Supprimer</button>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
    <script src="chambres.js"></script>
  </body>
</html>
