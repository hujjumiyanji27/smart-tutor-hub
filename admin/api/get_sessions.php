<?php
include '../components/connect.php';

header('Content-Type: application/json');

if (isset($_GET['tutor_id'])) {
    $tutor_id = $_GET['tutor_id'];

    $query = $conn->prepare("SELECT id, start_time AS start, end_time AS end, status FROM `sessions` WHERE tutor_id = ?");
    $query->execute([$tutor_id]);

    $sessions = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sessions);
} else {
    echo json_encode([]);
}
?>
