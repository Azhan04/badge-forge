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
            $question = trim($_POST['question']);
            $answer = trim($_POST['answer']);
            $cat_id = (int)$_POST['cat_id'];
            
            if (!empty($question) && !empty($answer)) {
                if ($action == 'add') {
                    $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, cat_id) VALUES (?, ?, ?)");
                    $stmt->execute([$question, $answer, $cat_id]);
                    $message = 'FAQ added successfully!';
                } else {
                    $faq_id = (int)$_POST['faq_id'];
                    $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, cat_id = ? WHERE faq_id = ?");
                    $stmt->execute([$question, $answer, $cat_id, $faq_id]);
                    $message = 'FAQ updated successfully!';
                }
            } else {
                $message = 'Please fill in all required fields.';
            }
        } elseif ($action == 'delete') {
            $faq_id = (int)$_POST['faq_id'];
            $stmt = $pdo->prepare("DELETE FROM faqs WHERE faq_id = ?");
            $stmt->execute([$faq_id]);
            $message = 'FAQ deleted successfully!';
        }
    }
}

// Get action and FAQ ID
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$faq_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY cat_name")->fetchAll(PDO::FETCH_ASSOC);

// Get FAQ for editing
$faq = null;
if ($action == 'edit' && $faq_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM faqs WHERE faq_id = ?");
    $stmt->execute([$faq_id]);
    $faq = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination for list view
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$total_faqs = $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
$total_pages = ceil($total_faqs / $limit);

// Get FAQs with pagination
$stmt = $pdo->prepare("SELECT f.*, c.cat_name FROM faqs f LEFT JOIN categories c ON f.cat_id = c.cat_id ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute();
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage FAQs - Admin Panel</title>
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
                        <a class="nav-link active" href="manage_faqs.php">Manage FAQs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_categories.php">Manage Categories</a>
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
            <h1><i class="fas fa-question-circle"></i> Manage FAQs</h1>
            <a href="manage_faqs.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New FAQ
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $action == 'add' ? 'Add New FAQ' : 'Edit FAQ'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action == 'edit' && $faq): ?>
                            <input type="hidden" name="faq_id" value="<?php echo $faq['faq_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="question" class="form-label">Question</label>
                            <textarea class="form-control" id="question" name="question" rows="3" required><?php echo $faq ? htmlspecialchars($faq['question']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer</label>
                            <textarea class="form-control" id="answer" name="answer" rows="5" required><?php echo $faq ? htmlspecialchars($faq['answer']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cat_id" class="form-label">Category</label>
                            <select class="form-select" id="cat_id" name="cat_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($faq && $faq['cat_id'] == $cat['cat_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['cat_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $action == 'add' ? 'Add FAQ' : 'Update FAQ'; ?>
                            </button>
                            <a href="manage_faqs.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- FAQ List -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faqs)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No FAQs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($faq['question'], 0, 100)) . (strlen($faq['question']) > 100 ? '...' : ''); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($faq['cat_name']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($faq['created_at'])); ?></td>
                                    <td>
                                        <a href="manage_faqs.php?action=edit&id=<?php echo $faq['faq_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $faq['faq_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
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
                    <p>Are you sure you want to delete this FAQ? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="faq_id" id="deleteFaqId" value="">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set FAQ ID for delete modal
        document.querySelectorAll('[data-bs-target="#deleteModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const faqId = this.getAttribute('data-id');
                document.getElementById('deleteFaqId').value = faqId;
            });
        });
    </script>
</body>
</html>