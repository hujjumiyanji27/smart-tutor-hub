<?php
include '../components/connect.php';

// Check if the tutor is logged in
if(isset($_COOKIE['tutor_id'])){
   $tutor_id = $_COOKIE['tutor_id'];
} else {
   header('location:login.php');
   exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['student_id'])) {
   $message = $_POST['message'];
   $student_id = $_POST['student_id'];

   // Store the message in the database
   $insert_message = $conn->prepare("INSERT INTO tutor_student_messages (tutor_id, student_id, message) VALUES (?, ?, ?)");
   $insert_message->execute([$tutor_id, $student_id, $message]);

   echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);
}
?>
