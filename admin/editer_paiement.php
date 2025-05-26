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
$message = '';
$error = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
    $date_paiement = isset($_POST['date_paiement']) ? $_POST['date_paiement'] : date('Y-m-d');
    $methode_paiement = isset($_POST['methode_paiement']) ? $_POST['methode_paiement'] : 'Carte bancaire';
    $id_reservation = isset($_POST['id_reservation']) ? intval($_POST['id_reservation']) : 0;
    
    // Vérifier que le montant est positif
    if ($montant <= 0) {
        $error = "Le montant doit être supérieur à zéro.";
    } else {
        // Mettre à jour le paiement dans la base de données
        $sql = "UPDATE paiement SET 
                MONTANT = ?, 
                DATE_PAIEMENT = ?, 
                MOYEN_PAIEMENT = ?,  // Changed from METHODE_PAIEMENT to MOYEN_PAIEMENT
                ID_RESERVATION = ? 
                WHERE ID_PAIEMENT = ?";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dssii", $montant, $date_paiement, $methode_paiement, $id_reservation, $id_paiement);
            // Changed type string to "dssii" for correct data types
            
            if ($stmt->execute()) {
                $message = "Le paiement a été mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour du paiement.";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour du paiement: " . $e->getMessage();
        }
    }
}

// Récupérer les détails du paiement
$sql = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION 
        FROM paiement p 
        LEFT JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
        LEFT JOIN client c ON r.ID_CLIENT = c.ID_CLIENT 
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

// Récupérer la liste des réservations pour le menu déroulant
$sql_reservations = "SELECT r.ID_RESERVATION, c.NOM, c.PRENOM 
                     FROM reservation r 
                     JOIN client c ON r.ID_CLIENT = c.ID_CLIENT 
                     ORDER BY r.DATE_RESERVATION DESC";

try {
    $result_reservations = $conn->query($sql_reservations);
    $reservations = $result_reservations->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $reservations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier le paiement #<?php echo $id_paiement; ?> - Hôtel Élégance</title>
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
        <h2>Modifier le paiement #<?php echo $id_paiement; ?></h2>
        <p>Mettre à jour les informations de paiement</p>
      </section>
      
      <section class="admin-dashboard-content">
        <div class="admin-back-button">
          <a href="paiements.php" class="admin-btn"><i class="bx bx-arrow-back"></i> Retour aux paiements</a>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="admin-success-message">
          <i class="bx bx-check-circle"></i>
          <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="admin-error-message">
          <i class="bx bx-error-circle"></i>
          <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-form-card">
          <form method="POST" action="">
            <div class="admin-form-group">
              <label for="id_reservation">Réservation</label>
              <select id="id_reservation" name="id_reservation" disabled>
                <option value="<?php echo $paiement['ID_RESERVATION']; ?>">
                  #<?php echo $paiement['ID_RESERVATION']; ?> - <?php echo htmlspecialchars($paiement['PRENOM'] . ' ' . $paiement['NOM']); ?>
                </option>
              </select>
              <!-- Ajouter ce champ caché pour conserver l'ID_RESERVATION lors de la soumission -->
              <input type="hidden" name="id_reservation" value="<?php echo $paiement['ID_RESERVATION']; ?>">
              <p class="admin-form-help">La réservation ne peut pas être modifiée</p>
            </div>
            
            <div class="admin-form-group">
              <label for="montant">Montant (€)</label>
              <input type="number" id="montant" name="montant" step="0.01" min="0" value="<?php echo $paiement['MONTANT']; ?>" required>
            </div>
            
            <div class="admin-form-group">
              <label for="date_paiement">Date de paiement</label>
              <input type="date" id="date_paiement" name="date_paiement" value="<?php echo date('Y-m-d', strtotime($paiement['DATE_PAIEMENT'])); ?>" required>
            </div>
            
            <div class="admin-form-group">
              <label for="methode_paiement">Méthode de paiement</label>
              <select id="methode_paiement" name="methode_paiement">
                <option value="Carte bancaire" <?php echo (isset($paiement['METHODE_PAIEMENT']) && $paiement['METHODE_PAIEMENT'] == 'Carte bancaire') ? 'selected' : ''; ?>>Carte bancaire</option>
                <option value="Espèces" <?php echo (isset($paiement['METHODE_PAIEMENT']) && $paiement['METHODE_PAIEMENT'] == 'Espèces') ? 'selected' : ''; ?>>Espèces</option>
                <option value="Virement" <?php echo (isset($paiement['METHODE_PAIEMENT']) && $paiement['METHODE_PAIEMENT'] == 'Virement') ? 'selected' : ''; ?>>Virement</option>
                <option value="Chèque" <?php echo (isset($paiement['METHODE_PAIEMENT']) && $paiement['METHODE_PAIEMENT'] == 'Chèque') ? 'selected' : ''; ?>>Chèque</option>
              </select>
            </div>
            
            <div class="admin-form-actions">
              <button type="submit" class="admin-btn admin-btn-primary"><i class="bx bx-save"></i> Enregistrer les modifications</button>
              <a href="paiements.php" class="admin-btn">Annuler</a>
            </div>
          </form>
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