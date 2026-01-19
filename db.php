<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_faq_system');

// Create connection
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Get user's rating for a FAQ
function getUserRating($faq_id, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT rating FROM faq_ratings WHERE faq_id = ? AND user_id = ?");
    $stmt->execute([$faq_id, $user_id]);
    return $stmt->fetchColumn();
}

// Rate a FAQ
function rateFAQ($faq_id, $user_id, $rating) {
    $pdo = getConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Check if user has already rated this FAQ
        $existing_rating = getUserRating($faq_id, $user_id);
        
        if ($existing_rating) {
            // Update existing rating
            $stmt = $pdo->prepare("UPDATE faq_ratings SET rating = ? WHERE faq_id = ? AND user_id = ?");
            $stmt->execute([$rating, $faq_id, $user_id]);
        } else {
            // Insert new rating
            $stmt = $pdo->prepare("INSERT INTO faq_ratings (faq_id, user_id, rating) VALUES (?, ?, ?)");
            $stmt->execute([$faq_id, $user_id, $rating]);
        }
        
        // Update FAQ stats
        $stmt = $pdo->prepare("UPDATE faqs SET likes = (SELECT COUNT(*) FROM faq_ratings WHERE faq_id = ? AND rating = 'like'), 
                              dislikes = (SELECT COUNT(*) FROM faq_ratings WHERE faq_id = ? AND rating = 'dislike') 
                              WHERE faq_id = ?");
        $stmt->execute([$faq_id, $faq_id, $faq_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// Log search
function logSearch($search_term, $results_count, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO search_logs (search_term, results_count, user_id, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$search_term, $results_count, $user_id, $_SERVER['REMOTE_ADDR']]);
}

// Check if FAQ is bookmarked by user
function isBookmarked($faq_id, $user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_bookmarks WHERE faq_id = ? AND user_id = ?");
    $stmt->execute([$faq_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

// Toggle bookmark
function toggleBookmark($faq_id, $user_id) {
    $pdo = getConnection();
    
    if (isBookmarked($faq_id, $user_id)) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM user_bookmarks WHERE faq_id = ? AND user_id = ?");
        $stmt->execute([$faq_id, $user_id]);
        return false;
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO user_bookmarks (faq_id, user_id) VALUES (?, ?)");
        $stmt->execute([$faq_id, $user_id]);
        return true;
    }
}

// Log audit action
function logAudit($user_id, $action, $details) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

// Get user bookmarks
function getUserBookmarks($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT f.*, c.cat_name 
        FROM user_bookmarks ub 
        LEFT JOIN faqs f ON ub.faq_id = f.faq_id 
        LEFT JOIN categories c ON f.cat_id = c.cat_id 
        WHERE ub.user_id = ? 
        ORDER BY ub.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get popular FAQs
function getPopularFAQs($limit = 5) {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT f.*, c.cat_name, (f.likes - f.dislikes) as score 
        FROM faqs f 
        LEFT JOIN categories c ON f.cat_id = c.cat_id 
        ORDER BY score DESC, f.views DESC 
        LIMIT $limit
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add these functions to your db.php file

// Get user profile
function getUserProfile($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update user preferences
function updateUserPreferences($user_id, $preferences) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE user_id = ?");
    return $stmt->execute([json_encode($preferences), $user_id]);
}

// Get password reset token
function getPasswordResetToken($token) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>