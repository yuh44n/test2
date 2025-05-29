<?php
require_once 'init.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header("Location: login.html");
    exit();
}

// Définir l'encodage des caractères
$conn->set_charset("utf8");

// Déterminer si l'utilisateur est admin
$isAdmin = isAdmin();
$currentUserId = $_SESSION['ID_CLIENT'];

// Variables pour les messages
$payment_message = '';
$payment_error = '';

// Traitement des paiements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'process_payment') {
        $id_reservation = intval($_POST['id_reservation']);
        $montant = floatval($_POST['montant']);
        $moyen_paiement = $_POST['moyen_paiement'];
        
        // Vérifier que la réservation existe et appartient au client (ou admin)
        $sql_check = "SELECT r.*, c.NUMERO_CHAMBRE, c.TARIF 
                      FROM reservation r 
                      JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
                      WHERE r.ID_RESERVATION = ? AND r.STATUT_RESERVATION = 'Confirmée'";
        
        if (!$isAdmin) {
            $sql_check .= " AND r.ID_CLIENT = ?";
        }
        
        $stmt_check = $conn->prepare($sql_check);
        if ($isAdmin) {
            $stmt_check->bind_param("i", $id_reservation);
        } else {
            $stmt_check->bind_param("ii", $id_reservation, $currentUserId);
        }
        
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $reservation = $result_check->fetch_assoc();
            
            // Vérifier qu'il n'y a pas déjà un paiement pour cette réservation
            $sql_payment_check = "SELECT ID_PAIEMENT FROM paiement WHERE ID_RESERVATION = ?";
            $stmt_payment_check = $conn->prepare($sql_payment_check);
            $stmt_payment_check->bind_param("i", $id_reservation);
            $stmt_payment_check->execute();
            $payment_exists = $stmt_payment_check->get_result();
            
            if ($payment_exists->num_rows == 0) {
                // Insérer le paiement dans la base de données
                $sql_insert = "INSERT INTO paiement (ID_RESERVATION, MONTANT, DATE_PAIEMENT, MOYEN_PAIEMENT) 
                              VALUES (?, ?, NOW(), ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("ids", $id_reservation, $montant, $moyen_paiement);
                
                if ($stmt_insert->execute()) {
                    $payment_message = "Paiement enregistré avec succès! Montant: " . number_format($montant, 2) . "€";
                } else {
                    $payment_error = "Erreur lors de l'enregistrement du paiement. Veuillez réessayer.";
                }
            } else {
                $payment_error = "Cette réservation a déjà été payée.";
            }
        } else {
            $payment_error = "Réservation non trouvée ou non autorisée.";
        }
    }
}

// Récupérer les réservations confirmées non payées
if ($isAdmin) {
    $sql_unpaid = "SELECT r.*, c.NUMERO_CHAMBRE, c.TARIF, cl.NOM, cl.PRENOM
                   FROM reservation r 
                   JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
                   JOIN client cl ON r.ID_CLIENT = cl.ID_CLIENT
                   LEFT JOIN paiement p ON r.ID_RESERVATION = p.ID_RESERVATION
                   WHERE r.STATUT_RESERVATION = 'Confirmée' AND p.ID_PAIEMENT IS NULL
                   ORDER BY r.DATE_RESERVATION DESC";
    $stmt_unpaid = $conn->prepare($sql_unpaid);
    $stmt_unpaid->execute();
} else {
    $sql_unpaid = "SELECT r.*, c.NUMERO_CHAMBRE, c.TARIF, cl.NOM, cl.PRENOM
                   FROM reservation r 
                   JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
                   JOIN client cl ON r.ID_CLIENT = cl.ID_CLIENT
                   LEFT JOIN paiement p ON r.ID_RESERVATION = p.ID_RESERVATION
                   WHERE r.STATUT_RESERVATION = 'Confirmée' AND p.ID_PAIEMENT IS NULL AND r.ID_CLIENT = ?
                   ORDER BY r.DATE_RESERVATION DESC";
    $stmt_unpaid = $conn->prepare($sql_unpaid);
    $stmt_unpaid->bind_param("i", $currentUserId);
    $stmt_unpaid->execute();
}

$unpaid_reservations = $stmt_unpaid->get_result()->fetch_all(MYSQLI_ASSOC);

