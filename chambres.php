<?php
require_once 'init.php';

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Traitement du formulaire de réservation
$reservation_message = '';
$reservation_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reserve') {
    $id_chambre = $_POST['id_chambre'];
    $id_client = isset($_SESSION['ID_CLIENT']) ? $_SESSION['ID_CLIENT'] : 1; // À modifier selon votre gestion des clients
    $date_reservation = date('Y-m-d'); // Date actuelle
    $date_arrivee = $_POST['date_arrivee'];
    $date_depart = $_POST['date_depart'];
    $statut_reservation = "En attente";
    
    // Vérifier que la date d'arrivée est postérieure à aujourd'hui
    if (strtotime($date_arrivee) < strtotime(date('Y-m-d'))) {
        $reservation_error = "La date d'arrivée doit être future.";
    }
    // Vérifier que la date de départ est postérieure à la date d'arrivée
    elseif (strtotime($date_depart) <= strtotime($date_arrivee)) {
        $reservation_error = "La date de départ doit être postérieure à la date d'arrivée.";
    }
    else {
        // Vérifier que la chambre est disponible pour cette période
        // CORRECTION: Requête SQL pour vérifier les chevauchements de dates
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
        
        // Pour le débogage
        if ($stmt_check === false) {
            $reservation_error = "Erreur de préparation de la requête: " . $conn->error;
        } else {
            if (!$stmt_check->execute()) {
                $reservation_error = "Erreur d'exécution de la requête: " . $stmt_check->error;
            } else {
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    $reservation_error = "Cette chambre est déjà réservée pour la période sélectionnée.";
                    
                    // Débogage - afficher les réservations en conflit
                    $debug_info = "<div style='font-size: 12px; margin-top: 10px;'>";
                    $debug_info .= "<strong>Réservations en conflit:</strong><br>";
                    while ($row = $result_check->fetch_assoc()) {
                        $debug_info .= "ID: " . $row['ID_RESERVATION'] . 
                                      ", Arrivée: " . $row['DATE_ARRIVEE'] . 
                                      ", Départ: " . $row['DATE_DEPART'] . 
                                      ", Statut: " . $row['STATUT_RESERVATION'] . "<br>";
                    }
                    $debug_info .= "</div>";
                    
                    // Commenter cette ligne en production
                    // $reservation_error .= $debug_info;
                } else {
                    // Insérer la réservation dans la base de données
                    $sql_insert = "INSERT INTO reservation (ID_CLIENT, ID_CHAMBRE, DATE_RESERVATION, DATE_ARRIVEE, DATE_DEPART, STATUT_RESERVATION) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("iissss", $id_client, $id_chambre, $date_reservation, $date_arrivee, $date_depart, $statut_reservation);
                    
                    if ($stmt_insert->execute()) {
                        // Mettre à jour le statut de la chambre si nécessaire (si la réservation est immédiate)
                        if (strtotime($date_arrivee) == strtotime(date('Y-m-d'))) {
                            $sql_update = "UPDATE chambre SET STATUT_CHAMBRE = 'Occupée' WHERE ID_CHAMBRE = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            $stmt_update->bind_param("i", $id_chambre);
                            $stmt_update->execute();
                        }
                        
                        $reservation_message = "Votre réservation a été enregistrée avec succès pour la période du " . date('d/m/Y', strtotime($date_arrivee)) . " au " . date('d/m/Y', strtotime($date_depart)) . ".";
                    } else {
                        $reservation_error = "Une erreur est survenue lors de la réservation. Veuillez réessayer.";
                    }
                }
            }
        }
    }
}

// Récupération des chambres libres uniquement
$sql = "SELECT * FROM chambre WHERE STATUT_CHAMBRE = 'Libre'";
$result = $conn->query($sql);

