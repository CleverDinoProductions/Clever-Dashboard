<?php
$blockInfo = ['title' => 'Danger Zone', 'emoji' => '⚠️', 'color' => '#FF9800', 'positions' => '13-16', 'description' => 'Pressure Building - Action Required'];
?>
<div class="block-detail-page">
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $blockInfo['emoji']; ?> Block 4: <?php echo $blockInfo['title']; ?></h2>
            <span class="position-badge" style="background: rgba(255, 152, 0, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">Positions <?php echo $blockInfo['positions']; ?></span>
        </div>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">Teams here need consistent 1.0 PPG to avoid slipping into relegation battle. Pressure is mounting.</p>
    </div>
    <div class="panel" style="background: rgba(255, 152, 0, 0.05);">
        <h3>⚠️ Block 4 Warning Signs</h3>
        <ul style="padding-left: 20px; color: #ddd;">
            <li style="padding: 8px 0;">📉 <strong>Points dropping:</strong> Under 1.0 PPG risks relegation</li>
            <li style="padding: 8px 0;">🔄 <strong>Tactical changes:</strong> Manager under pressure</li>
            <li style="padding: 8px 0;">🛒 <strong>January window:</strong> Reinforcements critical</li>
            <li style="padding: 8px 0;">🏠 <strong>Home form vital:</strong> Must win home games</li>
        </ul>
    </div>
    <div class="panel">
        <h3>🎯 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>📊 Target</h4><ul><li><strong>PPG:</strong> 1.0-1.3</li><li><strong>Points:</strong> 38-48</li><li><strong>Survival threshold</strong></li></ul></div>
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>🧠 Mentality</h4><ul><li>Pressure building</li><li>Defensive focus</li><li>Fight for every point</li></ul></div>
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>⚽ Tactics</h4><ul><li>Pragmatic approach</li><li>Counter-attacking</li><li>Set-piece focus</li></ul></div>
        </div>
    </div>
    <div class="panel"><div class="block-navigation"><a href="?tab=premier-league&subtab=blocks-3" class="nav-btn">← Block 3</a><a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(255, 152, 0, 0.1); color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a><a href="?tab=premier-league&subtab=blocks-5" class="nav-btn">Block 5 →</a></div></div>
</div>
<style>.block-detail-page{max-width:1400px;margin:0 auto}.block-header-section{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}.block-header-section h2{margin:0;font-size:2em}.position-badge{padding:8px 15px;border-radius:6px;font-size:0.9em;font-weight:bold}.characteristics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}.char-card{background:rgba(255,255,255,0.03);padding:20px;border-radius:8px;border-left:4px solid}.char-card h4{margin:0 0 15px 0;color:#fff}.char-card ul{list-style:none;padding:0;margin:0}.char-card ul li{padding:8px 0;color:#bbb;font-size:0.95em}.block-navigation{display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}.nav-btn{flex:1;min-width:150px;padding:12px 20px;background:rgba(255,255,255,0.05);color:#fff;text-decoration:none;text-align:center;border-radius:6px;border:1px solid rgba(255,255,255,0.1);transition:all 0.2s;font-weight:500}.nav-btn:hover{background:rgba(255,255,255,0.1);transform:translateY(-2px)}</style>