<?php 
// Start the session if it hasn't been started, required for admin login check
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php'; // Assumes config.php provides $conn, isAdmin(), redirect(), sanitize(), and formatPrice()

// --- 1. Security Check ---
if (!isAdmin()) {
    redirect('login.php');
}

// --- 2. Handle Status Updates (SECURE using Prepared Statements) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    // Validate and sanitize inputs
    $order_id = intval($_POST['order_id']); // Ensure order_id is an integer

    // Basic status validation (should ideally match a predefined whitelist array)
    $valid_payment_statuses = ['pending', 'paid', 'failed'];
    $valid_order_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    
    // Base SQL update query structure
    $query = "UPDATE orders SET %s = ? WHERE id = ?";
    $status_field = '';
    $status_value = '';
    $success_message = '';

    // Sanitize input strings for database use (though prepared statements handle most of it)
    if ($_POST['action'] === 'payment_status' && isset($_POST['payment_status'])) {
        $status_field = 'payment_status';
        $status_value = sanitize($_POST['payment_status']);
        $success_message = "Payment status updated!";

        // Enforce whitelist check for security and data integrity
        if (!in_array($status_value, $valid_payment_statuses)) {
             $status_value = 'pending'; // Default to safe value if invalid status is sent
        }
    }
    elseif ($_POST['action'] === 'order_status' && isset($_POST['order_status'])) {
        $status_field = 'order_status';
        $status_value = sanitize($_POST['order_status']);
        $success_message = "Order status updated!";
        
        // Enforce whitelist check
        if (!in_array($status_value, $valid_order_statuses)) {
             $status_value = 'pending';
        }
    }
    
    if ($status_field && $status_value) {
        // Construct the final secure query
        $final_query = sprintf($query, $status_field);
        
        $stmt = $conn->prepare($final_query);
        
        if ($stmt) {
            // Bind parameters: 's' for the status string, 'i' for the integer ID
            $stmt->bind_param("si", $status_value, $order_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $success = $success_message;
            } else {
                // Handle case where update failed or no rows changed
                $success = "Status update failed or no changes made.";
            }
            $stmt->close();
        } else {
             // Handle prepare error
             // error_log("Failed to prepare statement: " . $conn->error);
             $success = "Database error during update.";
        }
    }
}

// --- 3. Get Orders (Secure - no user input used in this query) ---
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");

// Check if the query failed (best practice)
if ($orders === false) {
    // error_log("Failed to fetch orders: " . $conn->error);
    $orders = new stdClass(); // Create an empty mock object to prevent fatal errors below
    $orders->num_rows = 0;
    $orders->fetch_assoc = function() { return null; };
}

// Function to safely check and fetch order items (since it's called in a loop)
function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    // Return an empty result object on failure
    return (object)['num_rows' => 0, 'fetch_assoc' => fn() => null];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-4">
                    <h4 class="mb-4"><i class="fas fa-utensils"></i> Admin Panel</h4>
                    <p class="small">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                </div>
                <nav class="nav flex-column px-3">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a class="nav-link active" href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-globe"></i> View Website
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <div class="col-md-10 p-4">
                <h2 class="mb-4">Orders Management</h2>

                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($orders->num_rows > 0): ?>
                                        <?php while($order = $orders->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                            <td><strong><?php echo htmlspecialchars(formatPrice($order['total_amount'])); ?></strong></td>
                                            
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="payment_status">
                                                    <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                                    <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                    </select>
                                                </form>
                                            </td>
                                            
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="order_status">
                                                    <input type="hidden" name="order_id" value="<?php echo intval($order['id']); ?>">
                                                    <select name="order_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="completed" <?php echo $order['order_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                            
                                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo intval($order['id']); ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="orderModal<?php echo intval($order['id']); ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-4">
                                                            <div class="col-md-6">
                                                                <h6>Customer Information</h6>
                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                                                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Order Information</h6>
                                                                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
                                                                <p><strong>Payment Status:</strong> 
                                                                    <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                                        <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                                                    </span>
                                                                </p>
                                                                <p><strong>Order Status:</strong> 
                                                                    <span class="badge bg-info">
                                                                        <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?>
                                                                    </span>
                                                                </p>
                                                                <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                                            </div>
                                                        </div>

                                                        <h6>Order Items</h6>
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Product</th>
                                                                    <th>Quantity</th>
                                                                    <th>Price</th>
                                                                    <th>Subtotal</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                // --- SECURE Query for Order Items ---
                                                                $items = getOrderItems($conn, $order['id']); 
                                                                
                                                                while($item = $items->fetch_assoc()):
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                                    <td><?php echo htmlspecialchars(formatPrice($item['price'])); ?></td>
                                                                    <td><?php echo htmlspecialchars(formatPrice($item['price'] * $item['quantity'])); ?></td>
                                                                </tr>
                                                                <?php endwhile; ?>
                                                                <tr>
                                                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                                    <td><strong><?php echo htmlspecialchars(formatPrice($order['total_amount'])); ?></strong></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <a href="https://wa.me/<?php echo htmlspecialchars(str_replace(['+', ' '], '', $order['customer_phone'])); ?>?text=Hello <?php echo urlencode($order['customer_name']); ?>, regarding your order #<?php echo intval($order['id']); ?>" 
                                                            target="_blank" class="btn btn-success">
                                                            <i class="fab fa-whatsapp"></i> Contact Customer
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No orders yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>