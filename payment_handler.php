<?php
// Include necessary files
include 'components/connect.php'; // Database connection file
include 'admin/paypal_config.php'; // PayPal configuration file

// Check if the form was submitted via POST
if (isset($_POST['pay_now'])) {
    // Retrieve POST data and cookies
    $user_id = $_COOKIE['user_id'] ?? ''; // User ID from cookies
    $course_id = $_POST['course_id'] ?? ''; // Course ID from POST data
    $amount = $_POST['amount'] ?? ''; // Payment amount from POST data

    // Validate input
    if (empty($user_id) || empty($course_id) || empty($amount)) {
        die('Invalid input: Missing user ID, course ID, or amount.');
    }

    // Check if the course exists in the database
    $select_course = $conn->prepare("SELECT * FROM `playlist` WHERE id = ?");
    $select_course->execute([$course_id]);
    $fetch_course = $select_course->fetch(PDO::FETCH_ASSOC);

    if (!$fetch_course) {
        die('Course not found.');
    }

    // Get the course price and validate the payment amount
    $course_price = $fetch_course['price'];
    if ($course_price > 0 && $amount != $course_price) {
        die('Invalid payment amount.');
    }

    // Handle PayPal payment for paid courses
    if ($course_price > 0) {
        // Initialize cURL for PayPal API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . "v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . getPayPalAccessToken() // Fetch PayPal access token
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'GBP', // Currency code
                        'value' => $course_price // Course price
                    ]
                ]
            ],
            'application_context' => [
                'return_url' => 'http://localhost/test-1/success.php', // Redirect after successful payment
                'cancel_url' => 'http://localhost/test-1/cancel.php' // Redirect if payment is canceled
            ]
        ]));

        // Execute the cURL request
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            die('Error in cURL request: ' . curl_error($ch));
        }
        curl_close($ch);

        // Parse PayPal response
        $paypal_response = json_decode($response);

        if (isset($paypal_response->id)) {
            $paypal_order_id = $paypal_response->id;

            // Store the payment information in the database
            $insert_payment = $conn->prepare("INSERT INTO `payments` (user_id, course_id, amount, paypal_order_id) VALUES (?, ?, ?, ?)");
            $insert_payment->execute([$user_id, $course_id, $amount, $paypal_order_id]);

            // Redirect to PayPal for payment approval
            header('Location: ' . $paypal_response->links[1]->href); // Redirect to PayPal's approval URL
            exit();
        } else {
            die('Error creating PayPal order: ' . json_encode($paypal_response));
        }
    } else {
        // If the course is free, mark it as paid and provide access immediately
        $insert_payment = $conn->prepare("INSERT INTO `payments` (user_id, course_id, amount, paypal_order_id) VALUES (?, ?, ?, ?)");
        $insert_payment->execute([$user_id, $course_id, 0, 'FREE']);

        // Redirect user to course page (immediate access for free courses)
        header('Location: watch_video.php?course_id=' . $course_id);
        exit();
    }
} else {
    // If the form was not submitted or pay_now button was not clicked
    die('Payment not initiated correctly. Please ensure the form was submitted.');
}

/**
 * Function to fetch PayPal access token
 */
function getPayPalAccessToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . "v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        die('Error fetching PayPal access token: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_response = json_decode($response);
    return $token_response->access_token;
}
?>