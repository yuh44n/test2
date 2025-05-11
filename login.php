<?php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['EMAIL']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM client WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            echo "Login successful!";
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "Email not found.";
    }

    $stmt->close();
}
?>