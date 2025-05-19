<?php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['EMAIL']);
    $password = $_POST['password'];

    session_start();

    $stmt = $conn->prepare("SELECT ID_CLIENT, NOM, PRENOM, password, role FROM client WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nom, $prenom, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['ID_CLIENT'] = $id;
            $_SESSION['NOM'] = $nom;
            $_SESSION['PRENOM'] = $prenom;
            $_SESSION['ROLE'] = $role;

            // Redirection selon le rôle
            if ($role == 0) {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "Mot de passe invalide.";
        }
    } else {
        echo "Email non trouvé.";
    }

    $stmt->close();
}
?>