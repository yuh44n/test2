<?php
session_start();
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
          <li><a href="index.html" class="active">Accueil</a></li>
          <li><a href="chambres.html">Chambres</a></li>
          <li><a href="reservations.html">Réservations</a></li>
          <li><a href="paiements.html">Paiements</a></li>
        </ul>
        <div class="user-actions">
          <?php if (isset($_SESSION['ID_CLIENT'])): ?>
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
    </main>

    <!-- Footer simple -->
    <footer>
      <p>&copy; 2025 Hôtel Élégance - Tous droits réservés</p>
    </footer>

    <script src="main.js"></script>
  </body>
</html>