<?php
// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Direct access forbidden');
}

// Reusable authentication and authorization helper
function checkAuth($required_roles = ['admin', 'operator', 'viewer']) {
    session_start();
    
    // Authentication check
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // Authorization check
    if (!in_array($_SESSION['user']['role'], $required_roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
        exit;
    }
    
    return $_SESSION['user'];
}
?>