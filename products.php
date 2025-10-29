<?php
require_once 'config.php';

// Initialize PDO and fetch products
try {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If DB is unavailable, show empty product list and log the error
    error_log('Products fetch failed: ' . $e->getMessage());
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --light-blue: #e3f2fd;
        }
        .navbar, .footer {
            background-color: var(--primary-blue);
        }
        .product-card {
            transition: transform 0.3s;
            border: 1px solid #e0e0e0;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                Resilient Kitchen
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#shop-category">Shop by Category</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="#" id="cartIcon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-danger cart-badge" id="cartCount">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Products Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Kitchen Products</h2>
            
            <div class="row">
                <?php foreach($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card product-card h-100">
                            <?php if(!empty($product['image'])): ?>
                                <?php
                                    // If image already contains a path use it, otherwise use category subfolder
                                    $imgPath = $product['image'];
                                    if (strpos($imgPath, '/') === false && !empty($product['category'])) {
                                        $imgPath = 'uploads/' . $product['category'] . '/' . $imgPath;
                                    } else {
                                        $imgPath = 'uploads/' . $imgPath;
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($imgPath); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200/0d6efd/ffffff?text=No+Image" class="card-img-top" alt="No Image">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                <p class="card-text flex-grow-1"><?php echo $product['description'] ?: 'High-quality kitchen equipment for professional use.'; ?></p>
                                <div class="mt-auto">
                                    <p class="h4 text-primary">KSh <?php echo number_format($product['price'], 2); ?></p>
                                    <button class="btn btn-primary w-100 add-to-cart" data-id="<?php echo $product['id']; ?>" data-name="<?php echo $product['name']; ?>" data-price="<?php echo $product['price']; ?>">
                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Shop by Category Grid -->
    <div class="container mb-4" id="shop-category">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card p-3 shadow-sm">
                    <h4 class="text-center text-primary mb-3">Shop by Category</h4>
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#kitchen-equipment" class="btn btn-outline-primary w-100">Kitchen Equipment</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#coldrooms" class="btn btn-outline-primary w-100">Coldrooms</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#custom-fabrications" class="btn btn-outline-primary w-100">Custom Fabrications</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#water" class="btn btn-outline-primary w-100">Water</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#milk" class="btn btn-outline-primary w-100">Milk</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#butchery" class="btn btn-outline-primary w-100">Butchery</a></div>
                        <div class="col-6 col-md-3 mb-2"><a href="index.php#bakery" class="btn btn-outline-primary w-100">Bakery</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Your Shopping Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItems"></div>
                    <div class="d-flex justify-content-between mt-3">
                        <h5>Total: KSh <span id="cartTotal">0.00</span></h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                    <button type="button" class="btn btn-primary" id="checkoutBtn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p><i class="fas fa-phone me-2"></i> <?php echo WHATSAPP_NUMBER; ?></p>
                    <p><i class="fab fa-whatsapp me-2"></i> <?php echo WHATSAPP_NUMBER; ?></p>
                </div>
                <div class="col-md-4">
                    <h5>Follow Us</h5>
                    <a href="https://www.tiktok.com/@resilient.modern" class="text-white me-3"><i class="fab fa-tiktok fa-2x"></i></a>
                    <a href="https://www.facebook.com/profile.php?id=100090618168491" class="text-white"><i class="fab fa-facebook fa-2x"></i></a>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cart functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        function updateCartCount() {
            document.getElementById('cartCount').textContent = cart.reduce((total, item) => total + item.quantity, 0);
        }
        
        function updateCartModal() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<p>Your cart is empty.</p>';
                cartTotal.textContent = '0.00';
                return;
            }
            
            let itemsHTML = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                itemsHTML += `
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div>
                            <h6>${item.name}</h6>
                            <p class="mb-0">KSh ${item.price.toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary decrease-quantity" data-index="${index}">-</button>
                            <span class="mx-2">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary increase-quantity" data-index="${index}">+</button>
                            <button class="btn btn-sm btn-danger ms-2 remove-item" data-index="${index}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = itemsHTML;
            cartTotal.textContent = total.toFixed(2);
            
            // Add event listeners to cart buttons
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    if (cart[index].quantity > 1) {
                        cart[index].quantity--;
                    } else {
                        cart.splice(index, 1);
                    }
                    saveCart();
                    updateCartModal();
                    updateCartCount();
                });
            });
            
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart[index].quantity++;
                    saveCart();
                    updateCartModal();
                    updateCartCount();
                });
            });
            
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    cart.splice(index, 1);
                    saveCart();
                    updateCartModal();
                    updateCartCount();
                });
            });
        }
        
        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Add to cart buttons
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const price = parseFloat(this.getAttribute('data-price'));
                    
                    // Check if item already in cart
                    const existingItem = cart.find(item => item.id === id);
                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        cart.push({
                            id: id,
                            name: name,
                            price: price,
                            quantity: 1
                        });
                    }
                    
                    saveCart();
                    updateCartCount();
                    
                    // Show success message
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '11';
                    toast.innerHTML = `
                        <div class="toast show" role="alert">
                            <div class="toast-header">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong class="me-auto">Success</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                ${name} added to cart!
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                });
            });
            
            // Cart icon click
            document.getElementById('cartIcon').addEventListener('click', function(e) {
                e.preventDefault();
                updateCartModal();
                new bootstrap.Modal(document.getElementById('cartModal')).show();
            });
            
            // Checkout button
            document.getElementById('checkoutBtn').addEventListener('click', function() {
                if (cart.length === 0) {
                    alert('Your cart is empty. Please add some products first.');
                    return;
                }
                
                // Redirect to checkout page
                window.location.href = 'checkout.php';
            });
        });
    </script>
</body>
</html>