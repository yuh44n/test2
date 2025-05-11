<?php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST['NOM']);
    $prenom = trim($_POST['PRENOM']);
    $telephone = trim($_POST['TELEPHONE']);
    $email = trim($_POST['EMAIL']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $date_inscription = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO client (nom, prenom, telephone, email, password, date_inscription) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nom, $prenom, $telephone, $email, $password, $date_inscription);

    if ($stmt->execute()) {
        echo "Signup successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>