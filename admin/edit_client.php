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

// Messages de succès/erreur
$message = '';
$message_type = '';

// FIRST: Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    
    
    // Simple validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
        $message = "Tous les champs obligatoires doivent être remplis.";
        $message_type = "error";

    } else {

        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE client SET NOM = ?, PRENOM = ?, EMAIL = ?, TELEPHONE = ?, password = ? WHERE ID_CLIENT = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $nom, $prenom, $email, $telephone, $hashed_password, $client_id);
            } else {
                $sql = "UPDATE client SET NOM = ?, PRENOM = ?, EMAIL = ?, TELEPHONE = ? WHERE ID_CLIENT = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nom, $prenom, $email, $telephone, $client_id);
            }

            
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                
                if ($affected > 0) {
                    $message = "Client mis à jour avec succès! ($affected ligne(s) modifiée(s))";
                    $message_type = "success";
                } else {
                    $message = "Aucune modification détectée. Les données sont peut-être identiques.";
                    $message_type = "info";
                }
            } else {
                $message = "Erreur SQL: " . $stmt->error;
                $message_type = "error";
            }
            
        } catch (Exception $e) {
            $message = "Erreur: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Récupérer les informations actuelles du client
$sql = "SELECT * FROM client WHERE ID_CLIENT = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: clients.php");
    exit();
}

$client = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier Client</title>
    <link rel="stylesheet" href="../main.css" />
    <link rel="stylesheet" href="admin.css" />
</head>
<body>
    <header>
        <div class="admin-logo">
            <h1>Modifier Client</h1>
        </div>
    </header>

    <main style="padding: 20px;">

        <!-- MESSAGE -->
        <?php if (!empty($message)): ?>
            <div class="admin-<?php echo $message_type; ?>-message" style="padding: 10px; margin: 10px 0; border-radius: 5px; background: <?php echo $message_type == 'success' ? '#d4edda' : ($message_type == 'info' ? '#d1ecf1' : '#f8d7da'); ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <div style="background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd;">
            <h2>Modifier: <?php echo htmlspecialchars($client['PRENOM'] . ' ' . $client['NOM']); ?></h2>
            
            <form method="POST" style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>Nom *</label><br>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($client['NOM']); ?>" required style="width: 300px; padding: 5px;" />
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Prénom *</label><br>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($client['PRENOM']); ?>" required style="width: 300px; padding: 5px;" />
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Email *</label><br>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($client['EMAIL']); ?>" required style="width: 300px; padding: 5px;" />
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Téléphone *</label><br>
                    <input type="text" name="telephone" value="<?php echo htmlspecialchars($client['TELEPHONE']); ?>" required style="width: 300px; padding: 5px;" />
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Nouveau mot de passe (optionnel)</label><br>
                    <input type="password" name="new_password" placeholder="Laisser vide pour ne pas changer" style="width: 300px; padding: 5px;" />
                </div>

                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
                    Enregistrer les modifications
                </button>
                
                <a href="clients.php" style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;">
                    Retour
                </a>
            </form>
        </div>


    </main>

    <script>
        // Log form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form is being submitted!');
            console.log('Form method:', this.method);
            console.log('Form action:', this.action);
            
            // Log all form data
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });
    </script>
</body>
</html>