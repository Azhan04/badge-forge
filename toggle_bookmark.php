<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faq_id = (int)$_POST['faq_id'];
    
    $result = toggleBookmark($faq_id, $_SESSION['user_id']);
    
    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => 'Bookmark updated', 'isBookmarked' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating bookmark']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>