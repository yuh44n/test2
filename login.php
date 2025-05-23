<?php
require_once 'init.php';

// If user is already logged in, redirect to home page
if (isLoggedIn()) {
    header("Location: indexC.php");
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['EMAIL']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT ID_CLIENT, NOM, PRENOM, password, role FROM client WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nom, $prenom, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Use the loginUser function from init.php
            loginUser($id, $nom, $prenom, $role);
            
            // Redirect based on role
            if ($role == 0) {
                header("Location: admin/index.php");
            } else {
                header("Location: indexC.php");
            }
            exit();
        } else {
            $error_message = "Mot de passe invalide.";
        }
    } else {
        $error_message = "Email non trouvé.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Hôtel Élégance</title>
    <link rel="stylesheet" href="login.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <div class="container">
      <?php if (!empty($error_message)): ?>
      <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
        <?php echo $error_message; ?>
      </div>
      <?php endif; ?>
      
      <div class="form-box login">
        <form action="login.php" method="POST">
          <h1>Login</h1>
          <div class="input-box">
            <input type="text" name="EMAIL" placeholder="EMAIL" required />
            <i class="bx bxs-user"></i>
          </div>
          <div class="input-box">
            <input type="password" name="password" placeholder="password" required />
            <i class="bx bxs-lock-alt"></i>
          </div>
          <div class="forgot-link">
            <a href="#">Forgot Password?</a>
          </div>
          <button type="submit" class="btn">Login</button>
        </form>
      </div>

      <div class="form-box register">
       <form action="register.php" method="POST">
          <h1>Registration</h1>
          <div class="input-box">
            <input type="text" name="PRENOM" placeholder="PRENOM" required />
            <i class="bx bxs-user"></i>
          </div>
          <div class="input-box">
            <input type="text" name="NOM" placeholder="NOM" required />
            <i class="bx bxs-user"></i>
          </div>
          <div class="input-box">
            <input type="number" name="TELEPHONE" placeholder="TELEPHONE" required />
            <i class="bx bxs-phone"></i>
          </div>
          <div class="input-box">
            <input type="email" name="EMAIL" placeholder="EMAIL" required />
            <i class="bx bxs-envelope"></i>
          </div>
          <div class="input-box">
            <input type="password" name="password" placeholder="password" required />
            <i class="bx bxs-lock-alt"></i>
          </div>
          <button type="submit" class="btn">Register</button>
        </form>
      </div>

      <div class="toggle-box">
        <div class="toggle-panel toggle-left">
          <h1>Hello, Welcome!</h1>
          <p>Don't have an account?</p>
          <button class="btn register-btn">Register</button>
        </div>

        <div class="toggle-panel toggle-right">
          <h1>Welcome Back!</h1>
          <p>Already have an account?</p>
          <button class="btn login-btn">Login</button>
        </div>
      </div>
    </div>

    <script src="login.js"></script>
  </body>
</html>
