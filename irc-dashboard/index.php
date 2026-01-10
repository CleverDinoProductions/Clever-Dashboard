<?php
date_default_timezone_set('UTC');
// Main Dashboard Router
require_once 'config.php';

$tab = $_GET['tab'] ?? 'general';

// Validate tab
$valid_tabs = ['general', 'channels', 'invitations', 'interviews',  'interview-history', 'invitation-history', 'windows', 'timezones'];
if (!in_array($tab, $valid_tabs)) {
    $tab = 'general';
}

require_once 'includes/header.php';

// Load the selected tab
require_once "tabs/{$tab}.php";

require_once 'includes/footer.php';
?>
