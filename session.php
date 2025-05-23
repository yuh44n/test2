<?php
require_once 'init.php';
if(!isset($_SESSION['ID_CLIENT'])){
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1> Welcome, <?php echo $_SESSION['PRENOM']?> </h1>
    <p>This is your Dashboard</p>
    <a href="logout.php"></a>
</body>
</html>
