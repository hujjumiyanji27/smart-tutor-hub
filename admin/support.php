<?php
session_start();
include '../components/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    $query = $conn->prepare("INSERT INTO support_requests (tutor_id, message) VALUES (?, ?)");
    $query->execute([$_SESSION['tutor_id'], $message]);

    echo "<script>alert('Support request submitted successfully!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support</title>
</head>
<body>
    <h1>Contact Support</h1>
    <form action="" method="POST">
        <textarea name="message" rows="5" required placeholder="Enter your query here..."></textarea>
        <br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
