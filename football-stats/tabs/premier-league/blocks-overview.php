<?php
// Blocks of 4 Overview - General Information
?>
<div class="blocks-wrapper">
    <div class="panel">
        <h2>📊 Blocks of 4 Framework</h2>
        <p class="intro-text">
            The Premier League table divided into 5 equal blocks of 4 teams, providing clear insights into 
            competitive zones, psychological boundaries, and tactical positioning throughout the season.
        </p>
    </div>

    <!-- Framework Explanation -->
    <div class="panel">
        <h3>🎯 How the Framework Works</h3>
        <div class="blocks-grid">
            <div class="block-card block-1">
                <div class="block-header">
                    <span class="block-number">Block 1</span>
                    <span class="block-positions">Positions 1-4</span>
                </div>
                <h4>🏆 Title Contenders</h4>
                <ul>
                    <li>Champions League qualification</li>
                    <li>Title race participants</li>
                    <li>Attacking mentality</li>
                    <li>High confidence levels</li>
                </ul>
            </div>

            <div class="block-card block-2">
                <div class="block-header">
                    <span class="block-number">Block 2</span>
                    <span class="block-positions">Positions 5-8</span>
                </div>
                <h4>🌟 European Zone</h4>
                <ul>
                    <li>Europa League / Conference League</li>
                    <li>Tactical flexibility required</li>
                    <li>Competitive mid-table</li>
                    <li>Push for European spots</li>
                </ul>
            </div>

            <div class="block-card block-3">
                <div class="block-header">
                    <span class="block-number">Block 3</span>
                    <span class="block-positions">Positions 9-12</span>
                </div>
                <h4>✅ Safe Mid-Table</h4>
                <ul>
                    <li>Premier League security</li>
                    <li>No relegation concerns</li>
                    <li>Tactical stability zone</li>
                    <li>Build for future success</li>
                </ul>
            </div>

            <div class="block-card block-4">
                <div class="block-header">
                    <span class="block-number">Block 4</span>
                    <span class="block-positions">Positions 13-16</span>
                </div>
                <h4>⚠️ Danger Zone</h4>
                <ul>
                    <li>Pressure building</li>
                    <li>Tactical changes needed</li>
                    <li>Risk of dropping to Block 5</li>
                    <li>Managerial scrutiny</li>
                </ul>
            </div>

            <div class="block-card block-5">
                <div class="block-header">
                    <span class="block-number">Block 5</span>
                    <span class="block-positions">Positions 17-20</span>
                </div>
                <h4>🔻 Relegation Battle</h4>
                <ul>
                    <li>Immediate danger</li>
                    <li>Crisis mode mentality</li>
                    <li>Desperate measures required</li>
                    <li>Mathematical escape needed</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Why It Works -->
    <div class="panel">
        <h3>💡 Why Blocks of 4 Works Perfectly</h3>
        <div class="info-grid">
            <div class="info-card">
                <h4>🔢 Universal Application</h4>
                <p>Works across all English leagues:</p>
                <ul>
                    <li><strong>Premier League:</strong> 20 teams = 5 blocks of 4</li>
                    <li><strong>Championship:</strong> 24 teams = 6 blocks of 4</li>
                    <li><strong>League One:</strong> 24 teams = 6 blocks of 4</li>
                    <li><strong>League Two:</strong> 24 teams = 6 blocks of 4</li>
                </ul>
            </div>

            <div class="info-card">
                <h4>🧠 Psychological Boundaries</h4>
                <p>Each block represents distinct team mentalities and tactical approaches throughout the season.</p>
            </div>

            <div class="info-card">
                <h4>📈 Predictive Power</h4>
                <p>Track movement between blocks to identify:</p>
                <ul>
                    <li>Teams under pressure (dropping blocks)</li>
                    <li>Form improvements (climbing blocks)</li>
                    <li>Stabilization points in the season</li>
                </ul>
            </div>

            <div class="info-card">
                <h4>⏱️ Stabilization Timeline</h4>
                <p>By October-November (Matchday 10-12), block positions typically stabilize as mathematical realities become clear.</p>
            </div>
        </div>
    </div>

    <!-- Current Season Quick Stats -->
    <div class="panel">
        <h3>📊 Current Season Block Distribution</h3>
        <div class="stats-overview">
            <p class="note">View individual block tabs to see detailed analysis of teams in each position range.</p>
            <div class="quick-navigation">
                <a href="?tab=premier-league&subtab=blocks-1" class="nav-button block-1-btn">Block 1 →</a>
                <a href="?tab=premier-league&subtab=blocks-2" class="nav-button block-2-btn">Block 2 →</a>
                <a href="?tab=premier-league&subtab=blocks-3" class="nav-button block-3-btn">Block 3 →</a>
                <a href="?tab=premier-league&subtab=blocks-4" class="nav-button block-4-btn">Block 4 →</a>
                <a href="?tab=premier-league&subtab=blocks-5" class="nav-button block-5-btn">Block 5 →</a>
            </div>
        </div>
    </div>

    <!-- Mathematical Reality -->
    <div class="panel">
        <h3>🔢 The Mathematical Reality</h3>
        <div class="math-explanation">
            <p><strong>Why bottom blocks struggle to catch up:</strong></p>
            <ul>
                <li>Top teams accumulate 2-3 points per week consistently</li>
                <li>Bottom teams average 0-1 points per week</li>
                <li>The gap widens exponentially as the season progresses</li>
                <li>After Matchday 10, teams need 1.0-1.2 PPG to survive - historically difficult for struggling sides</li>
            </ul>
            
            <div class="survival-stats">
                <h4>Historical Survival Rates (After 10 Games):</h4>
                <ul>
                    <li>✅ <strong>11+ points:</strong> 89% survival rate</li>
                    <li>⚠️ <strong>8-10 points:</strong> 53% survival rate</li>
                    <li>🔻 <strong>0-7 points:</strong> Only 12% survival rate</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.blocks-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.intro-text {
    font-size: 1.1em;
    line-height: 1.6;
    color: #ddd;
}

