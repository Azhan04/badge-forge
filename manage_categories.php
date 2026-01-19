<?php
session_start();
include 'db.php';
requireAdmin();

$pdo = getConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $cat_name = trim($_POST['cat_name']);
            
            if (!empty($cat_name)) {
                if ($action == 'add') {
                    $stmt = $pdo->prepare("INSERT INTO categories (cat_name) VALUES (?)");
                    $stmt->execute([$cat_name]);
                    $message = 'Category added successfully!';
                } else {
                    $cat_id = (int)$_POST['cat_id'];
                    $stmt = $pdo->prepare("UPDATE categories SET cat_name = ? WHERE cat_id = ?");
                    $stmt->execute([$cat_name, $cat_id]);
                    $message = 'Category updated successfully!';
                }
            } else {
                $message = 'Category name is required.';
            }
        } elseif ($action == 'delete') {
            $cat_id = (int)$_POST['cat_id'];
            // Check if category has FAQs
            $count = $pdo->prepare("SELECT COUNT(*) FROM faqs WHERE cat_id = ?");
            $count->execute([$cat_id]);
            if ($count->fetchColumn() > 0) {
                $message = 'Cannot delete category with existing FAQs. Please reassign the FAQs first.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE cat_id = ?");
                $stmt->execute([$cat_id]);
                $message = 'Category deleted successfully!';
            }
        }
    }
}

// Get action and category ID
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category for editing
$category = null;
if ($action == 'edit' && $cat_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE cat_id = ?");
    $stmt->execute([$cat_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all categories
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM faqs f WHERE f.cat_id = c.cat_id) as faq_count FROM categories c ORDER BY c.cat_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_faqs.php">Manage FAQs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_categories.php">Manage Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Manage Users</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tags"></i> Manage Categories</h1>
            <a href="manage_categories.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Category
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $action == 'add' ? 'Add New Category' : 'Edit Category'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action == 'edit' && $category): ?>
                            <input type="hidden" name="cat_id" value="<?php echo $category['cat_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="cat_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="cat_name" name="cat_name" value="<?php echo $category ? htmlspecialchars($category['cat_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action == 'add' ? 'Add Category' : 'Update Category'; ?>
                            </button>
                            <a href="manage_categories.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Category List -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Category Name</th>
                            <th>FAQ Count</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No categories found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat['cat_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo $cat['faq_count']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                    <td>
                                        <a href="manage_categories.php?action=edit&id=<?php echo $cat['cat_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $cat['cat_id']; ?>" data-name="<?php echo htmlspecialchars($cat['cat_name']); ?>" <?php echo $cat['faq_count'] > 0 ? 'disabled title="Cannot delete category with FAQs"' : ''; ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete category "<span id="deleteCatName"></span>"? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="cat_id" id="deleteCatId" value="">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set category ID and name for delete modal
        document.querySelectorAll('[data-bs-target="#deleteModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const catId = this.getAttribute('data-id');
                const catName = this.getAttribute('data-name');
                document.getElementById('deleteCatId').value = catId;
                document.getElementById('deleteCatName').textContent = catName;
            });
        });
    </script>
</body>
</html>