<?php
include '../components/connect.php';

// Check if tutor is logged in
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    header('location:login.php');
    exit();
}

$message = [];

// Handle adding availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (strtotime($start_time) >= strtotime($end_time)) {
        $message[] = "End time must be after start time.";
    } else {
        $stmt = $conn->prepare("INSERT INTO availability (tutor_id, start_time, end_time, status) VALUES (?, ?, ?, 'available')");
        $stmt->execute([$tutor_id, $start_time, $end_time]);
        $message[] = "Availability added successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Availability</title>
</head>
<body>
    <?php if (!empty($message)) {
        foreach ($message as $msg) {
            echo "<div class='message'>$msg</div>";
        }
    } ?>

    <form action="" method="post">
        <label for="start_time">Start Time:</label>
        <input type="datetime-local" name="start_time" required>
        <label for="end_time">End Time:</label>
        <input type="datetime-local" name="end_time" required>
        <button type="submit" name="add_availability">Add Availability</button>
    </form>
</body>
</html>
