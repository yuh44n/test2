<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel_base_donnees";

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    // Log the error (to a file or error log)
    error_log("Database connection error: " . $e->getMessage());
    
    // Display a user-friendly message
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>
            <h3>Erreur de connexion à la base de données</h3>
            <p>Veuillez vérifier que le serveur MySQL est en cours d'exécution et que les paramètres de connexion sont corrects.</p>
            <p>Si le problème persiste, contactez l'administrateur du système.</p>
          </div>";
    exit;
}
?>
