<?php
include '../components/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability_id'])) {
    session_start();  // Ensure session is started before accessing session variables

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];  // Get the logged-in user's ID
        $availability_id = $_POST['availability_id'];  // Get the availability slot ID

        // Check if availability_id is valid
        if (!empty($availability_id)) {

            // Start transaction to ensure atomic updates
            $conn->begin_transaction();

            try {
                // Get the tutor_id and session time for the selected availability slot
                $sql = "SELECT tutor_id, start_time, end_time FROM availability WHERE availability_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $availability_id);  // Bind the availability ID to the query
                $stmt->execute();
                $stmt->bind_result($tutor_id, $start_time, $end_time);
                $stmt->fetch();  // Fetch the session data
                $stmt->close();

                if ($tutor_id && $start_time && $end_time) {
                    // Insert booking into the sessions table
                    $sql = "INSERT INTO sessions (user_id, tutor_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'booked')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('iiss', $user_id, $tutor_id, $start_time, $end_time);
                    $stmt->execute();  // Execute the insert query
                    $stmt->close();

                    // Update availability status to "booked"
                    $sql = "UPDATE availability SET status = 'booked' WHERE availability_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $availability_id);
                    $stmt->execute();  // Update the status of the availability slot
                    $stmt->close();

                    // Commit transaction
                    $conn->commit();

                    echo 'Session booked successfully!';
                } else {
                    echo 'Invalid availability slot.';
                }
            } catch (Exception $e) {
                $conn->rollback();  // Rollback if there's an error
                echo 'Failed to book session: ' . $e->getMessage();
            }
        } else {
            echo 'Invalid session or availability slot.';
        }
    } else {
        echo 'User not logged in.';
    }
}
?>
