<?php
/**
 * Sample Data Population Script
 * Use this to populate the database with sample Premier League data for testing
 * Run: php populate-sample-data.php
 */

try {
    $pdo = new PDO('sqlite:football-stats.sqlite3');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear existing data
    $pdo->exec("DELETE FROM league_table");
    
    // Sample Premier League data (Matchday 18 - December 2025)
    $sampleTeams = [
        // Block 1: Title Contenders (1-4)
        ['Liverpool', 1, 18, 13, 4, 1, 39, 16, 23, 43],
        ['Arsenal', 2, 18, 11, 5, 2, 35, 16, 19, 38],
        ['Chelsea', 3, 18, 10, 5, 3, 35, 20, 15, 35],
        ['Manchester City', 4, 18, 10, 4, 4, 32, 21, 11, 34],
        
        // Block 2: European Zone (5-8)
        ['Newcastle United', 5, 18, 10, 3, 5, 31, 21, 10, 33],
        ['Tottenham Hotspur', 6, 18, 9, 4, 5, 33, 23, 10, 31],
        ['Nottingham Forest', 7, 18, 9, 4, 5, 25, 21, 4, 31],
        ['Brighton & Hove Albion', 8, 18, 8, 6, 4, 29, 24, 5, 30],
        
        // Block 3: Safe Mid-Table (9-12)
        ['Aston Villa', 9, 18, 8, 4, 6, 26, 26, 0, 28],
        ['Fulham', 10, 18, 7, 6, 5, 26, 24, 2, 27],
        ['Manchester United', 11, 18, 7, 4, 7, 23, 22, 1, 25],
        ['Leeds United', 12, 18, 6, 7, 5, 24, 25, -1, 25],
        
        // Block 4: Danger Zone (13-16)
        ['Brentford', 13, 18, 7, 3, 8, 28, 30, -2, 24],
        ['Bournemouth', 14, 18, 6, 5, 7, 24, 26, -2, 23],
        ['West Ham United', 15, 18, 6, 4, 8, 22, 30, -8, 22],
        ['Crystal Palace', 16, 18, 5, 6, 7, 20, 25, -5, 21],
        
        // Block 5: Relegation Battle (17-20)
        ['Everton', 17, 18, 4, 7, 7, 18, 25, -7, 19],
        ['Ipswich Town', 18, 18, 4, 6, 8, 19, 30, -11, 18],
        ['Leicester City', 19, 18, 3, 5, 10, 21, 35, -14, 14],
        ['Southampton', 20, 18, 1, 3, 14, 12, 38, -26, 6]
    ];
    
    // Insert sample data
    $stmt = $pdo->prepare("
        INSERT INTO league_table 
        (team_name, position, played, won, drawn, lost, gf, ga, gd, points, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $timestamp = time();
    foreach ($sampleTeams as $team) {
        $stmt->execute(array_merge($team, [$timestamp]));
    }
    
    echo "\n✅ SUCCESS: Sample data populated!\n";
    echo "-----------------------------------\n";
    echo "📊 20 Premier League teams inserted\n";
    echo "🏆 Block 1 (1-4): Title Contenders\n";
    echo "🌟 Block 2 (5-8): European Zone\n";
    echo "✅ Block 3 (9-12): Safe Mid-Table (Leeds in position 12!)\n";
    echo "⚠️  Block 4 (13-16): Danger Zone\n";
    echo "🔻 Block 5 (17-20): Relegation Battle\n";
    echo "-----------------------------------\n";
    echo "🎯 Matchday: 18 of 38\n";
    echo "📅 Updated: " . date('Y-m-d H:i:s', $timestamp) . "\n\n";
    echo "You can now view your blocks analysis at:\n";
    echo "→ http://your-dashboard/football-stats/?tab=premier-league&subtab=blocks-overview\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Make sure football-stats.sqlite3 exists and is writable.\n\n";
}
?>