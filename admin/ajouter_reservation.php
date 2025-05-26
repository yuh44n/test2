<?php
require_once '../init.php';

// Verify admin
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

$clients = [];
$chambres = [];

// Fetch clients for dropdown - Corrected column names
$clients_sql = "SELECT ID_CLIENT, NOM, PRENOM FROM client ORDER BY NOM";
$clients_result = mysqli_query($conn, $clients_sql);
if ($clients_result) {
    while ($row = mysqli_fetch_assoc($clients_result)) {
        // Use correct column names NOM and PRENOM
        $clients[] = $row;
    }
    mysqli_free_result($clients_result);
}

$chambres_sql = "SELECT ID_CHAMBRE, NUMERO_CHAMBRE FROM chambre ORDER BY NUMERO_CHAMBRE";
$chambres_result = mysqli_query($conn, $chambres_sql);
if ($chambres_result) {
    while ($row = mysqli_fetch_assoc($chambres_result)) {
        $chambres[] = $row;
    }
    mysqli_free_result($chambres_result);
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_client = $_POST['id_client'] ?? '';
    $id_chambre = $_POST['id_chambre'] ?? '';
    $date_arrivee = $_POST['date_arrivee'] ?? '';
    $date_depart = $_POST['date_depart'] ?? '';
    $statut_reservation = $_POST['statut_reservation'] ?? 'En attente'; // Default status

    // Basic validation
    if (empty($id_client) || empty($id_chambre) || empty($date_arrivee) || empty($date_depart)) {
        $message = "Veuillez remplir tous les champs requis.";
        $message_type = 'error';
    } else {
        // Prepare and execute the insert statement
        $insert_sql = "INSERT INTO reservation (ID_CLIENT, ID_CHAMBRE, DATE_ARRIVEE, DATE_DEPART, STATUT_RESERVATION) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisss", $id_client, $id_chambre, $date_arrivee, $date_depart, $statut_reservation);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Réservation ajoutée avec succès.";
                $message_type = 'success';
                // Redirect after successful insertion
                header('Location: reservations.php');
                exit();
            } else {
                $message = "Erreur lors de l'ajout de la réservation : " . mysqli_error($conn);
                $message_type = 'error';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Erreur de base de données : Impossible de préparer la requête.";
            $message_type = 'error';
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Nouvelle Réservation</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-page-header">
            <h1>Ajouter une Nouvelle Réservation</h1>
        </div>

        <?php if ($message): ?>
            <div class="admin-message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <form action="ajouter_reservation.php" method="POST">
                <div class="form-group">
                    <label for="id_client">Client :</label>
                    <select id="id_client" name="id_client" required>
                        <option value="">Sélectionner un Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['ID_CLIENT']); ?>">
                                <?php // Use correct column names NOM and PRENOM
                                echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_chambre">Chambre :</label>
                    <select id="id_chambre" name="id_chambre" required>
                        <option value="">Sélectionner une Chambre</option>
                        <?php foreach ($chambres as $chambre): ?>
                            <option value="<?php echo htmlspecialchars($chambre['ID_CHAMBRE']); ?>">
                                <?php echo htmlspecialchars($chambre['NUMERO_CHAMBRE']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_arrivee">Date d'Arrivée :</label>
                    <input type="date" id="date_arrivee" name="date_arrivee" required>
                </div>

                <div class="form-group">
                    <label for="date_depart">Date de Départ :</label>
                    <input type="date" id="date_depart" name="date_depart" required>
                </div>

                <div class="form-group">
                    <label for="statut_reservation">Statut :</label>
                    <select id="statut_reservation" name="statut_reservation">
                        <option value="En attente">En attente</option>
                        <option value="Confirmee">Confirmée</option>
                        <option value="Annulee">Annulée</option>
                    </select>
                </div>

                <button type="submit" class="admin-button">Ajouter la Réservation</button>
            </form>
        </div>
    </div>
</body>
</html>