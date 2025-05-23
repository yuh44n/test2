<?php
require_once '../init.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Cette fonction peut être utilisée au début des pages admin pour vérifier les droits
function requireAdminAccess() {
    if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
        header("Location: ../login.html");
        exit();
    }
}
?>