// Requêtes pour récupérer les données de paiement existants
if ($isAdmin) {
    $sql_paiements = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, r.ID_CLIENT
                      FROM paiement p 
                      JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                      JOIN client c ON r.ID_CLIENT = c.ID_CLIENT
                      ORDER BY p.DATE_PAIEMENT DESC";
    
    $sql_stats = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(MONTANT) as revenus_mois,
                    COUNT(*) as completes
                  FROM paiement 
                  WHERE MONTH(DATE_PAIEMENT) = MONTH(CURRENT_DATE()) 
                  AND YEAR(DATE_PAIEMENT) = YEAR(CURRENT_DATE())";
} else {
    $sql_paiements = "SELECT p.*, c.NOM, c.PRENOM, r.ID_RESERVATION, r.DATE_ARRIVEE, r.DATE_DEPART, r.ID_CLIENT
                      FROM paiement p 
                      JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                      JOIN client c ON r.ID_CLIENT = c.ID_CLIENT
                      WHERE r.ID_CLIENT = ? 
                      ORDER BY p.DATE_PAIEMENT DESC";
    
    $sql_stats = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(p.MONTANT) as total_paye,
                    COUNT(*) as completes
                  FROM paiement p
                  JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION
                  WHERE r.ID_CLIENT = ?";
}

