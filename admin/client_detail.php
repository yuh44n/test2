<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

$conn->set_charset("utf8");

// Récupérer l'ID du client
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($client_id <= 0) {
    header("Location: clients.php");
    exit();
}

// Récupérer les informations du client
$sql = "SELECT * FROM client WHERE ID_CLIENT = ? AND ROLE != 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: clients.php");
    exit();
}

$client = $result->fetch_assoc();

// Récupérer les réservations du client
$sql_reservations = "SELECT r.*, c.NUMERO_CHAMBRE, c.TYPE_CHAMBRE 
                     FROM reservation r 
                     LEFT JOIN chambre c ON r.ID_CHAMBRE = c.ID_CHAMBRE 
                     WHERE r.ID_CLIENT = ? 
                     ORDER BY r.DATE_DEBUT DESC";
$stmt_reservations = $conn->prepare($sql_reservations);
$stmt_reservations->bind_param("i", $client_id);
$stmt_reservations->execute();
$reservations = $stmt_reservations->get_result();

// Récupérer les paiements du client
$sql_paiements = "SELECT p.*, r.DATE_DEBUT, r.DATE_FIN 
                  FROM paiement p 
                  LEFT JOIN reservation r ON p.ID_RESERVATION = r.ID_RESERVATION 
                  WHERE r.ID_CLIENT = ? 
                  ORDER BY p.DATE_PAIEMENT DESC";
$stmt_paiements = $conn->prepare($sql_paiements);
$stmt_paiements->bind_param("i", $client_id);
$stmt_paiements->execute();
$paiements = $stmt_paiements->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Détails Client - <?php echo htmlspecialchars($client['PRENOM'] . ' ' . $client['NOM']); ?></title>
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
        <div class="admin-nav-toggle" id="navToggle">
            <i class="bx bx-menu"></i>
        </div>
        <nav class="admin-navbar">
            <ul class="admin-nav-links">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="chambres.php">Chambres</a></li>
                <li><a href="reservations.php">Réservations</a></li>
                <li><a href="clients.php" class="active">Clients</a></li>
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

    <main>
        <div class="admin-back-button">
            <a href="clients.php" class="admin-btn">
                <i class="bx bx-arrow-back"></i> Retour à la liste des clients
            </a>
        </div>

        <div class="admin-detail-card">
            <div class="admin-detail-header">
                <h2>
                    <i class="bx bx-user"></i>
                    <?php echo htmlspecialchars($client['PRENOM'] . ' ' . $client['NOM']); ?>
                </h2>
                <div class="admin-detail-actions">
                    <a href="edit_client.php?id=<?php echo $client['ID_CLIENT']; ?>" class="admin-btn">
                        <i class="bx bx-edit"></i> Modifier
                    </a>
                </div>
            </div>

            <div class="admin-detail-content">
                <div class="admin-detail-section">
                    <h4>Informations personnelles</h4>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">ID Client :</span>
                        <span class="admin-detail-value"><?php echo htmlspecialchars($client['ID_CLIENT']); ?></span>
                    </div>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">Nom :</span>
                        <span class="admin-detail-value"><?php echo htmlspecialchars($client['NOM']); ?></span>
                    </div>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">Prénom :</span>
                        <span class="admin-detail-value"><?php echo htmlspecialchars($client['PRENOM']); ?></span>
                    </div>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">Email :</span>
                        <span class="admin-detail-value">
                            <a href="mailto:<?php echo htmlspecialchars($client['EMAIL']); ?>">
                                <?php echo htmlspecialchars($client['EMAIL']); ?>
                            </a>
                        </span>
                    </div>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">Téléphone :</span>
                        <span class="admin-detail-value">
                            <a href="tel:<?php echo htmlspecialchars($client['TELEPHONE']); ?>">
                                <?php echo htmlspecialchars($client['TELEPHONE']); ?>
                            </a>
                        </span>
                    </div>
                    <div class="admin-detail-row">
                        <span class="admin-detail-label">Date d'inscription :</span>
                        <span class="admin-detail-value"><?php echo date('d/m/Y à H:i', strtotime($client['DATE_INSCRIPTION'])); ?></span>
                    </div>
                </div>

                <div class="admin-detail-section">
                    <h4>Historique des réservations</h4>
                    <?php if ($reservations->num_rows > 0): ?>
                        <div class="admin-data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Chambre</th>
                                        <th>Type</th>
                                        <th>Date début</th>
                                        <th>Date fin</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reservation['ID_RESERVATION']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['NUMERO_CHAMBRE']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['TYPE_CHAMBRE']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation['DATE_DEBUT'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reservation['DATE_FIN'])); ?></td>
                                            <td>
                                                <span class="admin-status-badge admin-<?php echo strtolower($reservation['STATUT_RESERVATION']); ?>">
                                                    <?php echo htmlspecialchars($reservation['STATUT_RESERVATION']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: #6c757d; text-align: center; padding: 2rem;">
                            <i class="bx bx-calendar-x" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                            Aucune réservation trouvée pour ce client.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="admin-detail-section">
                    <h4>Historique des paiements</h4>
                    <?php if ($paiements->num_rows > 0): ?>
                        <div class="admin-data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Montant</th>
                                        <th>Date paiement</th>
                                        <th>Méthode</th>
                                        <th>Période séjour</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($paiement = $paiements->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($paiement['ID_PAIEMENT']); ?></td>
                                            <td><strong><?php echo number_format($paiement['MONTANT'], 2, ',', ' '); ?> €</strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($paiement['DATE_PAIEMENT'])); ?></td>
                                            <td><?php echo htmlspecialchars($paiement['METHODE_PAIEMENT']); ?></td>
                                            <td>
                                                <?php if ($paiement['DATE_DEBUT'] && $paiement['DATE_FIN']): ?>
                                                    <?php echo date('d/m/Y', strtotime($paiement['DATE_DEBUT'])); ?> - 
                                                    <?php echo date('d/m/Y', strtotime($paiement['DATE_FIN'])); ?>
                                                <?php else: ?>
                                                    <span style="color: #6c757d;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="color: #6c757d; text-align: center; padding: 2rem;">
                            <i class="bx bx-credit-card-off" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                            Aucun paiement trouvé pour ce client.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
</body>
</html>