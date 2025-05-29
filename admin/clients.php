<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

$conn->set_charset("utf8");

// Variables pour la pagination et le filtrage
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Messages de succès/erreur
$message = '';
$message_type = '';

// Gestion des actions (suppression, modification de statut)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_client':
                $client_id = (int)$_POST['client_id'];
                if ($client_id > 0) {
                    // Vérifier que ce n'est pas un admin (ROLE != 0)
                    $check_sql = "SELECT ROLE FROM client WHERE ID_CLIENT = ? AND ROLE != 0";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("i", $client_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $delete_sql = "DELETE FROM client WHERE ID_CLIENT = ?";
                        $delete_stmt = $conn->prepare($delete_sql);
                        $delete_stmt->bind_param("i", $client_id);
                        
                        if ($delete_stmt->execute()) {
                            $message = "Client supprimé avec succès.";
                            $message_type = "success";
                        } else {
                            $message = "Erreur lors de la suppression du client.";
                            $message_type = "error";
                        }
                    } else {
                        $message = "Impossible de supprimer ce client.";
                        $message_type = "error";
                    }
                }
                break;
        }
    }
}

// Construction de la requête avec recherche
$where_clause = "WHERE ROLE != 0"; // Exclure les admins
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (NOM LIKE ? OR PRENOM LIKE ? OR EMAIL LIKE ? OR TELEPHONE LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = "ssss";
}

// Requête pour compter le total des clients
$count_sql = "SELECT COUNT(*) as total FROM client $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_clients = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_clients / $limit);

// Requête pour récupérer les clients avec pagination
$sql = "SELECT ID_CLIENT, NOM, PRENOM, EMAIL, TELEPHONE, DATE_INSCRIPTION 
        FROM client 
        $where_clause 
        ORDER BY DATE_INSCRIPTION DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$clients = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des Clients - Hôtel Élégance</title>
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
        <section class="admin-page-header">
            <h2>Gestion des Clients</h2>
            <p>Consultez et gérez les informations des clients de l'hôtel</p>
        </section>

        <?php if (!empty($message)): ?>
            <div class="admin-<?php echo $message_type; ?>-message">
                <i class="bx <?php echo $message_type == 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="admin-controls">
            <div class="admin-search-filter">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Rechercher par nom, prénom, email ou téléphone..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="admin-search-input"
                    />
                    <button type="submit" class="admin-btn">
                        <i class="bx bx-search"></i> Rechercher
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="clients.php" class="admin-btn" style="background: #6c757d;">
                            <i class="bx bx-x"></i> Effacer
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="admin-summary">
                <span><strong><?php echo $total_clients; ?></strong> client(s) trouvé(s)</span>
            </div>
        </section>

        <section class="admin-data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clients->num_rows > 0): ?>
                        <?php while ($client = $clients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['ID_CLIENT']); ?></td>
                                <td><?php echo htmlspecialchars($client['NOM']); ?></td>
                                <td><?php echo htmlspecialchars($client['PRENOM']); ?></td>
                                <td><?php echo htmlspecialchars($client['EMAIL']); ?></td>
                                <td><?php echo htmlspecialchars($client['TELEPHONE']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($client['DATE_INSCRIPTION'])); ?></td>
                                <td class="admin-actions">
                                    <button 
                                        class="admin-btn-icon" 
                                        title="Voir détails"
                                        onclick="viewClient(<?php echo $client['ID_CLIENT']; ?>)"
                                    >
                                        <i class="bx bx-show"></i>
                                    </button>
                                    <button 
                                        class="admin-btn-icon" 
                                        title="Modifier"
                                        onclick="editClient(<?php echo $client['ID_CLIENT']; ?>)"
                                    >
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button 
                                        class="admin-btn-icon" 
                                        title="Supprimer"
                                        onclick="deleteClient(<?php echo $client['ID_CLIENT']; ?>, '<?php echo htmlspecialchars($client['NOM'] . ' ' . $client['PRENOM']); ?>')"
                                        style="color: #dc3545;"
                                    >
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #6c757d;">
                                <i class="bx bx-user-x" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                <?php echo !empty($search) ? 'Aucun client trouvé pour cette recherche.' : 'Aucun client enregistré.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <?php if ($total_pages > 1): ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-btn">
                        <i class="bx bx-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
                
                <span class="admin-pagination-info">
                    Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="admin-pagination-btn">
                        Suivant <i class="bx bx-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <!-- Modal pour la confirmation de suppression -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; max-width: 400px; width: 90%;">
            <h3 style="margin-bottom: 1rem; color: #dc3545;">Confirmer la suppression</h3>
            <p id="deleteMessage" style="margin-bottom: 2rem;"></p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" class="admin-btn" style="background: #6c757d;">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_client">
                    <input type="hidden" name="client_id" id="deleteClientId">
                    <button type="submit" class="admin-btn" style="background: #dc3545;">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewClient(clientId) {
            window.location.href = `client_detail.php?id=${clientId}`;
        }
        
        function editClient(clientId) {
            window.location.href = `edit_client.php?id=${clientId}`;
        }
        
        function deleteClient(clientId, clientName) {
            document.getElementById('deleteMessage').textContent = `Êtes-vous sûr de vouloir supprimer le client "${clientName}" ? Cette action est irréversible.`;
            document.getElementById('deleteClientId').value = clientId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>

    <script src="../main.js"></script>
</body>
</html>