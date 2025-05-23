<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Administration - Hôtel Élégance</title>
    <link rel="stylesheet" href="../main.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
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
          <li><a href="index.php" class="active">Accueil</a></li>
          <li><a href="chambres.php">Chambres</a></li>
          <li><a href="reservations.php">Réservations</a></li>
          <li><a href="paiements.php">Paiements</a></li>
        </ul>
        <div class="user-actions">
          <?php if (isLoggedIn()): ?>
            <span class="welcome-msg">Bienvenue, <?php echo htmlspecialchars($_SESSION['PRENOM']); ?></span>
            <a href="../logout.php" class="btn logout-btn">Déconnexion</a>
          <?php else: ?>
            <a href="../login.html" class="btn login-btn">Connexion</a>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Contenu principal -->
    <main>
      <section class="hero">
        <div class="hero-content">
          <h2>Administration - Hôtel Élégance</h2>
          <p>Panneau d'administration pour la gestion de l'hôtel</p>
          
          <!-- Informations de session pour le débogage - À supprimer en production -->
          <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; text-align: left;">
            <h3>Informations de session</h3>
            <p><strong>ID de session:</strong> <?php echo session_id(); ?></p>
            <p><strong>ID utilisateur:</strong> <?php echo $_SESSION['ID_CLIENT']; ?></p>
            <p><strong>Nom:</strong> <?php echo $_SESSION['PRENOM'] . ' ' . $_SESSION['NOM']; ?></p>
            <p><strong>Rôle:</strong> <?php echo $_SESSION['ROLE'] == 0 ? 'Administrateur' : 'Client'; ?></p>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="../main.js"></script>
  </body>
</html>
