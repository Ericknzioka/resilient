<?php
include 'config.php';
$pdo = getPDO();

// Fetch featured products
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $featuredProducts = [];
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
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
        .hero-section {
            background: linear-gradient(rgba(13, 110, 253, 0.8), rgba(13, 110, 253, 0.9)), url('https://images.unsplash.com/photo-1556909114-4d0d853e5e25?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .product-card {
            transition: transform 0.3s;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-radius: 10px;
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
        .whatsapp-chat {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25d366;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            transition: background 0.2s;
        }
        .whatsapp-chat:hover {
            background: #128c7e;
            color: #fff;
        }
        .sidebar {
            min-height: 100vh;
            border-right: 1px solid #e0e0e0;
        }
        .card-img-top {
            border-radius: 10px 10px 0 0;
        }
        .category-section {
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 40px;
            padding: 30px 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
            }
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
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

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 mx-auto">
                    <div class="bg-white p-3 rounded shadow-sm d-inline-block" style="font-size:1.2rem;">
                        <span class="fw-bold text-primary">Welcome to Resilient Modern Kitchen Online Shopping Store!</span>
                        <p class="mb-0" style="font-size:1rem;">Shop high-quality, durable kitchen equipment and furniture for commercial and residential use. Enjoy a seamless shopping experience with fast delivery and professional support.</p>
                    </div>
                </div>
            </div>
            <h1 class="display-6 fw-bold mt-4">Professional Kitchen Equipment</h1>
            <p class="lead mb-4">High-quality, durable kitchen furniture and equipment for restaurants, hotels, and homes</p>
            <a href="products.php" class="btn btn-light btn-lg px-5 py-3">Shop Now</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="bg-white p-4 rounded shadow-sm">
                        <h2 class="fw-bold mb-3 text-primary">About Resilient Kitchen</h2>
                        <p class="lead mb-3">We provide high-quality, durable kitchen equipment and furniture for commercial and residential use.</p>
                        <p class="mb-0">With years of experience in the industry, we understand the needs of professional chefs and home cooks alike. Our products are designed to withstand the rigors of daily use while maintaining aesthetic appeal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sidebar and Categories -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h5 class="text-primary">Shop by Category</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="#kitchen-equipment">Kitchen Equipment</a></li>
                        <li class="nav-item"><a class="nav-link" href="#coldrooms">Coldrooms</a></li>
                        <li class="nav-item"><a class="nav-link" href="#custom-fabrications">Custom Fabrications</a></li>
                        <li class="nav-item"><a class="nav-link" href="#water">Water</a></li>
                        <li class="nav-item"><a class="nav-link" href="#milk">Milk</a></li>
                        <li class="nav-item"><a class="nav-link" href="#butchery">Butchery</a></li>
                        <li class="nav-item"><a class="nav-link" href="#bakery">Bakery</a></li>
                    </ul>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-4">
                <!-- Category Galleries -->
                <?php
                $categories = [
                    'kitchen-equipment' => 'Kitchen Equipment',
                    'coldrooms' => 'Coldrooms',
                    'custom-fabrications' => 'Custom Fabrications',
                    'water' => 'Water',
                    'milk' => 'Milk',
                    'butchery' => 'Butchery',
                    'bakery' => 'Bakery'
                ];
                foreach ($categories as $folder => $label):
                    $dir = __DIR__ . "/uploads/$folder";
                    $images = is_dir($dir) ? array_filter(scandir($dir), function($f) {
                        return !is_dir($f) && preg_match('/\.(jpg|jpeg|png)$/i', $f);
                    }) : [];
                ?>
                <section class="py-4" id="<?php echo $folder; ?>">
                    <h3 class="mb-4 text-primary"><?php echo $label; ?></h3>
                    <div class="row">
                        <?php if(count($images) > 0): ?>
                            <?php foreach($images as $img): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card product-card h-100">
                                        <img src="uploads/<?php echo $folder . '/' . $img; ?>" class="card-img-top" alt="<?php echo $img; ?>" style="height: 200px; object-fit: cover;">
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">Product</h5>
                                            <p class="card-text flex-grow-1">High-quality <?php echo $label; ?> item.</p>
                                            <div class="mt-auto">
                                                <?php
                                                // Set prices by category and image name
                                                $price = 10000;
                                                if ($folder === 'butchery') {
                                                    if (stripos($img, 'manual meat mincer') !== false) $price = 5000;
                                                    elseif (stripos($img, 'meat ang grill') !== false) $price = 35000;
                                                    elseif (stripos($img, 'meat grill') !== false) $price = 7000;
                                                    elseif (stripos($img, 'ventilated fridge') !== false) $price = 280000;
                                                } elseif ($folder === 'bakery') {
                                                    if (stripos($img, 'bainmrie') !== false) $price = 85000;
                                                    elseif (stripos($img, 'combined oven cooker') !== false) $price = 250000;
                                                    elseif (stripos($img, 'deck oven') !== false) $price = 19500;
                                                    elseif (stripos($img, 'single deck oven') !== false) $price = 80000;
                                                } elseif ($folder === 'custom-fabrications') {
                                                    if (stripos($img, 'perforated rack') !== false) $price = 50000;
                                                    elseif (stripos($img, 'storage rack') !== false) $price = 35000;
                                                    elseif (stripos($img, 'toy boy trolley') !== false) $price = 30000;
                                                    elseif (stripos($img, 'working table with over shelves') !== false) $price = 40000;
                                                } elseif ($folder === 'kitchen-equipment') {
                                                    if (stripos($img, 'burner gas cooker') !== false) $price = 25000;
                                                    elseif (stripos($img, 'closed cabinet') !== false) $price = 30000;
                                                    elseif (stripos($img, 'coffee grinder') !== false) $price = 95000;
                                                    elseif (stripos($img, 'combined cooker') !== false) $price = 80000;
                                                    elseif (stripos($img, 'combined cooking unit') !== false) $price = 140000;
                                                    elseif (stripos($img, 'combined') !== false) $price = 130000;
                                                    elseif (stripos($img, 'commercial fridge') !== false) $price = 190000;
                                                    elseif (stripos($img, 'deep fryer') !== false) $price = 20000;
                                                    elseif (stripos($img, 'double sink') !== false) $price = 40000;
                                                    elseif (stripos($img, 'ags cooker double bowl') !== false) $price = 55000;
                                                    elseif (stripos($img, 'grease trap') !== false) $price = 35000;
                                                    elseif (stripos($img, 'hospital sluice sink') !== false) $price = 60000;
                                                    elseif (stripos($img, 'kitchen hood structure') !== false) $price = 85000;
                                                    elseif (stripos($img, 'scrub sink') !== false) $price = 35000;
                                                    elseif (stripos($img, 'sigle sink') !== false) $price = 25000;
                                                    elseif (stripos($img, 'sluice sink') !== false) $price = 40000;
                                                    elseif (stripos($img, 'steel sluice sink') !== false) $price = 55000;
                                                } elseif ($folder === 'milk') {
                                                    if (stripos($img, 'ventilated fridge') !== false) $price = 180000;
                                                } elseif ($folder === 'water') {
                                                    if (stripos($img, 'ventilated fridge') !== false) $price = 250000;
                                                }
                                                ?>
                                                <p class="h4 text-primary">KSh <span class="product-price"><?php echo number_format($price, 2); ?></span></p>
                                                <button class="btn btn-primary w-100 add-to-cart" data-id="<?php echo $folder . '-' . $img; ?>" data-name="<?php echo $img; ?>" data-price="<?php echo $price; ?>">
                                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </main>
        </div>
    </div>
    <!-- End Sidebar and Categories -->

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
                    <a href="https://www.tiktok.com/@resilient.modern" class="text-white me-3" target="_blank"><i class="fab fa-tiktok fa-2x"></i></a>
                    <a href="https://www.facebook.com/profile.php?id=100090618168491" class="text-white" target="_blank"><i class="fab fa-facebook fa-2x"></i></a>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                    <div class="mt-4">
                        <a href="https://wa.me/<?php echo WHATSAPP_NUMBER; ?>" class="btn btn-success me-2" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i>Chat on WhatsApp
                        </a>
                        <a href="tel:<?php echo WHATSAPP_NUMBER; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-phone me-2"></i>Call Us
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                   <p class="mt-2 fw-bold">Paybill: 247247 &nbsp; | &nbsp; Acc: 743649 &nbsp; | &nbsp; Resilient Modern</p>
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
                window.location.href = 'checkout.php';
            });
        });
    </script>

    <!-- WhatsApp Chat Button -->
    <a href="https://wa.me/<?php echo WHATSAPP_NUMBER; ?>" class="whatsapp-chat" target="_blank" title="Chat with us">
        <i class="fab fa-whatsapp"></i>
    </a>
</body>
</html>