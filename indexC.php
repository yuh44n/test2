<?php
require_once 'init.php';

// Variables pour les messages
$message = '';
$message_type = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $confirm_email = trim($_POST['confirm_email']);
        $telephone = trim($_POST['telephone']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Validation des champs obligatoires
        if (empty($nom)) $errors[] = "Le nom est requis.";
        if (empty($prenom)) $errors[] = "Le prénom est requis.";
        if (empty($email)) $errors[] = "L'email est requis.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide.";
        if ($email !== $confirm_email) $errors[] = "La confirmation de l'email ne correspond pas.";
        
        // Vérifier si l'email existe déjà pour un autre client
        $check_email_sql = "SELECT ID_CLIENT FROM client WHERE EMAIL = ? AND ID_CLIENT != ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("si", $email, $_SESSION['ID_CLIENT']);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre compte.";
        }
        
        // Validation du mot de passe si fourni
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "Le mot de passe actuel est requis pour changer le mot de passe.";
            } else {
                // Vérifier le mot de passe actuel
                $verify_sql = "SELECT password FROM client WHERE ID_CLIENT = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("i", $_SESSION['ID_CLIENT']);
                $verify_stmt->execute();
                $result = $verify_stmt->get_result()->fetch_assoc();
                
                if (!password_verify($current_password, $result['password'])) {
                    $errors[] = "Le mot de passe actuel est incorrect.";
                }
            }
            

            
            if ($new_password !== $confirm_password) {
                $errors[] = "La confirmation du mot de passe ne correspond pas.";
            }
        }
        
        // Si pas d'erreurs, mettre à jour
        if (empty($errors)) {
            if (!empty($new_password)) {
                // Mise à jour avec nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE client SET NOM = ?, PRENOM = ?, EMAIL = ?, TELEPHONE = ?, password = ? WHERE ID_CLIENT = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssi", $nom, $prenom, $email, $telephone, $hashed_password, $_SESSION['ID_CLIENT']);
            } else {
                // Mise à jour sans changer le mot de passe
                $update_sql = "UPDATE client SET NOM = ?, PRENOM = ?, EMAIL = ?, TELEPHONE = ? WHERE ID_CLIENT = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $nom, $prenom, $email, $telephone, $_SESSION['ID_CLIENT']);
            }
            
            if ($update_stmt->execute()) {
                // Mettre à jour les variables de session
                $_SESSION['NOM'] = $nom;
                $_SESSION['PRENOM'] = $prenom;
                $_SESSION['EMAIL'] = $email;
                
                $message = "Vos informations ont été mises à jour avec succès.";
                $message_type = "success";
            } else {
                $message = "Erreur lors de la mise à jour de vos informations.";
                $message_type = "error";
            }
        } else {
            $message = implode(" ", $errors);
            $message_type = "error";
        }
    }
}

