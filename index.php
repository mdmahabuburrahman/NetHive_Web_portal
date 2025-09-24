<?php
session_start();

// Authentication check - redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Authorization - define role-based access control
$allowed_roles = [
    'dashboard' => ['admin', 'operator', 'viewer'],
    'dashboard2' => ['admin', 'operator', 'viewer'],
    'hotspot' => ['admin', 'operator'],
    'nasManagement' => ['admin'],
    'queue' => ['admin', 'operator'],
    'reports' => ['admin', 'operator', 'viewer'],
    'apiUserManagement' => ['admin'],
    'settings' => ['admin'],
    'userlogs' => ['admin', 'operator'],
    'webBlocking' => ['admin', 'operator'],
    'internetControl' => ['admin', 'operator']
];

// Default page is dashboard
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Sanitize the page parameter
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

// Define allowed pages
$allowed_pages = [
    'dashboard',
    'dashboard2',
    'hotspot',
    'nasManagement',
    'queue',
    'reports',
    'apiUserManagement',
    'settings',
    'userlogs',
    'webBlocking',
    'internetControl'
];

// Create a mapping of page names to display titles
$page_titles = [
    'dashboard' => 'Dashboard',
    'dashboard2' => 'Dashboard 2',
    'hotspot' => 'Hotspot',
    'nasManagement' => 'NAS Management',
    'queue' => 'Queue',
    'reports' => 'Reports',
    'apiUserManagement' => 'API User Management',
    'settings' => 'Settings',
    'userlogs' => 'User Logs',
    'webBlocking' => 'Web Blocking',
    'internetControl' => 'Internet Control'
];

// Validate if the requested page exists and is allowed
if (!in_array($page, $allowed_pages) || !file_exists(__DIR__ . "/views/{$page}.php")) {
    // Set 404 title
    $page_title = 'Page Not Found';

    // Include only the header (for consistent CSS/JS) but with minimal layout
    include __DIR__ . '/includes/header.php';

    // Include the 404 page directly (without nav, sidebar, footer)
    include __DIR__ . "/views/404.php";

    // Exit to prevent loading the rest of the layout
    exit;
}

// Authorization check - verify user has permission to access the requested page
if (!in_array($_SESSION['user']['role'], $allowed_roles[$page])) {
    // Set 403 title
    $page_title = 'Access Denied';

    // Include only the header
    include __DIR__ . '/includes/header.php';

    // Include the 403 page
    include __DIR__ . "/views/403.php";

    // Exit to prevent loading the rest of the layout
    exit;
}

// For valid pages, proceed as normal
$page_title = $page_titles[$page];

// Include the full layout
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . "/views/{$page}.php";
include __DIR__ . '/includes/footer.php';
