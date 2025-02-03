<?php
include 'paypal_config.php'; // Include your PayPal config file

// Get the payment ID and payer ID from the URL
$paymentId = $_GET['paymentId'];
$payerId = $_GET['PayerID'];

// PayPal API to execute the payment
$url = PAYPAL_API_URL . '/v1/payments/payment/' . $paymentId . '/execute/';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . getAccessToken()
];

// Prepare the data to execute the payment
$payment_data = json_encode([
    'payer_id' => $payerId
]);

// Execute the API request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payment_data);
$response = curl_exec($ch);

// Handle the response
if ($response) {
    $response_data = json_decode($response, true);
    if ($response_data['state'] == 'approved') {
        // Payment successful - Update payment status in the database
        // For example, update the `payments` table
        $tutor_id = $_COOKIE['tutor_id']; // Get the tutor_id from cookie or session
        $payment_amount = $response_data['transactions'][0]['amount']['total'];
        $currency = $response_data['transactions'][0]['amount']['currency'];

        // Insert payment details into the database
        $stmt = $conn->prepare("INSERT INTO payments (tutor_id, amount, currency, payment_status) VALUES (?, ?, ?, 'completed')");
        $stmt->execute([$tutor_id, $payment_amount, $currency]);

        echo 'Payment successful! Your earnings have been updated.';
    } else {
        echo 'Payment failed. Please try again.';
    }
} else {
    echo 'Error occurred while executing payment. Please try again.';
}

// Function to get the PayPal access token
function getAccessToken() {
    $url = PAYPAL_API_URL . '/v1/oauth2/token';
    $headers = [
        'Authorization: Basic ' . base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $data = 'grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);

    if ($response) {
        $response_data = json_decode($response, true);
        return $response_data['access_token'];
    } else {
        return null;
    }
}
?>
