<?php
// Include database configuration
require_once 'config.php';

// Get current tab selections
$currentMainTab = isset($_GET['tab']) ? $_GET['tab'] : 'premier-league';
$currentSubTab = isset($_GET['subtab']) ? $_GET['subtab'] : 'table';

// Include header (which now contains tab navigation)
include 'includes/header.php';
?>

<!-- Main Content Area -->
<div class="content-wrapper">
    <?php
    // Load the appropriate tab content based on URL parameters
    if ($currentMainTab == 'premier-league') {
        switch ($currentSubTab) {
            case 'table':
                include 'tabs/premier-league/table.php';
                break;
            case 'blocks-overview':
                include 'tabs/premier-league/blocks-overview.php';
                break;
            case 'blocks-1':
                include 'tabs/premier-league/blocks-1.php';
                break;
            case 'blocks-2':
                if (file_exists('tabs/premier-league/blocks-2.php')) {
                    include 'tabs/premier-league/blocks-2.php';
                } else {
                    echo '<div class="panel"><h2>🌟 Block 2: European Zone</h2><p style="color: #888;">Content coming soon. Use Block 1 as a template to create this page.</p></div>';
                }
                break;
            case 'blocks-3':
                if (file_exists('tabs/premier-league/blocks-3.php')) {
                    include 'tabs/premier-league/blocks-3.php';
                } else {
                    echo '<div class="panel"><h2>✅ Block 3: Safe Mid-Table</h2><p style="color: #888;">Content coming soon. Use Block 1 as a template to create this page.</p></div>';
                }
                break;
            case 'blocks-4':
                if (file_exists('tabs/premier-league/blocks-4.php')) {
                    include 'tabs/premier-league/blocks-4.php';
                } else {
                    echo '<div class="panel"><h2>⚠️ Block 4: Danger Zone</h2><p style="color: #888;">Content coming soon. Use Block 1 as a template to create this page.</p></div>';
                }
                break;
            case 'blocks-5':
                if (file_exists('tabs/premier-league/blocks-5.php')) {
                    include 'tabs/premier-league/blocks-5.php';
                } else {
                    echo '<div class="panel"><h2>🔻 Block 5: Relegation Battle</h2><p style="color: #888;">Content coming soon. Use Block 1 as a template to create this page.</p></div>';
                }
                break;
            case 'relegation':
                include 'tabs/premier-league/relegation.php';
                break;
            case 'leeds':
                include 'tabs/premier-league/leeds.php';
                break;
            case 'leeds-2':
                include 'tabs/premier-league/leeds2.php';
                break;
            case 'leeds-3':
                include 'tabs/premier-league/leeds3.php';
                break;
            default:
                include 'tabs/premier-league/table.php';
        }
    } elseif ($currentMainTab == 'world-cup') {
        switch ($currentSubTab) {
            case 'groups':
                include 'tabs/world-cup/groups.php';
                break;
            case 'third-place':
                if (file_exists('tabs/world-cup/third-place-teams.php')) {
                    include 'tabs/world-cup/third-place-teams.php';
                } else {
                    echo '<div class="panel"><p style="color: #888;">Content coming soon...</p></div>';
                }
                break;
            case 'knockout':
                include 'tabs/world-cup/knockout.php';
                break;
            case 'standings':
                include 'tabs/world-cup/standings.php';
                break;
            case 'predictions':
                include 'tabs/world-cup/predictions.php';
                break;
            default:
                include 'tabs/world-cup/groups.php';
        }
    }
    ?>
</div>

<?php include 'includes/footer.php'; ?>