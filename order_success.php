<?php 
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; // Assumes config.php provides $conn, redirect(), and constants like SITE_NAME, etc.

// Check if the order ID is set in the session. If not, redirect.
if (!isset($_SESSION['order_id'])) {
    header("Location: index.php");
    exit();
}

// --- SECURITY FIX: Use Prepared Statements to prevent SQL Injection ---
$order_id = intval($_SESSION['order_id']); // Ensure it's treated as an integer

$order = null;

// Prepare the statement
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");

if ($stmt) {
    // Bind the integer parameter
    $stmt->bind_param("i", $order_id);
    
    // Execute the statement
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch the single row
    $order = $result->fetch_assoc();
    
    // Close the statement
    $stmt->close();
}

// Clear the session variable so the confirmation page isn't shown again on refresh/return
unset($_SESSION['order_id']);

// If the order couldn't be found (e.g., database error or invalid ID), redirect to home
if (!$order) {
    header("Location: index.php");
    exit();
}

// --- FIX for "Can't use function/method return value in write context" ---
// Use defined() instead of isset() for constants.
$site_name_safe = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'E-commerce Site';
$phone_number_safe = defined('PHONE_NUMBER') ? htmlspecialchars(PHONE_NUMBER) : '';
$whatsapp_number_safe = defined('WHATSAPP_NUMBER') ? htmlspecialchars(WHATSAPP_NUMBER) : '';
$facebook_url_safe = defined('FACEBOOK_URL') ? htmlspecialchars(FACEBOOK_URL) : '#';
$tiktok_url_safe = defined('TIKTOK_URL') ? htmlspecialchars(TIKTOK_URL) : '#';
$admin_email_safe = defined('ADMIN_EMAIL') ? htmlspecialchars(ADMIN_EMAIL) : '';

// Prepare WhatsApp link variables
$whatsapp_text = urlencode("I just placed order #{$order['id']}");
$whatsapp_link = "https://wa.me/{$whatsapp_number_safe}?text={$whatsapp_text}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h3 class="mb-4 text-success">Thank you for your order!</h3>
                        <p>Your order has been received. We will contact you soon for delivery.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Clear cart after successful order
        localStorage.removeItem('cart');
    </script>
</body>
</html>