<?php
require_once 'init.php';

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Fonction pour tester la disponibilité d'une chambre
function testRoomAvailability($id_chambre, $date_arrivee, $date_depart) {
    global $conn;
    
    echo "<h3>Test de disponibilité pour la chambre #$id_chambre</h3>";
    echo "<p>Période: du $date_arrivee au $date_depart</p>";
    
    // Requête SQL pour vérifier les chevauchements de dates
    $sql_check = "SELECT * FROM reservation 
                WHERE ID_CHAMBRE = ? 
                AND STATUT_RESERVATION != 'Annulée'
                AND (
                    (DATE_ARRIVEE <= ? AND DATE_DEPART > ?) 
                    OR (DATE_ARRIVEE < ? AND DATE_DEPART >= ?) 
                    OR (? <= DATE_ARRIVEE AND ? >= DATE_DEPART)
                )";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("issssss", 
        $id_chambre, 
        $date_depart, $date_arrivee,  // Cas 1: Réservation existante englobe la nouvelle
        $date_depart, $date_arrivee,  // Cas 2: Nouvelle réservation chevauche la fin d'une existante
        $date_arrivee, $date_depart   // Cas 3: Nouvelle réservation englobe une existante
    );
    
    if ($stmt_check === false) {
        echo "<p style='color: red;'>Erreur de préparation de la requête: " . $conn->error . "</p>";
        return;
    }
    
    if (!$stmt_check->execute()) {
        echo "<p style='color: red;'>Erreur d'exécution de la requête: " . $stmt_check->error . "</p>";
        return;
    }
    
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo "<p style='color: red;'>Cette chambre est déjà réservée pour la période sélectionnée.</p>";
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID Réservation</th><th>Date d'arrivée</th><th>Date de départ</th><th>Statut</th></tr>";
        
        while ($row = $result_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['ID_RESERVATION'] . "</td>";
            echo "<td>" . $row['DATE_ARRIVEE'] . "</td>";
            echo "<td>" . $row['DATE_DEPART'] . "</td>";
            echo "<td>" . $row['STATUT_RESERVATION'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: green;'>Cette chambre est disponible pour la période sélectionnée.</p>";
    }
}

// Afficher toutes les réservations
function showAllReservations() {
    global $conn;
    
    echo "<h3>Toutes les réservations</h3>";
    
    $sql = "SELECT r.*, c.NUMERO_CHAMBRE 
            FROM reservation r 
            JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
            ORDER BY r.DATE_ARRIVEE";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Chambre</th><th>Date d'arrivée</th><th>Date de départ</th><th>Statut</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['ID_RESERVATION'] . "</td>";
            echo "<td>" . $row['NUMERO_CHAMBRE'] . "</td>";
            echo "<td>" . $row['DATE_ARRIVEE'] . "</td>";
            echo "<td>" . $row['DATE_DEPART'] . "</td>";
            echo "<td>" . $row['STATUT_RESERVATION'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucune réservation trouvée.</p>";
    }
}

// Afficher toutes les chambres
function showAllRooms() {
    global $conn;
    
    echo "<h3>Toutes les chambres</h3>";
    
    $sql = "SELECT * FROM chambre ORDER BY ID_CHAMBRE";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Type</th><th>Numéro</th><th>Tarif</th><th>Statut</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['ID_CHAMBRE'] . "</td>";
            echo "<td>" . $row['ID_TYPE_CHAMBRE'] . "</td>";
            echo "<td>" . $row['NUMERO_CHAMBRE'] . "</td>";
            echo "<td>" . $row['TARIF'] . " €</td>";
            echo "<td>" . $row['STATUT_CHAMBRE'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucune chambre trouvée.</p>";
    }
}

// Interface utilisateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Réservation - Hôtel Élégance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #7494ec; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #5a7dd3; }
        .result { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Outil de Test de Réservation</h1>
        
        <div class="section">
            <h2>Tester la disponibilité d'une chambre</h2>
            <form method="post">
                <div class="form-group">
                    <label for="id_chambre">ID de la chambre</label>
                    <select name="id_chambre" id="id_chambre" required>
                        <?php
                        $sql = "SELECT ID_CHAMBRE, NUMERO_CHAMBRE FROM chambre ORDER BY NUMERO_CHAMBRE";
                        $result = $conn->query($sql);
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['ID_CHAMBRE'] . "'>Chambre " . $row['NUMERO_CHAMBRE'] . " (ID: " . $row['ID_CHAMBRE'] . ")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_arrivee">Date d'arrivée</label>
                    <input type="date" name="date_arrivee" id="date_arrivee" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="date_depart">Date de départ</label>
                    <input type="date" name="date_depart" id="date_depart" required value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                </div>
                <button type="submit" name="test_availability">Tester la disponibilité</button>
            </form>
            
            <?php
            if (isset($_POST['test_availability'])) {
                echo "<div class='result'>";
                testRoomAvailability($_POST['id_chambre'], $_POST['date_arrivee'], $_POST['date_depart']);
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="section">
            <h2>Toutes les réservations</h2>
            <?php showAllReservations(); ?>
        </div>
        
        <div class="section">
            <h2>Toutes les chambres</h2>
            <?php showAllRooms(); ?>
        </div>
        
        <p><a href="chambres.php">Retour à la page des chambres</a></p>
    </div>
</body>
</html>
