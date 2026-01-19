<?php
session_start();
include 'db.php';
requireLogin();

$pdo = getConnection();
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (!empty($name) && !empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is already taken by another user
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_stmt->execute([$email, $user_id]);
            if ($check_stmt->fetch()) {
                $error = 'Email address is already in use.';
            } else {
                // Update user info
                $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
                if ($update_stmt->execute([$name, $email, $user_id])) {
                    $_SESSION['name'] = $name;
                    $message = 'Profile updated successfully!';
                    $user['name'] = $name;
                    $user['email'] = $email;
                } else {
                    $error = 'Error updating profile. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $verify_stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $verify_stmt->execute([$user_id]);
        $stored_password = $verify_stmt->fetchColumn();
        
        if (password_verify($current_password, $stored_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($update_pass_stmt->execute([$hashed_password, $user_id])) {
                $message = 'Password updated successfully!';
            } else {
                $error = 'Error updating password.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - College FAQ System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-question-circle text-white text-2xl mr-3"></i>
                    <a href="index.php" class="text-white text-xl font-bold">College FAQ</a>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Home</a>
                        <a href="faq_list.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">All FAQs</a>
                        <a href="contact.php" class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <div class="relative">
                            <button id="dropdownButton" class="flex items-center text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium transition-all">
                                <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            
                            <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i>My Profile
                                </a>
                                <a href="bookmarks.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-bookmark mr-2"></i>My Bookmarks
                                </a>
                                <?php if (isAdmin()): ?>
                                    <a href="admin_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cog mr-2"></i>Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">My Profile</h1>
            <p class="text-xl text-gray-600">Manage your account settings</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user-edit mr-3 text-blue-600"></i>
                    Profile Information
                </h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="name">
                            Full Name
                        </label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                            Email Address
                        </label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition-all">
                            <i class="fas fa-save mr-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-lock mr-3 text-green-600"></i>
                    Change Password
                </h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="current_password">
                            Current Password
                        </label>
                        <input type="password" name="current_password" id="current_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="new_password">
                            New Password
                        </label>
                        <input type="password" name="new_password" id="new_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                            Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               required>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" name="change_password" class="bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 px-6 rounded-lg font-medium hover:from-green-700 hover:to-emerald-700 transition-all">
                            <i class="fas fa-key mr-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Dropdown functionality
        const dropdownButton = document.getElementById('dropdownButton');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        dropdownButton.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>