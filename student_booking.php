<?php
session_start();

// Debugging: Check if the student ID is set in the session
if (!isset($_SESSION['user_id'])) {
    echo "Session variable 'student_id' is not set.";
    exit(); // Stop further execution if not logged in
} else {
    echo "Student ID: " . $_SESSION['user_id'];  // Display student ID for debugging purposes
}

include('../components/connect.php'); // Ensure the correct path to connect.php

// Ensure tutor_id is passed in the URL
if (isset($_GET['tutor_id'])) {
    $tutor_id = $_GET['tutor_id']; // Get tutor ID from URL
} else {
    echo "Tutor ID is missing.";
    exit();
}

// Fetch tutor's availability
$availability = [];
$query = "SELECT * FROM tutor_availability WHERE tutor_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$tutor_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $availability[] = [
        'availability_id' => $row['availability_id'],
        'start' => $row['start_time'],
        'end' => $row['end_time'],
    ];
}

// Handle booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book-session'])) {
    $availability_id = $_POST['availability-id'];
    $user_id = $_SESSION['user_id'];

    // Fetch availability details
    $query = "SELECT * FROM tutor_availability WHERE availability_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$availability_id]);
    $availability = $stmt->fetch(PDO::FETCH_ASSOC);

    // Insert session into the sessions table
    $query = "INSERT INTO sessions (user_id, tutor_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id, $tutor_id, $availability['start_time'], $availability['end_time']]);

    // Send email to tutor and student
    sendSessionRequestEmailToTutor($tutor_id, $user_id, $availability['start_time'], $availability['end_time']);
    sendSessionRequestEmailToStudent($user_id, $availability['start_time'], $availability['end_time']);

    $message = "Session request submitted successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session</title>
</head>
<body>
    <h2>Book a Session</h2>

    <form method="POST" action="student_booking.php?tutor_id=<?php echo $tutor_id; ?>">
        <input type="hidden" name="book-session" value="1">
        <label>Select Availability:</label><br />
        <?php foreach ($availability as $slot) { ?>
            <label>
                <input type="radio" name="availability-id" value="<?php echo $slot['availability_id']; ?>" required />
                <?php echo "{$slot['start']} - {$slot['end']}"; ?>
            </label><br />
        <?php } ?>
        <button type="submit">Request Session</button>
    </form>

    <?php if (isset($message)) { echo "<p>$message</p>"; } ?>
</body>
</html>
