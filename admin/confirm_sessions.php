<?php
session_start();
if (!isset($_SESSION['tutor_id'])) {
    echo "Please login as a tutor.";
    exit();
}

include('../components/connect.php');

// Fetch tutor's pending sessions
$tutor_id = $_SESSION['tutor_id'];
$sessions = [];
$query = "SELECT * FROM sessions WHERE tutor_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute([$tutor_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sessions[] = $row;
}

// Handle session confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm-session'])) {
    $session_id = $_POST['session-id'];
    $query = "UPDATE sessions SET status = 'confirmed' WHERE session_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$session_id]);

    // Send email to student
    sendSessionConfirmationEmailToStudent($session_id);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Sessions</title>
</head>
<body>
    <h2>Pending Sessions</h2>

    <?php foreach ($sessions as $session) { ?>
        <div>
            <p>Student: <?php echo $session['student_name']; ?></p>
            <p>Start Time: <?php echo $session['start_time']; ?></p>
            <p>End Time: <?php echo $session['end_time']; ?></p>
            <form method="POST" action="confirm_sessions.php">
                <input type="hidden" name="session-id" value="<?php echo $session['session_id']; ?>" />
                <button type="submit" name="confirm-session">Confirm Session</button>
            </form>
        </div>
    <?php } ?>
</body>
</html>
