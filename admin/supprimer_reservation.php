<?php
require_once '../init.php';

// Verify admin
if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

// Check reservation ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Add debugging output here
    error_log("Supprimer Reservation: No ID received.");
    $_SESSION['error_message'] = "Invalid reservation ID.";
    header("Location: reservations.php");
    exit();
}

if (isset($_GET['id'])) {
    $reservation_id = $_GET['id'];

    // Add debugging output here
    error_log("Supprimer Reservation: Attempting to delete reservation with ID: " . $reservation_id);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete related entries from associer table
        $delete_associer_sql = "DELETE FROM associer WHERE ID_RESERVATION = ?";
        $stmt_associer = mysqli_prepare($conn, $delete_associer_sql);
        mysqli_stmt_bind_param($stmt_associer, "i", $reservation_id);
        mysqli_stmt_execute($stmt_associer);
        mysqli_stmt_close($stmt_associer);

        // Delete related entries from paiement table
        $delete_paiement_sql = "DELETE FROM paiement WHERE ID_RESERVATION = ?";
        $stmt_paiement = mysqli_prepare($conn, $delete_paiement_sql);
        mysqli_stmt_bind_param($stmt_paiement, "i", $reservation_id);
        mysqli_stmt_execute($stmt_paiement);
        mysqli_stmt_close($stmt_paiement);

        // Delete the reservation
        $delete_reservation_sql = "DELETE FROM reservation WHERE ID_RESERVATION = ?";
        $stmt_reservation = mysqli_prepare($conn, $delete_reservation_sql);
        mysqli_stmt_bind_param($stmt_reservation, "i", $reservation_id);

        if (mysqli_stmt_execute($stmt_reservation)) {
            // Commit transaction
            mysqli_commit($conn);
            // Add debugging output here
            error_log("Supprimer Reservation: Reservation ID " . $reservation_id . " deleted successfully.");
            $_SESSION['success_message'] = "Reservation deleted successfully.";
            header('Location: reservations.php');
            exit();
        } else {
            // Rollback transaction
            mysqli_rollback($conn);
            // Add debugging output here
            error_log("Supprimer Reservation: Error deleting reservation ID " . $reservation_id . ": " . mysqli_error($conn));
            $_SESSION['error_message'] = "Error deleting reservation: " . mysqli_error($conn);
            header('Location: reservations.php');
            exit();
        }

        mysqli_stmt_close($stmt_reservation);

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        // Add debugging output here
        error_log("Supprimer Reservation: Exception occurred for ID " . $reservation_id . ": " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        header('Location: reservations.php');
        exit();
    }

    mysqli_close($conn);
} else {
    // This block should technically not be reached due to the check above, but keeping for completeness
    $_SESSION['error_message'] = "Invalid reservation ID.";
    header('Location: reservations.php');
    exit();
}
?>
exit();