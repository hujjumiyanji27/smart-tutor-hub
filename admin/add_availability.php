<?php
include '../components/connect.php';

// Check if tutor is logged in
if (isset($_COOKIE['tutor_id'])) {
    $tutor_id = $_COOKIE['tutor_id'];
} else {
    header('location:login.php');
    exit();
}

$message = [];

// Handle adding availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (strtotime($start_time) >= strtotime($end_time)) {
        $message[] = "End time must be after start time.";
    } else {
        $stmt = $conn->prepare("INSERT INTO availability (tutor_id, start_time, end_time, status) VALUES (?, ?, ?, 'available')");
        $stmt->execute([$tutor_id, $start_time, $end_time]);
        $message[] = "Availability added successfully.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
<style>
    /* Form Container Styling */
form {
    max-width: 500px;
    margin: 20px auto;
    padding: 20px;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Form Labels */
form label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 16px;
    color: #333;
}

/* Form Inputs */
form input[type="datetime-local"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background-color: #f9f9f9;
    color: #333;
    outline: none;
    transition: border-color 0.3s ease;
}

form input[type="datetime-local"]:focus {
    border-color: #4CAF50;
}

/* Submit Button */
form button {
    background-color: #4CAF50;
    color: #fff;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%;
    text-align: center;
}

form button:hover {
    background-color: #45a049;
    transform: scale(1.02);
}

/* Responsive Design */
@media (max-width: 768px) {
    form {
        padding: 15px;
    }

    form label {
        font-size: 14px;
    }

    form input[type="datetime-local"] {
        font-size: 12px;
    }

    form button {
        font-size: 14px;
        padding: 10px 15px;
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

    <form action="" method="post">
        <label for="start_time">Start Time:</label>
        <input type="datetime-local" name="start_time" required>
        <label for="end_time">End Time:</label>
        <input type="datetime-local" name="end_time" required>
        <button type="submit" name="add_availability">Add Availability</button>
    </form>


<?php include '../components/footer.php'; ?>

<script src="../js/admin_script.js"></script>

</body>
</html>