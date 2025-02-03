<?php
session_start();
include '../components/connect.php';

$tutor_id = $_SESSION['tutor_id'];

if (!isset($tutor_id)) {
    header('location:login.php');
    exit();
}

// Fetch total earnings
$query = $conn->prepare("SELECT SUM(amount) AS total_earnings FROM payments WHERE tutor_id = ?");
$query->execute([$tutor_id]);
$row = $query->fetch(PDO::FETCH_ASSOC);
$total_earnings = $row['total_earnings'] ?? 0;

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $paypal_email = $_POST['paypal_email'];
    $withdraw_amount = $_POST['amount'];

    if ($withdraw_amount > 0 && $withdraw_amount <= $total_earnings) {
        $query = $conn->prepare("INSERT INTO withdrawals (tutor_id, amount, paypal_email, status) VALUES (?, ?, ?, 'Pending')");
        $query->execute([$tutor_id, $withdraw_amount, $paypal_email]);
        echo "<script>alert('Withdrawal requested successfully!');</script>";
    } else {
        echo "<script>alert('Invalid withdrawal amount!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Earnings</title>
</head>
<body>
    <h1>Your Earnings</h1>
    <p>Total Earnings: $<?php echo number_format($total_earnings, 2); ?></p>

    <h2>Request Withdrawal</h2>
    <form action="" method="POST">
        <label for="paypal_email">PayPal Email:</label>
        <input type="email" id="paypal_email" name="paypal_email" required>
        <br>
        <label for="amount">Amount ($):</label>
        <input type="number" id="amount" name="amount" step="0.01" required>
        <br>
        <button type="submit" name="withdraw">Withdraw</button>
    </form>
</body>
</html>
