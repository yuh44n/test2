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
        /* Enhanced Reservations Page Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Main Content Styling */
        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Page Header Enhancement */
        .page-header {
            text-align: center;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            margin: 20px 0;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .page-header h2 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #7f8c8d;
            font-weight: 300;
        }

        /* Enhanced Messages */
        .success-message {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
            padding: 20px 25px;
            margin: 20px 0;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);
            font-weight: 500;
            border-left: 5px solid #00a085;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .success-message::before {
            content: '✅';
            font-size: 1.5rem;
        }

        .error-message {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px 25px;
            margin: 20px 0;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
            font-weight: 500;
            border-left: 5px solid #c0392b;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .error-message::before {
            content: '❌';
            font-size: 1.5rem;
        }

        /* Reservations List Container */
        .reservations-list {
            display: grid;
            gap: 25px;
        }

        /* Enhanced Reservation Cards */
        .reservation-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            border-radius: 20px 20px 0 0;
        }

        .reservation-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        /* Reservation Header */
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
            position: relative;
        }

        .reservation-id {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reservation-id::before {
            content: '🏨';
            font-size: 1.5rem;
        }

        .reservation-date {
            color: #7f8c8d;
            font-weight: 500;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* Reservation Details Grid */
        .reservation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .reservation-detail {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px 20px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .reservation-detail:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .reservation-detail strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Enhanced Status Badges */
        .reservation-status {
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .status-confirmee {
            background: linear-gradient(135deg, #00b894, #00a085);
            color: white;
        }

        .status-confirmee::before {
            content: '✅';
        }

        .status-en-attente {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .status-en-attente::before {
            content: '⏳';
        }

        .status-annulee {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .status-annulee::before {
            content: '❌';
        }

        /* Enhanced Cancel Button */
        .reservation-actions {
            text-align: right;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f8f9fa;
        }

        .cancel-reservation-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cancel-reservation-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }

        .cancel-reservation-btn:disabled {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .cancel-reservation-btn i {
            font-size: 1.2rem;
        }

        /* No Reservations State */
        .no-reservations {
            text-align: center;
            padding: 60px 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .no-reservations::before {
            content: '🏨';
            font-size: 4rem;
            display: block;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-reservations p {
            font-size: 1.3rem;
            color: #7f8c8d;
            margin-bottom: 25px;
            font-weight: 300;
        }

        .no-reservations .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .no-reservations .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            main {
                padding: 10px;
            }

            .page-header {
                margin: 10px 0;
                padding: 30px 20px;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .reservation-card {
                padding: 20px;
            }

            .reservation-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .reservation-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .reservation-actions {
                text-align: center;
            }

            .no-reservations {
                padding: 40px 20px;
            }
        }

        @media (max-width: 480px) {
            .reservation-detail {
                padding: 12px 15px;
            }

            .cancel-reservation-btn {
                width: 100%;
                justify-content: center;
            }

            .page-header h2 {
                font-size: 1.8rem;
            }
        }

        /* Animation for cards loading */
        .reservation-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .reservation-card:nth-child(1) { animation-delay: 0.1s; }
        .reservation-card:nth-child(2) { animation-delay: 0.2s; }
        .reservation-card:nth-child(3) { animation-delay: 0.3s; }
        .reservation-card:nth-child(4) { animation-delay: 0.4s; }
        .reservation-card:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status section styling */
        .status-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .status-section strong {
            color: #2c3e50;
            font-size: 1.1rem;
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
                                <strong>🏠 Chambre</strong>
                                N°<?php echo $reservation['NUMERO_CHAMBRE']; ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>📅 Arrivée</strong>
                                <?php echo date('d/m/Y', strtotime($reservation['DATE_ARRIVEE'])); ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>📅 Départ</strong>
                                <?php echo date('d/m/Y', strtotime($reservation['DATE_DEPART'])); ?>
                            </div>
                            <div class="reservation-detail">
                                <strong>⏰ Durée</strong>
                                <?php echo $nb_jours; ?> nuit(s)
                            </div>
                            <div class="reservation-detail">
                                <strong>💰 Prix/nuit</strong>
                                <?php echo $reservation['TARIF']; ?> €
                            </div>
                            <div class="reservation-detail">
                                <strong>💳 Prix total</strong>
                                <?php echo $prix_total; ?> €
                            </div>
                        </div>
                        
                        <div class="status-section">
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
                    <a href="chambres.php" class="btn reserve-btn">
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
