<?php
include '../components/connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['tutor_id'], $data['start_time'], $data['end_time'])) {
    $stmt = $conn->prepare("INSERT INTO `availability` (tutor_id, day, start_time, end_time) VALUES (?, DAYNAME(?), ?, ?)");
    $success = $stmt->execute([
        $data['tutor_id'],
        $data['start_time'],
        $data['start_time'],
        $data['end_time']
    ]);

    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false]);
}
?>
