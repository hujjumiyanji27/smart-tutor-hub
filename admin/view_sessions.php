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
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Sessions</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
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
        /* Container for Available Sessions */
.available-sessions {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Individual Session Slot */
.session-slot {
    flex: 1 1 calc(50% - 20px); /* Two slots per row */
    min-width: 300px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-left-width: 10px;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

/* Session Slot Hover Effect */
.session-slot:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Green Indicator for Available Slots */
.session-slot.green {
    border-left-color: green;
}

/* Red Indicator for Booked Slots (if needed) */
.session-slot.red {
    border-left-color: red;
}

/* Session Time Text */
.session-slot p {
    font-size: 16px;
    color: #333;
    margin: 0 0 10px;
}

/* Form Inside the Slot */
.session-slot form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Label Styling */
.session-slot form label {
    font-size: 14px;
    color: #555;
}

/* Dropdown Select */
.session-slot form select {
    width: 100%;
    padding: 8px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease;
}

.session-slot form select:focus {
    border-color: green;
}

/* Book Session Button */
.session-slot form button {
    padding: 10px 20px;
    font-size: 14px;
    color: #fff;
    background-color: green;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    text-align: center;
}

.session-slot form button:hover {
    background-color: darkgreen;
    transform: scale(1.05);
}

/* Responsive Design */
@media (max-width: 768px) {
    .session-slot {
        flex: 1 1 100%; /* One slot per row on smaller screens */
    }

    .available-sessions {
        padding: 10px;
        gap: 15px;
    }
}

    </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
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













<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>