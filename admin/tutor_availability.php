<?php
session_start();
if (!isset($_SESSION['tutor_id'])) {
    echo "Please login as a tutor.";
    exit();
}

include('../components/connect.php');

// Handle tutor availability declaration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set-availability'])) {
    $start_time = $_POST['start-time'];
    $end_time = $_POST['end-time'];
    $tutor_id = $_SESSION['tutor_id']; // Get tutor's ID

    // Insert availability into the database
    $query = "INSERT INTO tutor_availability (tutor_id, start_time, end_time) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$tutor_id, $start_time, $end_time]);

    $message = "Availability declared successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declare Availability</title>
</head>
<body>
    <h2>Declare Your Availability</h2>

    <form method="POST" action="tutor_availability.php">
        <input type="hidden" name="set-availability" value="1">
        <label>Start Time: <input type="datetime-local" name="start-time" required /></label><br />
        <label>End Time: <input type="datetime-local" name="end-time" required /></label><br />
        <button type="submit">Set Availability</button>
    </form>

    <?php if (isset($message)) { echo "<p>$message</p>"; } ?>
</body>
</html>
