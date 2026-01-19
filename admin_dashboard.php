<?php
session_start();
include 'db.php';
requireAdmin();

$pdo = getConnection();

// Get statistics
$faqs_count = $pdo->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
$categories_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College FAQ System</title>
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
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_faqs.php">Manage FAQs</a>
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
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $faqs_count; ?></h4>
                                <p class="mb-0">Total FAQs</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-question-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $categories_count; ?></h4>
                                <p class="mb-0">Categories</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-tags fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $users_count; ?></h4>
                                <p class="mb-0">Total Users</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $admin_count; ?></h4>
                                <p class="mb-0">Admin Users</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-cog fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="manage_faqs.php" class="btn btn-primary w-100">
                                    <i class="fas fa-question-circle"></i><br>
                                    Manage FAQs
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="manage_categories.php" class="btn btn-success w-100">
                                    <i class="fas fa-tags"></i><br>
                                    Manage Categories
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="manage_users.php" class="btn btn-info w-100">
                                    <i class="fas fa-users"></i><br>
                                    Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="manage_faqs.php?action=add" class="btn btn-warning w-100">
                                    <i class="fas fa-plus"></i><br>
                                    Add New FAQ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Recent FAQs</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_faqs = $pdo->query("SELECT f.*, c.cat_name FROM faqs f LEFT JOIN categories c ON f.cat_id = c.cat_id ORDER BY f.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if (empty($recent_faqs)): ?>
                            <p class="text-muted">No recent FAQs found.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_faqs as $faq): ?>
                                    <a href="manage_faqs.php?action=edit&id=<?php echo $faq['faq_id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars(substr($faq['question'], 0, 50)) . '...'; ?></h6>
                                            <small><?php echo date('M d', strtotime($faq['created_at'])); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($faq['cat_name']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-clock"></i> Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if (empty($recent_users)): ?>
                            <p class="text-muted">No recent users found.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                            <small><?php echo date('M d', strtotime($user['created_at'])); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>