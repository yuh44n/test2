<?php
require_once '../init.php';

if (!isLoggedIn() || $_SESSION['ROLE'] != 0) {
    header("Location: ../login.html");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: reservations.php");
    exit();
}

$id_reservation = intval($_GET['id']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statut_reservation = $_POST['statut_reservation'];

    $sql = "UPDATE reservation SET STATUT_RESERVATION = ? WHERE ID_RESERVATION = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $statut_reservation, $id_reservation);

    if ($stmt->execute()) {
        header('Location: reservations.php');
        exit();
    } else {
        $error = "Error updating reservation status.";
    }
}

$sql = "SELECT STATUT_RESERVATION FROM reservation WHERE ID_RESERVATION = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_reservation);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Reservation Status</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .edit-form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #f0f0f0;
        }

        .edit-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            color: #2c3e50;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-page-header">
        <h2>Edit Reservation Status</h2>
        <p>Reservation #<?php echo $id_reservation; ?></p>
    </div>

    <div class="edit-form-container">
        <?php if ($message) echo "<div class='message success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='message error'>$error</div>"; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Status:</label>
                <select name="statut_reservation">
                    <option value="En attente" <?php if ($reservation['STATUT_RESERVATION'] == 'En attente') echo 'selected'; ?>>En attente</option>
                    <option value="Annulee" <?php if ($reservation['STATUT_RESERVATION'] == 'Annulee') echo 'selected'; ?>>Annulée</option>
                    <option value="Confirmee" <?php if ($reservation['STATUT_RESERVATION'] == 'Confirmee') echo 'selected'; ?>>Confirmée</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Update Status</button>
        </form>
    </div>
</body>
</html>