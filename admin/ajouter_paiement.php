<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Récupérer la liste des réservations pour le formulaire
$sql_reservations = "SELECT r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, c.NOM, c.PRENOM 
                     FROM reservation r 
                     JOIN client c ON r.ID_CLIENT = c.ID_CLIENT 
                     ORDER BY r.DATE_ARRIVEE DESC";
$result_reservations = $conn->query($sql_reservations);

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_reservation = $_POST['ID_RESERVATION'];
    $montant = $_POST['MONTANT'];
    $methode = $_POST['METHODE_PAIEMENT'];
    $date = $_POST['DATE_PAIEMENT'];
    
    $sql = "INSERT INTO paiement (ID_RESERVATION, MONTANT, MOYEN_PAIEMENT, DATE_PAIEMENT) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $id_reservation, $montant, $methode, $date);
    
    if ($stmt->execute()) {
        header("Location: paiements.php?message=" . urlencode("Le paiement a été ajouté avec succès."));
        exit();
    } else {
        $error = "Erreur lors de l'ajout du paiement: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ajouter un Paiement - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link rel="stylesheet" href="admin.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>
    <header>
        <div class="admin-logo">
            <i class="bx bx-hotel"></i>
            <h1>Hôtel Élégance</h1>
        </div>
        <nav class="admin-navbar">
            <ul class="admin-nav-links">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="chambres.php">Chambres</a></li>
                <li><a href="reservations.php">Réservations</a></li>
                <li><a href="paiements.php" class="active">Paiements</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="admin-page-header">
            <h2>Ajouter un Paiement</h2>
            <p>Créer un nouveau paiement dans le système</p>
        </section>

        <?php if (isset($error)): ?>
        <div class="admin-error-message">
            <i class="bx bx-error-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="admin-form-container">
            <form method="POST" class="admin-form">
                <div class="admin-form-group">
                    <label for="ID_RESERVATION">Réservation</label>
                    <select name="ID_RESERVATION" id="ID_RESERVATION" required class="admin-input">
                        <option value="">Sélectionner une réservation</option>
                        <?php while($reservation = $result_reservations->fetch_assoc()): ?>
                            <option value="<?php echo $reservation['ID_RESERVATION']; ?>">
                                <?php echo htmlspecialchars($reservation['NOM'] . ' ' . $reservation['PRENOM'] . 
                                    ' - Du ' . date('d/m/Y', strtotime($reservation['DATE_ARRIVEE'])) . 
                                    ' au ' . date('d/m/Y', strtotime($reservation['DATE_DEPART']))); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="MONTANT">Montant (€)</label>
                    <input type="number" name="MONTANT" id="MONTANT" step="0.01" required class="admin-input" />
                </div>

                <div class="admin-form-group">
                    <label for="METHODE_PAIEMENT">Méthode de paiement</label>
                    <select name="METHODE_PAIEMENT" id="METHODE_PAIEMENT" required class="admin-input">
                        <option value="Carte bancaire">Carte bancaire</option>
                        <option value="Espèces">Espèces</option>
                        <option value="Virement">Virement</option>
                        <option value="Chèque">Chèque</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="DATE_PAIEMENT">Date du paiement</label>
                    <input type="date" name="DATE_PAIEMENT" id="DATE_PAIEMENT" required class="admin-input" 
                           value="<?php echo date('Y-m-d'); ?>" />
                </div>

                

                <div class="admin-form-actions">
                    <a href="paiements.php" class="admin-btn admin-cancel-btn">Annuler</a>
                    <button type="submit" class="admin-btn admin-submit-btn">Ajouter le paiement</button>
                </div>
            </form>
        </div>
    </main>

    <script src="../main.js"></script>
</body>
</html>