// Convertir les résultats en tableau pour faciliter l'accès
$chambres = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $chambres[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Réservation - Hôtel Élégance</title>
    <link rel="stylesheet" href="main.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="chambres_client.css" />
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
                <li><a href="chambres.php" class="active">Chambres disponibles</a></li>
                <li><a href="reservations.php">Mes réservations</a></li>
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
            <h2>Chambres Disponibles</h2>
            <p>Découvrez nos chambres disponibles et réservez dès maintenant</p>
        </section>
        
        <?php if ($reservation_message): ?>
        <div class="success-message">
            <?php echo $reservation_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($reservation_error): ?>
        <div class="error-message">
            <?php echo $reservation_error; ?>
        </div>
        <?php endif; ?>
        
        <section class="filtres">
            <div class="search-filter">
                <input type="text" id="searchInput" placeholder="Rechercher une chambre..." class="search-input" onkeyup="filterRooms()">
                <select id="typeFilter" class="filter-select" onchange="filterRooms()">
                    <option value="">Tous les types</option>
                    <?php
                    // Récupérer les types de chambres disponibles
                    $types = [];
                    foreach ($chambres as $chambre) {
                        if (!in_array($chambre['ID_TYPE_CHAMBRE'], $types)) {
                            $types[] = $chambre['ID_TYPE_CHAMBRE'];
                            echo "<option value=\"" . $chambre['ID_TYPE_CHAMBRE'] . "\">Type " . $chambre['ID_TYPE_CHAMBRE'] . "</option>";
                        }
                    }
                    ?>
                </select>
                <select id="priceFilter" class="filter-select" onchange="filterRooms()">
                    <option value="">Tous les prix</option>
                    <option value="100">Moins de 100€</option>
                    <option value="200">Moins de 200€</option>
                    <option value="300">Moins de 300€</option>
                </select>
            </div>
        </section>
        
        <section class="chambres-disponibles">
            <div class="chambres-grid" id="chambresGrid">
                <?php foreach ($chambres as $chambre): ?>
                <div class="chambre-card" data-type="<?php echo $chambre['ID_TYPE_CHAMBRE']; ?>" data-price="<?php echo $chambre['TARIF']; ?>">
                    <div class="chambre-details">
                        <h3>Type <?php echo $chambre['ID_TYPE_CHAMBRE']; ?> - Chambre <?php echo $chambre['NUMERO_CHAMBRE']; ?></h3>
                        <p class="chambre-tarif"><?php echo $chambre['TARIF']; ?> € / nuit</p>
                        <div class="chambre-status status-libre">Disponible</div>
                        <button class="btn reserve-btn" onclick="openReserveModal(<?php echo $chambre['ID_CHAMBRE']; ?>, <?php echo $chambre['ID_TYPE_CHAMBRE']; ?>, <?php echo $chambre['NUMERO_CHAMBRE']; ?>, <?php echo $chambre['TARIF']; ?>)">
                            <i class="bx bx-calendar-check"></i> Réserver
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($chambres) == 0): ?>
                <div class="no-chambres">
                    <p>Aucune chambre disponible pour le moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Modal pour réserver une chambre -->
        <div id="reserveModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('reserveModal')">&times;</span>
                <h3>Réserver une chambre</h3>
                <div id="chambreInfo"></div>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="action" value="reserve">
                    <input type="hidden" name="id_chambre" id="reserve_id_chambre">
                    
                    <div class="form-group">
                        <label for="date_arrivee">Date d'arrivée</label>
                        <input type="date" name="date_arrivee" id="date_arrivee" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_depart">Date de départ</label>
                        <input type="date" name="date_depart" id="date_depart" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="prix-total">
                        <p>Prix total estimé: <span id="prixTotal">0</span> €</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn cancel-btn" onclick="closeModal('reserveModal')">Annuler</button>
                        <button type="submit" class="btn submit-btn">Confirmer la réservation</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer simple -->
    <footer>
        <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="main.js"></script>
    <script src="chambres_client.js"></script>
</body>
</html>
