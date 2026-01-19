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
    $rating = $_POST['rating'];
    
    if ($rating !== 'like' && $rating !== 'dislike') {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit();
    }
    
    if (rateFAQ($faq_id, $_SESSION['user_id'], $rating)) {
        echo json_encode(['success' => true, 'message' => 'Rating updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating rating']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>