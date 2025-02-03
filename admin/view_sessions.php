<?php
include '../components/connect.php';

// Fetch available sessions
$stmt = $conn->prepare("SELECT * FROM availability WHERE status = 'available'");
$stmt->execute();
$available_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle session booking
$message = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_session'])) {
    $availability_id = $_POST['availability_id'];
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0; // Get duration, default to 0 if not set
    $user_id = $_COOKIE['user_id'] ?? null; // Assuming the user is logged in and their ID is stored in a cookie

    // Check if the user is logged in
    if (!$user_id) {
        $message[] = "You must be logged in to book a session.";
    } else {
        // Fetch availability details
        $stmt = $conn->prepare("SELECT * FROM availability WHERE availability_id = ? AND status = 'available'");
        $stmt->execute([$availability_id]);
        $availability = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($availability) {
            $start_time = strtotime($availability['start_time']);
            $end_time = strtotime($availability['end_time']);
            $new_end_time = $start_time + ($duration * 60); // Calculate new end time

            // Ensure the booking fits within the available slot
            if ($duration > 0 && $new_end_time <= $end_time) {
                // Update the availability slot
                $stmt = $conn->prepare("UPDATE availability SET start_time = ? WHERE availability_id = ?");
                $stmt->execute([date('Y-m-d H:i:s', $new_end_time), $availability_id]);

                // Add a new record for the booked session
                $stmt = $conn->prepare("INSERT INTO availability (tutor_id, start_time, end_time, status) VALUES (?, ?, ?, 'booked')");
                $stmt->execute([
                    $availability['tutor_id'],
                    date('Y-m-d H:i:s', $start_time),
                    date('Y-m-d H:i:s', $new_end_time),
                ]);

                $message[] = "Session booked successfully!";
            } else {
                $message[] = "Selected duration exceeds available time.";
            }
        } else {
            $message[] = "Selected slot is no longer available.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sessions</title>
    <style>
        .session-slot {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-left-width: 10px;
        }
        .session-slot.green {
            border-left-color: green;
        }
        .session-slot.red {
            border-left-color: red;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php if (!empty($message)) {
        foreach ($message as $msg) {
            echo "<div class='message'>$msg</div>";
        }
    } ?>

    <h1>Available Sessions</h1>
    <div class="available-sessions">
        <?php foreach ($available_slots as $slot) { ?>
            <div class="session-slot green">
                <p><?= $slot['start_time']; ?> - <?= $slot['end_time']; ?></p>
                <form action="" method="post">
                    <input type="hidden" name="availability_id" value="<?= $slot['availability_id']; ?>">
                    <label for="duration">Select Duration:</label>
                    <select name="duration" required>
                        <option value="15">15 Minutes</option>
                        <option value="30">30 Minutes</option>
                        <option value="45">45 Minutes</option>
                        <option value="60">1 Hour</option>
                    </select>
                    <button type="submit" name="book_session">Book Session</button>
                </form>
            </div>
        <?php } ?>
    </div>
</body>
</html>
