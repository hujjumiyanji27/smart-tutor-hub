<?php
session_start();
include '../components/connect.php';

$tutor_id = $_SESSION['tutor_id'];

if (!isset($tutor_id)) {
    header('location:login.php');
    exit();
}

// Fetch enrolled students
$query = $conn->prepare("SELECT students.name, students.email, enrollments.course_id 
                         FROM enrollments 
                         JOIN students ON enrollments.student_id = students.id 
                         WHERE enrollments.tutor_id = ?");
$query->execute([$tutor_id]);
$students = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
</head>
<body>
    <h1>Enrolled Students</h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Course</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['course_id']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
