<?php
// Include necessary dependencies
require '../vendor/autoload.php';

// Hardcoded Email Credentials
define('EMAIL_USERNAME', 'huzefzadaa410@gmail.com'); // Replace with your email
define('EMAIL_PASSWORD', 'fhaevqxrqkufpalo');        // Replace with your app-specific password

// Debugging: Verify credentials
if (empty(EMAIL_USERNAME) || empty(EMAIL_PASSWORD)) {
    die("Email credentials are not set. Please define them in the script.");
}

// Check if the tutor is logged in via cookie
if (!isset($_COOKIE['tutor_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$tutor_id = $_COOKIE['tutor_id']; // Fetch tutor's ID from the cookie

// Include the connection file
include('../components/connect.php');

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch tutor's sessions for the calendar
$sessions = [];
$query = "SELECT * FROM session WHERE tutor_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$tutor_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sessions[] = [
        'title' => "Session {$row['session_id']}",
        'start' => date('c', strtotime($row['start_time'])), // Convert to ISO 8601 format
        'end' => date('c', strtotime($row['end_time'])),     // Convert to ISO 8601 format
        'color' => $row['status'] == 'booked' ? 'green' : 'orange'
    ];
}

// Handle session booking
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book-session'])) {
    $start_time = $_POST['start-time'];
    $end_time = $_POST['end-time'];
    $user_id = $_POST['user-id']; // Get the user ID (who is booking the session)
    $status = 'pending'; // Default session status

    // Validate input
    if (empty($start_time) || empty($end_time) || empty($user_id)) {
        $message = "Please fill all fields.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $message = "End time must be after start time.";
    } else {
        // Insert session into the database
        $query = "INSERT INTO session (tutor_id, user_id, start_time, end_time, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        if ($stmt->execute([$tutor_id, $user_id, $start_time, $end_time, $status])) {
            // Send email notification to the user
            sendEmailNotification($user_id, $start_time, $end_time);
            $message = "Session booked successfully!";
        } else {
            $message = "Failed to book session. Please try again.";
        }
    }
}

// Function to send email notification
function sendEmailNotification($user_id, $start_time, $end_time) {
    global $conn;

    // Fetch user details from the database
    $query = "SELECT email, name FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['email'])) {
        error_log("User email not found for user ID: $user_id");
        return; // Exit if user not found or email is missing
    }

    $user_email = $user['email'];
    $user_name = $user['name'] ?? 'User'; // Use the user's name or default to "User"

    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME; // Use hardcoded email
        $mail->Password = EMAIL_PASSWORD; // Use hardcoded password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Enable debugging for development (optional)
        $mail->SMTPDebug = 0; // Set to 2 for verbose output

        // Recipients
        $mail->setFrom(EMAIL_USERNAME, 'Smart Tutor Hub');
        $mail->addAddress($user_email); // Add the recipient's email address

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your Session Booking Confirmation';

        $mail->Body = "
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }
                .email-header {
                    background-color: #4CAF50;
                    color: white;
                    text-align: center;
                    padding: 20px;
                }
                .email-header h2 {
                    margin: 0;
                    font-size: 24px;
                }
                .email-body {
                    padding: 20px;
                    line-height: 1.6;
                }
                .email-footer {
                    text-align: center;
                    background-color: #f4f4f4;
                    padding: 15px;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>Session Booking Confirmation</h2>
                </div>
                <div class='email-body'>
                    <p>Hi <strong>{$user_name}</strong>,</p>
                    <p>Your session has been successfully booked! Below are the session details:</p>
                    <ul>
                        <li><strong>Start Time:</strong> {$start_time}</li>
                        <li><strong>End Time:</strong> {$end_time}</li>
                        <li><strong>Status:</strong> Pending</li>
                    </ul>
                    <p>Thank you for choosing Smart Tutor Hub!</p>
                </div>
                <div class='email-footer'>
                    &copy; 2025 Smart Tutor Hub. All rights reserved.
                </div>
            </div>
        </body>
        </html>";

        // Send the email
        $mail->send();
        error_log("Email sent successfully to: $user_email");
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Scheduling</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        #calendar { max-width: 900px; margin: 40px auto; }
        .btn { background-color: #6c63ff; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        .btn:hover { background-color: #594ecf; }
    </style>
</head>
<body>
<div class="dashboard-content">
    <h2>Session Scheduling</h2>
    <p>Manage your availability and book sessions here.</p>
    <div id="calendar"></div>
    <h3>Book a Session</h3>
    <form method="POST" action="">
        <input type="hidden" name="book-session" value="1">
        <label>Tutor ID: <input type="text" name="tutor-id" required value="<?= htmlspecialchars($tutor_id) ?>" readonly /></label><br />
        <label>User ID: <input type="text" name="user-id" required /></label><br />
        <label>Start Time: <input type="datetime-local" name="start-time" required /></label><br />
        <label>End Time: <input type="datetime-local" name="end-time" required /></label><br />
        <button type="submit" class="btn">Book Session</button>
    </form>
    <?php if (!empty($message)): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: <?= json_encode($sessions) ?>
        });
        calendar.render();
    });
</script>
</body>
</html>
