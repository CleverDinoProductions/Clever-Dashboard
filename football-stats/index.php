<?php
require_once 'config.php';

$tab = $_GET['tab'] ?? 'table';
$valid_tabs = ['table', 'leeds','leeds2', 'relegation']; 
if (!in_array($tab, $valid_tabs)) $tab = 'table';

require_once 'includes/header.php';
require_once "tabs/$tab.php";
require_once 'includes/footer.php';
?>