.blocks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.block-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid;
}

.block-card.block-1 { border-left-color: #FFD700; }
.block-card.block-2 { border-left-color: #4CAF50; }
.block-card.block-3 { border-left-color: #2196F3; }
.block-card.block-4 { border-left-color: #FF9800; }
.block-card.block-5 { border-left-color: #F44336; }

.block-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.block-number {
    font-weight: bold;
    font-size: 0.9em;
}

.block-positions {
    font-size: 0.85em;
    color: #888;
}

.block-card h4 {
    margin: 10px 0;
    font-size: 1.2em;
}

.block-card ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.block-card ul li {
    padding: 5px 0;
    font-size: 0.95em;
    color: #bbb;
}

.block-card ul li:before {
    content: "→ ";
    color: #666;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.info-card {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.info-card h4 {
    margin-top: 0;
    color: #4CAF50;
}

.info-card ul {
    margin: 10px 0;
    padding-left: 20px;
}

.info-card ul li {
    margin: 5px 0;
    color: #bbb;
}

.quick-navigation {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.nav-button {
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    border: 2px solid;
}

.block-1-btn {
    background: rgba(255, 215, 0, 0.1);
    border-color: #FFD700;
    color: #FFD700;
}

.block-2-btn {
    background: rgba(76, 175, 80, 0.1);
    border-color: #4CAF50;
    color: #4CAF50;
}

.block-3-btn {
    background: rgba(33, 150, 243, 0.1);
    border-color: #2196F3;
    color: #2196F3;
}

.block-4-btn {
    background: rgba(255, 152, 0, 0.1);
    border-color: #FF9800;
    color: #FF9800;
}

.block-5-btn {
    background: rgba(244, 67, 54, 0.1);
    border-color: #F44336;
    color: #F44336;
}

.nav-button:hover {
    transform: translateX(5px);
    opacity: 0.8;
}

.math-explanation {
    background: rgba(255, 255, 255, 0.03);
    padding: 20px;
    border-radius: 8px;
    margin-top: 15px;
}

.math-explanation ul {
    margin: 10px 0;
    padding-left: 20px;
}

.survival-stats {
    background: rgba(255, 152, 0, 0.1);
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
    border-left: 3px solid #FF9800;
}

.survival-stats h4 {
    margin-top: 0;
}

.survival-stats ul {
    list-style: none;
    padding: 0;
}

.survival-stats ul li {
    padding: 8px 0;
    font-size: 1.05em;
}

.note {
    color: #888;
    font-style: italic;
    margin-bottom: 15px;
}
</style>