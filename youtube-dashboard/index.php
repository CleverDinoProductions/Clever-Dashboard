<?php
date_default_timezone_set('UTC');

// YouTube Dashboard Router
require_once 'config.php';
require_once 'includes/db.php';  // ✅ Load DB functions FIRST

// Get current tab (default: timeline)
$tab = $_GET['tab'] ?? 'timeline';

// Validate tab
$valid_tabs = [
    'timeline',
    'channels', 
    'analytics', 
    'gaps', 
    'locations', 
    'frequency', 
    'trends', 
    'search'
];

if (!in_array($tab, $valid_tabs)) {
    $tab = 'timeline';
}

require_once 'includes/header.php';

// Load the selected tab
require_once "tabs/{$tab}.php";

require_once 'includes/footer.php';
?>
