<?php
require_once 'init.php';

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// ID du client connecté
$id_client = isset($_SESSION['ID_CLIENT']) ? $_SESSION['ID_CLIENT'] : 1;

// Traitement de l'annulation de réservation
$annulation_message = '';
$annulation_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $id_reservation = $_POST['id_reservation'];
    
    // Vérifier que la réservation appartient bien au client
    $sql_check = "SELECT * FROM reservation WHERE ID_RESERVATION = ? AND ID_CLIENT = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_reservation, $id_client);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $reservation = $result_check->fetch_assoc();
        
        // Vérifier que l'annulation est possible (date d'arrivée future)
        if (strtotime($reservation['DATE_ARRIVEE']) > strtotime(date('Y-m-d'))) {
            // Mettre à jour le statut de la réservation
            $sql_update = "UPDATE reservation SET STATUT_RESERVATION = 'Annulée' WHERE ID_RESERVATION = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $id_reservation);
            
            if ($stmt_update->execute()) {
                $annulation_message = "Votre réservation a été annulée avec succès.";
            } else {
                $annulation_error = "Une erreur est survenue lors de l'annulation. Veuillez réessayer.";
            }
        } else {
            $annulation_error = "Impossible d'annuler une réservation dont la date d'arrivée est déjà passée.";
        }
    } else {
        $annulation_error = "Vous n'êtes pas autorisé à annuler cette réservation.";
    }
}

// Récupération des réservations du client
$sql = "SELECT r.*, c.NUMERO_CHAMBRE, c.TARIF 
        FROM reservation r 
        JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
        WHERE r.ID_CLIENT = ? 
        ORDER BY r.DATE_RESERVATION DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_client);
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Mes Réservations - Hôtel Élégance</title>
    <link rel="stylesheet" href="main.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="chambres_client.css" />
    <style>
        .reservation-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .reservation-id {
            font-weight: bold;
        }
        
        .reservation-date {
            color: #666;
        }
        
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .reservation-detail {
            margin-bottom: 5px;
        }
        
        .reservation-status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .status-confirmee {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-en-attente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-annulee {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .reservation-actions {
            text-align: right;
            margin-top: 10px;
        }
        
        .cancel-reservation-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .cancel-reservation-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .no-reservations {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
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
                <li><a href="indexC.php">Accueil</a></li>
                <li><a href="chambres.php">Chambres</a></li>
                <li><a href="reservations.php" class="active">Réservations</a></li>
                <li><a href="paiements.php">Paiements</a></li>
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
            <h2>Mes Réservations</h2>
            <p>Consultez et gérez vos réservations d'hôtel</p>
        </section>
        
        <?php if ($annulation_message): ?>
        <div class="success-message">
            <?php echo $annulation_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($annulation_error): ?>
        <div class="error-message">
            <?php echo $annulation_error; ?>
        </div>
        <?php endif; ?>
        
        <section class="reservations-list">
            <?php if (count($reservations) > 0): ?>
                <?php foreach ($reservations as $reservation): ?>
                    <?php
                    $status_class = "";
                    switch ($reservation["STATUT_RESERVATION"]) {
                        case "Confirmée":
                            $status_class = "status-confirmee";
                            break;
                        case "En attente":
                            $status_class = "status-en-attente";
                            break;
                        case "Annulée":
                            $status_class = "status-annulee";
                            break;
                    }
                    
                    // Calcul du prix total
                    $date_arrivee = new DateTime($reservation['DATE_ARRIVEE']);
                    $date_depart = new DateTime($reservation['DATE_DEPART']);
                    $nb_jours = $date_depart->diff($date_arrivee)->days;
                    $prix_total = $nb_jours * $reservation['TARIF'];
                    
                    // Vérifier si l'annulation est possible
                    $can_cancel = (strtotime($reservation['DATE_ARRIVEE']) > strtotime(date('Y-m-d'))) && $reservation['STATUT_RESERVATION'] != 'Annulée';
                    ?>
                    
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div class="reservation-id">Réservation #<?php echo $reservation['ID_RESERVATION']; ?></div>
                            <div class="reservation-date">Réservée le <?php echo date('d/m/Y', strtotime($reservation['DATE_RESERVATION'])); ?></div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="reservation-detail">
                                <strong>Chambre:</strong> N°<?php echo $reservation['NUMERO_CHAMBRE']; ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>Arrivée:</strong> <?php echo date('d/m/Y', strtotime($reservation['DATE_ARRIVEE'])); ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>Départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['DATE_DEPART'])); ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>Durée:</strong> <?php echo $nb_jours; ?> nuit(s)
                            </div>
                            <div class="reservation-detail">
                                <strong>Prix/nuit:</strong> <?php echo $reservation['TARIF']; ?> €
                            </div>
                            <div class="reservation-detail">
                                <strong>Prix total:</strong> <?php echo $prix_total; ?> €
                            </div>
                        </div>
                        
                        <div>
                            <strong>Statut:</strong> 
                            <span class="reservation-status <?php echo $status_class; ?>">
                                <?php echo $reservation['STATUT_RESERVATION']; ?>
                            </span>
                        </div>
                        
                        <?php if ($can_cancel): ?>
                        <div class="reservation-actions">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation?');">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id_reservation" value="<?php echo $reservation['ID_RESERVATION']; ?>">
                                <button type="submit" class="cancel-reservation-btn">
                                    <i class="bx bx-x"></i> Annuler cette réservation
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reservations">
                    <p>Vous n'avez aucune réservation pour le moment.</p>
                    <a href="chambres.php" class="btn reserve-btn" style="display: inline-block; margin-top: 15px;">
                        <i class="bx bx-calendar-check"></i> Réserver une chambre
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="main.js"></script>
  </body>
</html>
