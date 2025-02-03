<?php
include '../components/connect.php';

// Check if tutor is logged in
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    header('location:login.php');
    exit();
}

// Fetch all sessions for the tutor
$stmt = $conn->prepare("SELECT * FROM availability WHERE tutor_id = ?");
$stmt->execute([$tutor_id]);
$calendar_slots = [];
while ($slot = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $calendar_slots[] = [
        'id' => $slot['availability_id'],
        'title' => $slot['status'] === 'available' ? 'Available' : 'Booked',
        'start' => $slot['start_time'],
        'end' => $slot['end_time'],
        'color' => $slot['status'] === 'available' ? 'green' : 'red',
    ];
}

// Handle deleting or canceling an appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $availability_id = $_POST['availability_id'];

    if ($_POST['action'] === 'delete') {
        $delete_stmt = $conn->prepare("DELETE FROM availability WHERE availability_id = ? AND tutor_id = ?");
        $delete_stmt->execute([$availability_id, $tutor_id]);
        echo "<script>alert('Appointment deleted successfully.'); window.location.href='calendar_view.php';</script>";
    } elseif ($_POST['action'] === 'cancel') {
        $cancel_stmt = $conn->prepare("UPDATE availability SET status = 'available' WHERE availability_id = ? AND tutor_id = ?");
        $cancel_stmt->execute([$availability_id, $tutor_id]);
        echo "<script>alert('Appointment canceled successfully.'); window.location.href='calendar_view.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        #modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background-color: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            width: 300px;
        }
        #modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <h1>Session Calendar</h1>
    <div id="calendar"></div>

    <!-- Modal -->
    <div id="modal-overlay"></div>
    <div id="modal">
        <h3>Manage Appointment</h3>
        <form id="manage-form" method="POST">
            <input type="hidden" name="availability_id" id="availability-id">
            <button type="submit" name="action" value="cancel">Cancel Appointment</button>
            <button type="submit" name="action" value="delete">Delete Appointment</button>
        </form>
        <button onclick="closeModal()">Close</button>
    </div>

    <script>
        // Event handling for FullCalendar
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?= json_encode($calendar_slots); ?>,
                eventClick: function (info) {
                    openModal(info.event.id);
                }
            });
            calendar.render();
        });

        // Modal handling
        function openModal(id) {
            document.getElementById('modal').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
            document.getElementById('availability-id').value = id;
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        }
    </script>
</body>
</html>
