<?php
include 'admin/paypal_config.php'; // Include your PayPal config file

// Prepare payment data
$payment_amount = 100; // Example amount to be paid, dynamically fetch it from the database
$currency = 'GBP'; // Currency code (GBP, USD, etc.)

// PayPal API endpoint for creating a payment
$url = PAYPAL_API_URL . '/v1/payments/payment';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . getAccessToken() // Get access token from PayPal
];

// Prepare the payment request body
$payment_data = json_encode([
    'intent' => 'sale',
    'payer' => [
        'payment_method' => 'paypal'
    ],
    'transactions' => [
        [
            'amount' => [
                'total' => $payment_amount,
                'currency' => $currency
            ],
            'description' => 'Payment for tutoring services'
        ]
    ],
    'redirect_urls' => [
        'return_url' => 'http://localhost/test-1/success.php',
        'cancel_url' => 'http://localhost/test-1/cancel.php'
    ]
]);

// Execute the PayPal API request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payment_data);
$response = curl_exec($ch);

// Handle the response
if ($response) {
    $response_data = json_decode($response, true);
    // Redirect user to PayPal approval URL
    header("Location: " . $response_data['links'][1]['href']);
    exit;
} else {
    // Handle error
    echo 'Error occurred. Please try again.';
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
