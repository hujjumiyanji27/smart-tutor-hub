<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

function sendSessionRequestEmailToTutor($tutor_id, $student_id, $start_time, $end_time) {
    global $conn;
    
    // Fetch tutor and student information
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$tutor_id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tutor email
    $tutor_email = $tutor['email'];

    // Create PHPMailer instance and send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'huzefzadaa410@gmail.com';
        $mail->Password = 'aygm gfzr llyf tkdy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('huzefzadaa410@gmail.com', 'Smart Tutor Hub');
        $mail->addAddress($tutor_email);
        $mail->isHTML(true);
        $mail->Subject = 'Student Session Request';

        $mail->Body = "
        <p>Dear {$tutor['name']},</p>
        <p>Student {$student['name']} has requested a session with you. The details are as follows:</p>
        <table>
            <tr><td>Start Time:</td><td>{$start_time}</td></tr>
            <tr><td>End Time:</td><td>{$end_time}</td></tr>
        </table>
        <p>Please confirm or cancel the session in your dashboard.</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
}

function sendSessionRequestEmailToStudent($student_id, $start_time, $end_time) {
    global $conn;
    
    // Fetch student information
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Student email
    $student_email = $student['email'];

    // Create PHPMailer instance and send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'huzefzadaa410@gmail.com';
        $mail->Password = 'aygm gfzr llyf tkdy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('huzefzadaa410@gmail.com', 'Smart Tutor Hub');
        $mail->addAddress($student_email);
        $mail->isHTML(true);
        $mail->Subject = 'Session Request Received';

        $mail->Body = "
        <p>Dear {$student['name']},</p>
        <p>Your session request is under review by the tutor. You will be notified once the tutor confirms your session.</p>
        <table>
            <tr><td>Start Time:</td><td>{$start_time}</td></tr>
            <tr><td>End Time:</td><td>{$end_time}</td></tr>
        </table>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
