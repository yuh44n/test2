<?php
require_once '../init.php';

// Vérifier si l'utilisateur est un administrateur
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Vérifier si l'ID du paiement est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: paiements.php");
    exit();
}

$id_paiement = intval($_GET['id']);

// Vérifier si le paiement existe
$sql_check = "SELECT ID_PAIEMENT FROM paiement WHERE ID_PAIEMENT = ?";
try {
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("i", $id_paiement);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Paiement non trouvé
        header("Location: paiements.php");
        exit();
    }
} catch (Exception $e) {
    // En cas d'erreur, rediriger vers la page des paiements
    header("Location: paiements.php");
    exit();
}

// Commencer une transaction pour assurer l'intégrité des données
$conn->begin_transaction();

try {
    // 1. D'abord supprimer les enregistrements associés dans la table 'associer'
    $sql_delete_associer = "DELETE FROM associer WHERE ID_PAIEMENT = ?";
    $stmt_associer = $conn->prepare($sql_delete_associer);
    $stmt_associer->bind_param("i", $id_paiement);
    $stmt_associer->execute();
    
    // 2. Ensuite supprimer le paiement
    $sql_delete = "DELETE FROM paiement WHERE ID_PAIEMENT = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id_paiement);
    
    if ($stmt->execute()) {
        // Valider la transaction
        $conn->commit();
        // Rediriger avec un message de succès
        header("Location: paiements.php?message=Le paiement a été supprimé avec succès");
        exit();
    } else {
        // Annuler la transaction en cas d'échec
        $conn->rollback();
        // Rediriger avec un message d'erreur
        header("Location: paiements.php?error=Erreur lors de la suppression du paiement");
        exit();
    }
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    // Rediriger avec un message d'erreur
    header("Location: paiements.php?error=Erreur lors de la suppression: " . urlencode($e->getMessage()));
    exit();
}