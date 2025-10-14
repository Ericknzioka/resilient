<?php
// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    $total = array_reduce($cart, function($sum, $item) {
        return $sum + ($item['price'] * $item['quantity']);
    }, 0);
    $order = [
        'name' => $name,
        'phone' => $phone,
        'items' => $cart,
        'total' => $total,
        'date' => date('Y-m-d H:i:s')
    ];
    $ordersFile = __DIR__ . '/orders.json';
    $orders = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) : [];
    $orders[] = $order;
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    header('Location: order_success.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="mb-4 text-center">Checkout</h3>
                        <form id="checkoutForm" method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <input type="hidden" name="cart" id="cartData">
                            <button type="submit" class="btn btn-primary w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Pass cart data from localStorage to hidden field
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            document.getElementById('cartData').value = localStorage.getItem('cart') || '[]';
        });
    </script>
</body>
</html>
