<?php
session_start();
include '../components/connect.php';

$tutor_id = $_SESSION['tutor_id'];

if (!isset($tutor_id)) {
    header('location:login.php');
    exit();
}

// Fetch notifications
$query = $conn->prepare("SELECT * FROM notifications WHERE tutor_id = ? ORDER BY created_at DESC");
$query->execute([$tutor_id]);
$notifications = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
</head>
<body>
    <h1>Your Notifications</h1>
    <ul>
        <?php foreach ($notifications as $notification): ?>
            <li><?php echo htmlspecialchars($notification['message']); ?> - <?php echo $notification['created_at']; ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