// Récupérer les informations actuelles du client
$client_info = null;
if (isLoggedIn()) {
    $info_sql = "SELECT NOM, PRENOM, EMAIL, TELEPHONE, DATE_INSCRIPTION FROM client WHERE ID_CLIENT = ?";
    $info_stmt = $conn->prepare($info_sql);
    $info_stmt->bind_param("i", $_SESSION['ID_CLIENT']);
    $info_stmt->execute();
    $client_info = $info_stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion Hôtelière</title>
    <link rel="stylesheet" href="main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
    <style>
      .profile-section {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        margin: 2rem auto;
        max-width: 800px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }
      
      .profile-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
      }
      
      .profile-form {
        display: grid;
        gap: 1.5rem;
      }
      
      .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      
      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
      }
      
      .form-group label {
        font-weight: 600;
        color: #333;
      }
      
      .form-group input {
        padding: 0.75rem;
        border: 2px solid #e9ecef;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
      }
      
      .form-group input:focus {
        outline: none;
        border-color: #007bff;
      }
      
      .password-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-top: 1rem;
      }
      
      .password-section h3 {
        margin-bottom: 1rem;
        color: #495057;
      }
      
      .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
      }
      
      .btn-primary {
        background: #007bff;
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
      }
      
      .btn-primary:hover {
        background: #0056b3;
      }
      
      .btn-secondary {
        background: #6c757d;
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease;
      }
      
      .btn-secondary:hover {
        background: #545b62;
      }
      
      .message {
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      
      .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }
      
      .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }
      
      .client-info {
        background: #e3f2fd;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        text-align: center;
      }
      
      @media (max-width: 768px) {
        .form-row {
          grid-template-columns: 1fr;
        }
        
        .form-actions {
          flex-direction: column;
        }
        
        .profile-section {
          margin: 1rem;
          padding: 1rem;
        }
      }
    </style>
  </head>
  <body>
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
          <li><a href="indexC.php" class="active">Accueil</a></li>
          <li><a href="chambres.php">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php">Paiements</a></li>
        </ul>
        <div class="user-actions">
          <?php if (isLoggedIn()): ?>
            <span class="welcome-msg">Bienvenue, <?php echo htmlspecialchars($_SESSION['PRENOM']); ?></span>
            <a href="logout.php" class="btn logout-btn">Déconnexion</a>
          <?php else: ?>
            <a href="login.html" class="btn login-btn">Connexion/Nouveau Client</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Contenu principal -->
    <main>
      <section class="hero">
        <div class="hero-content">
          <h2>Bienvenue à l'Hôtel Élégance</h2>
          <p>Système de gestion hôtelière</p>
        </div>
      </section>

      <?php if (isLoggedIn() && $client_info): ?>
        <section class="profile-section">
          <div class="profile-header">
            <h3><i class="bx bx-user-circle"></i> Mon Profil</h3>
            <div class="client-info">
              <p><strong>Membre depuis le:</strong> <?php echo date('d/m/Y', strtotime($client_info['DATE_INSCRIPTION'])); ?></p>
            </div>
          </div>

          <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
              <i class="bx <?php echo $message_type == 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>"></i>
              <?php echo htmlspecialchars($message); ?>
            </div>
          <?php endif; ?>

          <form method="POST" class="profile-form">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-row">
              <div class="form-group">
                <label for="prenom">
                  <i class="bx bx-user"></i> Prénom *
                </label>
                <input 
                  type="text" 
                  id="prenom" 
                  name="prenom" 
                  value="<?php echo htmlspecialchars($client_info['PRENOM']); ?>" 
                  required
                />
              </div>
              
              <div class="form-group">
                <label for="nom">
                  <i class="bx bx-user"></i> Nom *
                </label>
                <input 
                  type="text" 
                  id="nom" 
                  name="nom" 
                  value="<?php echo htmlspecialchars($client_info['NOM']); ?>" 
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="email">
                  <i class="bx bx-envelope"></i> Email *
                </label>
                <input 
                  type="email" 
                  id="email" 
                  name="email" 
                  value="<?php echo htmlspecialchars($client_info['EMAIL']); ?>" 
                  required
                />
              </div>
              
              <div class="form-group">
                <label for="confirm_email">
                  <i class="bx bx-envelope"></i> Confirmer l'email *
                </label>
                <input 
                  type="email" 
                  id="confirm_email" 
                  name="confirm_email" 
                  value="<?php echo htmlspecialchars($client_info['EMAIL']); ?>" 
                  required
                />
              </div>
            </div>

            <div class="form-group">
              <label for="telephone">
                <i class="bx bx-phone"></i> Téléphone
              </label>
              <input 
                type="tel" 
                id="telephone" 
                name="telephone" 
                value="<?php echo htmlspecialchars($client_info['TELEPHONE'] ?? ''); ?>"
              />
            </div>

            <div class="password-section">
              <h3><i class="bx bx-lock"></i> Changer le mot de passe (optionnel)</h3>
              <p style="margin-bottom: 1rem; color: #6c757d; font-size: 0.9rem;">
                Laissez ces champs vides si vous ne souhaitez pas changer votre mot de passe.
              </p>
              
              <div class="form-group">
                <label for="current_password">Mot de passe actuel</label>
                <input 
                  type="password" 
                  id="current_password" 
                  name="current_password"
                  placeholder="Entrez votre mot de passe actuel"
                />
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="new_password">Nouveau mot de passe</label>
                  <input 
                    type="password" 
                    id="new_password" 
                    name="new_password"
                    placeholder="Entrez votre nouveau mot de passe"
                  />
                </div>
                
                <div class="form-group">
                  <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                  <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password"
                    placeholder="Répétez le nouveau mot de passe"
                  />
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">
                <i class="bx bx-save"></i> Mettre à jour mes informations
              </button>
              <button type="button" class="btn-secondary" onclick="resetForm()">
                <i class="bx bx-reset"></i> Annuler
              </button>
            </div>
          </form>
        </section>
      <?php endif; ?>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script>
      function resetForm() {
        if (confirm('Êtes-vous sûr de vouloir annuler les modifications ?')) {
          location.reload();
        }
      }
      
      // Validation côté client
      document.querySelector('.profile-form').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const confirmEmail = document.getElementById('confirm_email').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const currentPassword = document.getElementById('current_password').value;
        
        if (email !== confirmEmail) {
          e.preventDefault();
          alert('La confirmation de l\'email ne correspond pas.');
          return;
        }
        
        if (newPassword && newPassword !== confirmPassword) {
          e.preventDefault();
          alert('La confirmation du mot de passe ne correspond pas.');
          return;
        }
        
        if (newPassword && !currentPassword) {
          e.preventDefault();
          alert('Veuillez entrer votre mot de passe actuel pour changer de mot de passe.');
          return;
        }
      });
    </script>

    <script src="main.js"></script>
  </body>
</html>