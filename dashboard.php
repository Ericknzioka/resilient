<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
// Load orders from file
$ordersFile = __DIR__ . '/../orders.json';
$orders = file_exists($ordersFile) ? json_decode(file_get_contents($ordersFile), true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Orders Dashboard</h2>
        <?php if(count($orders) === 0): ?>
            <div class="alert alert-info">No orders yet.</div>
        <?php else: ?>
            <table class="table table-bordered bg-white">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Items</th>
                        <th>Total (KSh)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $i => $order): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo htmlspecialchars($order['name']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td>
                            <ul>
                                <?php foreach($order['items'] as $item): ?>
                                    <li><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?> (KSh <?php echo number_format($item['price'],2); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><?php echo number_format($order['total'],2); ?></td>
                        <td><?php echo htmlspecialchars($order['date']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-secondary mt-3">Logout</a>
    </div>
</body>
</html>
