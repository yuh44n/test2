<?php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST['NOM']);
    $prenom = trim($_POST['PRENOM']);
    $telephone = trim($_POST['TELEPHONE']);
    $email = trim($_POST['EMAIL']);
    $email_confirm = trim($_POST['EMAIL_CONFIRM']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validation
    $errors = [];
    
    // Check if email and confirmation match
    if ($email !== $email_confirm) {
        $errors[] = "Email addresses do not match.";
    }
    
    // Check if password and confirmation match
    if ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check password strength (optional - adjust as needed)
    if (strlen($password) < 3) {
        $errors[] = "Password must be at least 3 characters long.";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT ID_CLIENT FROM client WHERE EMAIL = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email address already registered.";
    }
    $stmt->close();
    
    // If there are validation errors, return them as JSON for JavaScript to handle
    if (!empty($errors)) {
        echo "<script>
                alert('" . implode("\\n", array_map('addslashes', $errors)) . "');
                window.history.back();
              </script>";
        exit();
    }
    
    // If validation passes, proceed with registration
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $date_inscription = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO client (NOM, PRENOM, TELEPHONE, EMAIL, password, DATE_INSCRIPTION) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nom, $prenom, $telephone, $email, $password_hashed, $date_inscription);

    if ($stmt->execute()) {
        header("Location: success.html");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>