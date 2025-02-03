<?php
// Including necessary files
include 'components/connect.php';  // Corrected path assuming 'components' is in the same directory as payment_handler.php
include 'admin/paypal_config.php'; // Corrected path assuming 'admin' is in the same directory as payment_handler.php

// Check if the form was submitted
if (isset($_POST['pay_now'])) {
    // Retrieve POST data and cookies
    $user_id = $_COOKIE['user_id'] ?? ''; // If no user ID in cookies, default to an empty string
    $course_id = $_POST['course_id'] ?? ''; // Safely retrieve course_id
    $amount = $_POST['amount'] ?? ''; // Safely retrieve amount
    
    // Validate input
    if (empty($user_id) || empty($course_id) || empty($amount)) {
        die('Invalid input'); // Ensure these values are provided
    }

    // Check if the course exists
    $select_course = $conn->prepare("SELECT * FROM `playlist` WHERE id = ?");
    $select_course->execute([$course_id]);
    $fetch_course = $select_course->fetch(PDO::FETCH_ASSOC);
    
    if (!$fetch_course) {
        die('Course not found'); // Course doesn't exist
    }

    // Get the course price and validate the payment amount
    $course_price = $fetch_course['price'];
    if ($course_price > 0 && $amount != $course_price) {
        die('Invalid payment amount'); // The amount paid doesn't match the course price
    }

    // Handle PayPal payment for paid courses
    if ($course_price > 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_BASE_URL . "v2/checkout/orders");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'GBP',
                        'value' => $course_price // Send the course price for payment
                    ]
                ]
            ],
            'application_context' => [
                'return_url' => 'http://localhost/test-1/success.php',
                'cancel_url' => 'http://localhost/test-1/cancel.php'
            ]
        ]));
        
        // Execute the cURL request
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            die('Error in cURL request: ' . curl_error($ch)); // Error handling for cURL request failure
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
            header('Location: ' . $paypal_response->links[1]->href); // The link to PayPal's approval page
            exit();
        } else {
            die('Error creating PayPal order: ' . json_encode($paypal_response)); // Display PayPal response for debugging
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
?>
