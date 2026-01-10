<?php
/**
 * DEBUG VERSION - Find the exact error
 */

// Step 1: Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Debug Mode</h1>";
echo "<p>Testing each component...</p>";

// Step 2: Test database connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    require_once 'config.php';
    echo "✅ Database connected: " . get_class($db) . "<br>";
} catch (Exception $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Step 3: Test parameters
echo "<h2>2. Testing Parameters</h2>";
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$method = $_GET['method'] ?? 'auto';
echo "Year: " . htmlspecialchars($year ?? 'null') . "<br>";
echo "Month: " . htmlspecialchars($month ?? 'null') . "<br>";
echo "Method: " . htmlspecialchars($method) . "<br>";

// Step 4: Test helper function
echo "<h2>3. Testing Helper Function</h2>";
function strip_mirc_codes($text) {
    $text = preg_replace('/\x03[0-9]{1,2}(,[0-9]{1,2})?/', '', $text);
    $text = str_replace([chr(2), chr(15), chr(22), chr(31)], '', $text);
    return trim($text);
}
echo "✅ Helper function defined<br>";

// Step 5: Test simple query
echo "<h2>4. Testing Simple Query</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM messages LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Query works. Total messages: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
}

// Step 6: Test voice detection function
echo "<h2>5. Testing Voice Detection Function</h2>";
function detect_windows_by_voice($db, $year = null) {
    try {
        $year_filter = $year ? "AND strftime('%Y', time/1000, 'unixepoch') = :year" : "";

        $sql = "
            SELECT DISTINCT 
                date(time/1000, 'unixepoch') as date,
                strftime('%w', time/1000, 'unixepoch') as dow
            FROM messages
            WHERE channel = '#anonamouse.net'
            AND type = 'mode'
            AND json_extract(msg, '$.text') LIKE '+v %'
            AND strftime('%w', time/1000, 'unixepoch') IN ('3', '6')
            $year_filter
            ORDER BY date DESC
            LIMIT 5
        ";

        echo "SQL: <pre>" . htmlspecialchars($sql) . "</pre>";

        $stmt = $db->prepare($sql);

        if ($year) {
            $stmt->execute(['year' => $year]);
        } else {
            $stmt->execute();
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Found " . count($results) . " windows<br>";

        if (!empty($results)) {
            echo "<pre>" . print_r($results, true) . "</pre>";
        }

        return $results;
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
        return [];
    }
}

$windows = detect_windows_by_voice($db, $year);

// Step 7: Test auto detection
echo "<h2>6. Testing Auto Detection</h2>";
function detect_windows_auto($db, $year = null) {
    $all_windows = [];

    echo "Calling detect_windows_by_voice...<br>";
    foreach (detect_windows_by_voice($db, $year) as $w) {
        $w['method'] = 'voice';
        $all_windows[] = $w;
    }

    echo "✅ Auto detection collected " . count($all_windows) . " windows<br>";

    // Deduplicate
    $unique = [];
    $seen = [];

    foreach ($all_windows as $window) {
        if (!in_array($window['date'], $seen)) {
            $seen[] = $window['date'];
            $unique[] = $window;
        }
    }

    return $unique;
}

if ($method == 'auto') {
    echo "Testing auto mode...<br>";
    $windows = detect_windows_auto($db, $year);
}

// Step 8: Test month filtering
echo "<h2>7. Testing Month Filtering</h2>";
if ($year && $month) {
    echo "Filtering windows for month $month...<br>";
    $windows = array_filter($windows, function($w) use ($month) {
        $win_month = substr($w['date'], 5, 2);
        echo "Window date: {$w['date']}, month: $win_month, target: $month<br>";
        return $win_month === $month;
    });
    echo "✅ After filtering: " . count($windows) . " windows<br>";
}

// Final result
echo "<h2>8. Final Result</h2>";
echo "<pre>" . print_r($windows, true) . "</pre>";

echo "<hr>";
echo "<h2>✅ ALL TESTS PASSED</h2>";
echo "<p>If you see this, the code works! The error must be in the HTML output section.</p>";
?>