// Exécuter les requêtes
try {
    // Récupérer les statistiques
    if ($isAdmin) {
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->execute();
    } else {
        $stmt_stats = $conn->prepare($sql_stats);
        $stmt_stats->bind_param("i", $currentUserId);
        $stmt_stats->execute();
    }
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    // Récupérer les paiements
    if ($isAdmin) {
        $stmt_paiements = $conn->prepare($sql_paiements);
        $stmt_paiements->execute();
    } else {
        $stmt_paiements = $conn->prepare($sql_paiements);
        $stmt_paiements->bind_param("i", $currentUserId);
        $stmt_paiements->execute();
    }
    $result_paiements = $stmt_paiements->get_result();
    $paiements = $result_paiements->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $stats = [
        'total_transactions' => 0,
        'revenus_mois' => 0,
        'total_paye' => 0,
        'completes' => 0
    ];
    $paiements = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $isAdmin ? 'Gestion des Paiements' : 'Mes Paiements'; ?> - Hôtel Élégance</title>
    <link rel="stylesheet" href="main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=EUR"></script>
    <style>
      /* Enhanced Paiements Page Styling */
      .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 4rem 2rem;
        margin-bottom: 3rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
      }

      .page-header h2 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      }

      .page-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin: 0;
      }

      .paiements-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
      }

      /* Messages */
      .success-message, .error-message {
        padding: 1rem 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        font-weight: 500;
      }

      .success-message {
        background: linear-gradient(135deg, #00b894, #00a085);
        color: white;
        border-left: 5px solid #00a085;
      }

      .error-message {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        border-left: 5px solid #c0392b;
      }

      /* Dashboard Summary Cards */
      .dashboard-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
      }

      .summary-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
        position: relative;
        overflow: hidden;
      }

      .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
      }

      .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      }

      .summary-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        background: linear-gradient(135deg, #667eea, #764ba2);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      }

      .summary-info h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: #2c3e50;
      }

      .summary-info p {
        margin: 0;
        color: #7f8c8d;
        font-weight: 500;
      }

      /* Unpaid Reservations Section */
      .unpaid-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 3rem;
      }

      .unpaid-section h3 {
        font-size: 1.8rem;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .reservation-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
      }

      .reservation-card:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      .reservation-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
      }

      .reservation-detail {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      .reservation-detail strong {
        color: #2c3e50;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .reservation-detail span {
        font-size: 1.1rem;
        color: #555;
      }

      /* Payment Form */
      .payment-form {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        margin-top: 1rem;
      }

      .payment-methods {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
      }

      .payment-method {
        flex: 1;
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
      }

      .payment-method:hover {
        border-color: #667eea;
        background: #f8f9fa;
      }

      .payment-method.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      .payment-method i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
      }

      .btn {
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
      }

      .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
      }

      /* PayPal Button Container */
      .paypal-button-container {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        display: none;
      }

      /* Controls Section */
      .paiements-controls {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
      }

      .search-filter {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
      }

      .search-input, .filter-select {
        padding: 0.8rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        min-width: 200px;
      }

      .search-input:focus, .filter-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      /* Table Styling */
      .paiements-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      thead {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
      }

      th, td {
        padding: 1.2rem;
        text-align: left;
        border-bottom: 1px solid #f8f9fa;
      }

      th {
        font-weight: 600;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      tbody tr {
        transition: all 0.3s ease;
      }

      tbody tr:hover {
        background: #f8f9fa;
      }

      /* No data message */
      .no-data {
        text-align: center;
        padding: 3rem;
        color: #7f8c8d;
      }

      .no-data i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #bdc3c7;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .page-header {
          padding: 2rem 1rem;
        }

        .page-header h2 {
          font-size: 2rem;
        }

        .paiements-content {
          padding: 0 1rem;
        }

        .dashboard-summary {
          grid-template-columns: 1fr;
          gap: 1rem;
        }

        .reservation-info {
          grid-template-columns: 1fr;
        }

        .payment-methods {
          flex-direction: column;
        }

        .paiements-controls {
          flex-direction: column;
          align-items: stretch;
        }

        .search-filter {
          flex-direction: column;
        }

        .search-input, .filter-select {
          min-width: auto;
          width: 100%;
        }

        .paiements-table {
          overflow-x: auto;
        }

        table {
          min-width: 800px;
        }

        th, td {
          padding: 0.8rem;
          font-size: 0.9rem;
        }
      }
            /* Styles additionnels pour les badges de méthode de paiement */
      .payment-method-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
      }
      
      .payment-method-badge.paypal {
        background: #0070ba;
        color: white;
      }
      
      .payment-method-badge.espèces,
      .payment-method-badge.cash {
        background: #28a745;
        color: white;
      }
      
      .payment-method-badge.carte,
      .payment-method-badge.card {
        background: #6c757d;
        color: white;
      }
      
      /* Styles pour les méthodes de paiement sélectionnées */
      .payment-method.selected {
        border-color: #667eea !important;
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
        color: white !important;
        transform: scale(1.02);
      }
      
      /* Animation pour les cartes de réservation */
      .reservation-card {
        transition: all 0.3s ease;
      }
      
      .reservation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      }
      
      /* Responsive pour les boutons PayPal */
      @media (max-width: 768px) {
        .paypal-button-container {
          padding: 0.5rem;
        }
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
          <li><a href="<?php echo $isAdmin ? 'admin/index.php' : 'indexC.php'; ?>">Accueil</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/chambres.php' : 'chambres.php'; ?>">Chambres</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/reservations.php' : 'reservations.php'; ?>">Réservations</a></li>
          <li><a href="<?php echo $isAdmin ? 'admin/paiements.php' : 'paiements.php'; ?>" class="active">Paiements</a></li>
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
        <h2><?php echo $isAdmin ? 'Gestion des Paiements' : 'Mes Paiements'; ?></h2>
        <p><?php echo $isAdmin ? 'Suivi et historique des transactions' : 'Effectuez vos paiements et consultez l\'historique'; ?></p>
      </section>
      
      <section class="paiements-content">
        <?php if ($payment_message): ?>
        <div class="success-message">
          <i class="bx bx-check-circle"></i> <?php echo $payment_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($payment_error): ?>
        <div class="error-message">
          <i class="bx bx-error-circle"></i> <?php echo $payment_error; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-summary">
          <?php if ($isAdmin): ?>
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo number_format($stats['revenus_mois'] ?? 0, 0, ',', ' '); ?> €</h3>
              <p>Revenus du mois</p>
            </div>
          </div>
          <?php else: ?>
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-money"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo number_format($stats['total_paye'] ?? 0, 0, ',', ' '); ?> €</h3>
              <p>Total payé</p>
            </div>
          </div>
          <?php endif; ?>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-credit-card"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo $stats['total_transactions'] ?? 0; ?></h3>
              <p>Transactions</p>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="summary-icon">
              <i class="bx bx-time"></i>
            </div>
            <div class="summary-info">
              <h3><?php echo count($unpaid_reservations); ?></h3>
              <p>En attente de paiement</p>
            </div>
          </div>
        </div>

        <!-- Unpaid Reservations Section -->
        <?php if (!empty($unpaid_reservations)): ?>
        <div class="unpaid-section">
          <h3><i class="bx bx-credit-card"></i> Réservations à payer</h3>
          
          <?php foreach ($unpaid_reservations as $reservation): ?>
            <?php
            // Calcul du prix total
            $date_arrivee = new DateTime($reservation['DATE_ARRIVEE']);
            $date_depart = new DateTime($reservation['DATE_DEPART']);
            $nb_jours = $date_depart->diff($date_arrivee)->days;
            $prix_total = $nb_jours * $reservation['TARIF'];
            ?>
            
            <div class="reservation-card">
              <div class="reservation-info">
                <div class="reservation-detail">
                  <strong>Réservation</strong>
                  <span>#<?php echo $reservation['ID_RESERVATION']; ?></span>
                </div>
                <?php if ($isAdmin): ?>
                <div class="reservation-detail">
                  <strong>Client</strong>
                  <span><?php echo htmlspecialchars($reservation['PRENOM'] . ' ' . $reservation['NOM']); ?></span>
                </div>
                <?php endif; ?>
                <div class="reservation-detail">
                  <strong>Chambre</strong>
                  <span>N°<?php echo $reservation['NUMERO_CHAMBRE']; ?></span>
                </div>
                <div class="reservation-detail">
                  <strong>Dates</strong>
                  <span><?php echo date('d/m/Y', strtotime($reservation['DATE_ARRIVEE'])); ?> - <?php echo date('d/m/Y', strtotime($reservation['DATE_DEPART'])); ?></span>
                </div>
                <div class="reservation-detail">
                  <strong>Durée</strong>
                  <span><?php echo $nb_jours; ?> nuit(s)</span>
                </div>
                <div class="reservation-detail">
                  <strong>Montant à payer</strong>
                  <span style="font-size: 1.3rem; font-weight: bold; color: #667eea;"><?php echo $prix_total; ?> €</span>
                </div>
              </div>
              
              <div class="payment-form">
                <h4>Choisir le mode de paiement</h4>
                <form method="post" action="" id="paymentForm<?php echo $reservation['ID_RESERVATION']; ?>">
                  <input type="hidden" name="action" value="process_payment">
                  <input type="hidden" name="id_reservation" value="<?php echo $reservation['ID_RESERVATION']; ?>">
                  <input type="hidden" name="montant" value="<?php echo $prix_total; ?>">
                  
                  <div class="payment-methods">
                    <div class="payment-method" onclick="selectPaymentMethod('cash', <?php echo $reservation['ID_RESERVATION']; ?>)">
                      <i class="bx bx-money"></i>
                      <strong>Espèces</strong>
                      <p>Paiement en espèces</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('paypal', <?php echo $reservation['ID_RESERVATION']; ?>)">
                      <i class="bx bxl-paypal"></i>
                      <strong>PayPal</strong>
                      <p>Paiement par carte</p>
                    </div>
                  </div>
                  
                  <input type="hidden" name="moyen_paiement" id="paymentMethod<?php echo $reservation['ID_RESERVATION']; ?>" value="">
                  
                  <div id="cashPayment<?php echo $reservation['ID_RESERVATION']; ?>" style="display: none;">
                    <button type="submit" class="btn btn-primary">
                      <i class="bx bx-check"></i> Confirmer le paiement en espèces
                    </button>
                  </div>
                  
                  <div id="paypalPayment<?php echo $reservation['ID_RESERVATION']; ?>" class="paypal-button-container" style="display: none;">
                    <div id="paypal-button-container-<?php echo $reservation['ID_RESERVATION']; ?>"></div>
                  </div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="paiements-controls">
          <div class="search-filter">
            <input type="text" placeholder="Rechercher un paiement..." class="search-input" id="searchInput">
            <select class="filter-select" id="statusFilter">
              <option value="all">Tous les paiements</option>
              <option value="Espèces">Espèces</option>
              <option value="PayPal">PayPal</option>
            </select>
            <button class="btn filter-btn" onclick="filterPaiements()">Filtrer</button>
          </div>
        </div>
        
        <div class="paiements-table">
          <?php if (!empty($paiements)): ?>
<table id="paiementsTable">
            <thead>
              <tr>
                <th>ID Paiement</th>
                <?php if ($isAdmin): ?>
                <th>Client</th>
                <?php endif; ?>
                <th>Réservation</th>
                <th>Montant</th>
                <th>Méthode</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paiements as $paiement): ?>
              <tr>
                <td>#PAY-<?php echo str_pad($paiement['ID_PAIEMENT'], 6, '0', STR_PAD_LEFT); ?></td>
                <?php if ($isAdmin): ?>
                <td><?php echo htmlspecialchars(($paiement['PRENOM'] ?? '') . ' ' . ($paiement['NOM'] ?? '')); ?></td>
                <?php endif; ?>
                <td>#RES-<?php echo str_pad($paiement['ID_RESERVATION'], 6, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo number_format($paiement['MONTANT'], 2, ',', ' '); ?> €</td>
                <td>
                  <span class="payment-method-badge <?php echo strtolower($paiement['MOYEN_PAIEMENT']); ?>">
                    <?php if ($paiement['MOYEN_PAIEMENT'] == 'PayPal'): ?>
                      <i class="bx bxl-paypal"></i>
                    <?php else: ?>
                      <i class="bx bx-money"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($paiement['MOYEN_PAIEMENT'] ?? ''); ?>
                  </span>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($paiement['DATE_PAIEMENT'])); ?></td>
                <td class="actions">
                  <button class="btn-icon" title="Voir détails" onclick="viewPaymentDetails(<?php echo $paiement['ID_PAIEMENT']; ?>)">
                    <i class="bx bx-show"></i>
                  </button>
                  <?php if ($isAdmin): ?>
                  <button class="btn-icon" title="Imprimer reçu" onclick="printReceipt(<?php echo $paiement['ID_PAIEMENT']; ?>)">
                    <i class="bx bx-printer"></i>
                  </button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="no-data">
            <i class="bx bx-credit-card"></i>
            <h3>Aucun paiement trouvé</h3>
            <p><?php echo $isAdmin ? 'Aucune transaction enregistrée dans le système.' : 'Vous n\'avez effectué aucun paiement pour le moment.'; ?></p>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="main.js"></script>
  </body>
</html>