<?php
session_start();
if(!isset($_session['EMAIL'])){
    header("location : login.html");
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
    <h1> Welcome, <?php echo $_session['EMAIL']?> </h1>
    <p>This is your Dashboard</p>
    <a href="logout.php"></a>
</body>
</html>