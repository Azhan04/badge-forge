<?php
session_start();
include 'db.php';

$token = $_GET['token'] ?? '';
$message = '';
$error = '';

if (empty($token)) {
    $error = 'Invalid reset token.';
} else {
    $pdo = getConnection();
    
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reset) {
        $error = 'Invalid or expired reset token.';
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Update user password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($update_stmt->execute([$hashed_password, $reset['email']])) {
                // Delete the reset token
                $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $delete_stmt->execute([$token]);
                
                $message = 'Password has been reset successfully! You can now login.';
            } else {
                $error = 'Error resetting password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - College FAQ System</title>
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
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-8 text-center">
                <div class="flex justify-center mb-4">
                    <i class="fas fa-lock text-white text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Set New Password</h2>
                <p class="text-green-100">Create a new password for your account</p>
            </div>
            
            <div class="p-8">
                <?php if ($message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($error) && !empty($token)): ?>
                    <form method="POST">
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="password">
                                New Password
                            </label>
                            <input type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                                Confirm New Password
                            </label>
                            <input type="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 px-4 rounded-lg font-medium hover:from-green-700 hover:to-emerald-700 transition-all transform hover:scale-105">
                            <i class="fas fa-key mr-2"></i>Reset Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        <a href="login.php" class="text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>