
<?php
// course_access_control.php - handles free vs paid course access

include('components/connect.php');

$user_id = $_SESSION['user_id']; // assuming session management is in place
$course_id = $_GET['course_id'];

// Fetch course details
$course_query = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$course_query->execute([$course_id]);
$course = $course_query->fetch();

if ($course) {
    if ($course['is_free']) {
        // Grant access to the free content
        header('Location: watch_video.php?course_id=' . $course_id);
    } else {
        // Check payment status
        $payment_query = $conn->prepare("SELECT * FROM payments WHERE user_id = ? AND course_id = ?");
        $payment_query->execute([$user_id, $course_id]);
        $payment = $payment_query->fetch();

        if ($payment) {
            // Grant access to paid content
            header('Location: watch_video.php?course_id=' . $course_id);
        } else {
            // Redirect to payment page
            header('Location: payment_handler.php?course_id=' . $course_id);
        }
    }
} else {
    echo "Course not found.";
}
?>
