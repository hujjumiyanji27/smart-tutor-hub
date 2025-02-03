<?php
include '../components/connect.php';

header('Content-Type: application/json');

if (isset($_GET['tutor_id'])) {
    $tutor_id = $_GET['tutor_id'];

    $query = $conn->prepare("SELECT * FROM `notifications` WHERE tutor_id = ? ORDER BY created_at DESC");
    $query->execute([$tutor_id]);

    $notifications = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($notifications);
} else {
    echo json_encode([]);
}
?>
