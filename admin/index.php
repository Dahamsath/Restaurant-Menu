<?php
session_start();
require '../config/db.php';

$message = "";
$error = "";
$edit_item = null;

$admin_password = "Dahamsath";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Incorrect password!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$is_logged_in) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-card {
                max-width: 400px;
                width: 100%;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                background: white;
            }
        </style>
    </head>

    <body>
        <div class="login-card">
            <div class="text-center mb-4">
                <h2 class="fw-bold">🔐 Admin Access</h2>
                <p class="text-muted">Please enter your password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control form-control-lg" id="password" name="login_password"
                        placeholder="Enter password" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
            </form>

            <div class="mt-3 text-center">
                <a href="../index.php" class="text-decoration-none small text-muted">← Back to Restaurant Menu</a>
            </div>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $cat = $_POST['category'];

    if (!empty($name) && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $desc, $price, $cat])) {
            $message = "✅ Item added successfully!";
        } else {
            $error = "❌ Error adding item.";
        }
    } else {
        $error = "❌ Please fill all required fields.";
    }
}

// Handle Update Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_item'])) {
    $id = intval($_POST['item_id']);
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $cat = $_POST['category'];

    if (!empty($name) && $price > 0 && $id > 0) {
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category = ? WHERE id = ?");
        if ($stmt->execute([$name, $desc, $price, $cat, $id])) {
            $message = "✅ Item updated successfully!";
        } else {
            $error = "❌ Error updating item.";
        }
    } else {
        $error = "❌ Please fill all required fields.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
        $message = "✅ Item deleted successfully!";
    }
}

// Handle Edit (Load item data)
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_item) {
            $error = "❌ Item not found.";
        }
    }
}

// Cancel Edit
if (isset($_GET['cancel_edit'])) {
    $edit_item = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Gourmet Bistro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <span class="navbar-brand mb-0 h1">🛠️ Admin Dashboard</span>
            <div>
                <a href="orders.php" class="btn btn-warning btn-sm me-2">View Orders</a>
                <a href="../index.php" class="btn btn-outline-light btn-sm me-2">View Site</a>
                <a href="?logout=1" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">

            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header <?= $edit_item ? 'bg-warning text-dark' : 'bg-primary text-white' ?>">
                        <h5 class="mb-0">
                            <?= $edit_item ? '✏️ Edit Item' : '➕ Add New Item' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($edit_item): ?>
                                <input type="hidden" name="item_id" value="<?= $edit_item['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Food Name *</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= $edit_item ? htmlspecialchars($edit_item['name']) : '' ?>"
                                    placeholder="e.g., Margherita Pizza" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Ingredients, taste details..."><?= $edit_item ? htmlspecialchars($edit_item['description']) : '' ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Price ($) *</label>
                                <input type="number" step="0.01" name="price" class="form-control"
                                    value="<?= $edit_item ? $edit_item['price'] : '' ?>" placeholder="0.00" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select">
                                    <option value="Pizza" <?= ($edit_item && $edit_item['category'] == 'Pizza') ? 'selected' : '' ?>>Pizza</option>
                                    <option value="Burger" <?= ($edit_item && $edit_item['category'] == 'Burger') ? 'selected' : '' ?>>Burger</option>
                                    <option value="Salad" <?= ($edit_item && $edit_item['category'] == 'Salad') ? 'selected' : '' ?>>Salad</option>
                                    <option value="Drinks" <?= ($edit_item && $edit_item['category'] == 'Drinks') ? 'selected' : '' ?>>Drinks</option>
                                    <option value="Dessert" <?= ($edit_item && $edit_item['category'] == 'Dessert') ? 'selected' : '' ?>>Dessert</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($edit_item): ?>
                                    <button type="submit" name="update_item" class="btn btn-warning">💾 Update Item</button>
                                    <a href="?cancel_edit=1" class="btn btn-secondary">❌ Cancel Edit</a>
                                <?php else: ?>
                                    <button type="submit" name="add_item" class="btn btn-primary">➕ Add to Menu</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Items -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">📋 Current Menu Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items = $pdo->query("SELECT * FROM menu_items ORDER BY id DESC")->fetchAll();
                                    if (count($items) > 0):
                                        foreach ($items as $item): ?>
                                            <tr
                                                class="<?= ($edit_item && $edit_item['id'] == $item['id']) ? 'table-warning' : '' ?>">
                                                <td class="text-muted">#<?= $item['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                                    <small
                                                        class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 40)) ?><?= strlen($item['description']) > 40 ? '...' : '' ?></small>
                                                </td>
                                                <td><span class="badge bg-secondary"><?= $item['category'] ?></span></td>
                                                <td class="text-success fw-bold">$<?= number_format($item['price'], 2) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="?edit=<?= $item['id'] ?>" class="btn btn-outline-primary"
                                                            title="Edit">
                                                            ✏️ Edit
                                                        </a>
                                                        <a href="?delete=<?= $item['id'] ?>" class="btn btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete \'<?= htmlspecialchars($item['name']) ?>\'? This cannot be undone.')"
                                                            title="Delete">
                                                            🗑️ Delete
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <p class="mb-0">No items found.</p>
                                                <small>Add your first menu item using the form on the left!</small>
                                            </td>
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