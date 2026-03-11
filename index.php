<?php require 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gourmet Bistro | Online Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">🍽️ Gourmet Bistro</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold">Our Menu</h1>
                <p class="text-muted">Fresh ingredients, delivered to your table.</p>
            </div>
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search foods...">
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4" id="filters">
            <button class="btn btn-outline-dark active filter-btn" data-category="all">All</button>
            <button class="btn btn-outline-dark filter-btn" data-category="Pizza">Pizza</button>
            <button class="btn btn-outline-dark filter-btn" data-category="Burger">Burgers</button>
            <button class="btn btn-outline-dark filter-btn" data-category="Salad">Salads</button>
            <button class="btn btn-outline-dark filter-btn" data-category="Drinks">Drinks</button>
        </div>

        <!-- Menu Grid -->
        <div class="row g-4" id="menuContainer">
            <?php
            $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id DESC");
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $imagePath = !empty($item['image_url'])
                    ? htmlspecialchars($item['image_url'])
                    : 'https://via.placeholder.com/400x300?text=' . urlencode($item['name']);
                ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 menu-item"
                    data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="<?= $imagePath ?>" class="card-img-top" alt="<?= htmlspecialchars($item['name']) ?>"
                            onerror="this.src='https://via.placeholder.com/400x300?text=<?= urlencode($item['name']) ?>'">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($item['description']) ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success">$<?= number_format($item['price'], 2) ?></span>
                                <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $item['id'] ?>"
                                    data-name="<?= htmlspecialchars($item['name']) ?>" data-price="<?= $item['price'] ?>">
                                    Add to Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Cart Modal -->
        <div class="modal fade" id="cartModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Your Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul id="cartItems" class="list-group mb-3"></ul>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span id="cartTotal">$0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="checkoutBtn" class="btn btn-success">Proceed to Pay</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mock Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">🔒 Secure Checkout</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">This is a demo environment. No real charges will be made.</p>

                        <form id="paymentForm">
                            <div class="mb-3">
                                <label class="form-label">Name on Card</label>
                                <input type="text" class="form-control" id="cardName" placeholder="John Doe" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="cardNumber"
                                    placeholder="0000 0000 0000 0000" maxlength="19" required>
                                <div class="form-text">Enter any 16 digits for demo.</div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <select class="form-select" id="cardMonth" required>
                                                <option value="">MM</option>
                                                <option value="01">01</option>
                                                <option value="02">02</option>
                                                <option value="03">03</option>
                                                <option value="04">04</option>
                                                <option value="05">05</option>
                                                <option value="06">06</option>
                                                <option value="07">07</option>
                                                <option value="08">08</option>
                                                <option value="09">09</option>
                                                <option value="10">10</option>
                                                <option value="11">11</option>
                                                <option value="12">12</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select" id="cardYear" required>
                                                <option value="">YY</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="password" class="form-control" id="cardCvv" placeholder="123"
                                        maxlength="3" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="payNowBtn">Pay Now</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/script.js"></script>

        <div id="receipt-template"
            style="display:none; width: 400px; padding: 20px; background: #fff; font-family: 'Courier New', Courier, monospace; border: 2px solid #333; color: #333;">
            <div style="text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px;">
                <h2 style="margin: 0; font-size: 24px;">🍽️ GOURMET BISTRO</h2>
                <p style="margin: 5px 0 0; font-size: 12px;">123 Food Street, Flavor Town</p>
                <p style="margin: 0; font-size: 12px;">Tel: (555) 123-4567</p>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px;">
                <span><strong>Date:</strong> <span id="r-date"></span></span>
                <span><strong>Order #:</strong> <span id="r-order"></span></span>
            </div>

            <div style="border-bottom: 2px dashed #333; margin-bottom: 10px;"></div>

            <div id="r-items" style="font-size: 14px; margin-bottom: 10px;">
            </div>

            <div
                style="border-top: 2px dashed #333; margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                <span>TOTAL PAID:</span>
                <span id="r-total"></span>
            </div>

            <div style="text-align: center; margin-top: 20px; font-size: 12px;">
                <p>Thank you for your order!</p>
                <p>Please keep this receipt for your records.</p>
            </div>
        </div>

</body>

</html>