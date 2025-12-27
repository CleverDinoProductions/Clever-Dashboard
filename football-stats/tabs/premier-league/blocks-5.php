<?php
$blockInfo = ['title' => 'Relegation Battle', 'emoji' => '🔻', 'color' => '#F44336', 'positions' => '17-20', 'description' => 'Immediate Danger - Crisis Mode'];
?>
<div class="block-detail-page">
    <div class="panel" style="border-left: 4px solid <?php echo $blockInfo['color']; ?>;">
        <div class="block-header-section">
            <h2 style="color: <?php echo $blockInfo['color']; ?>;"><?php echo $blockInfo['emoji']; ?> Block 5: <?php echo $blockInfo['title']; ?></h2>
            <span class="position-badge" style="background: rgba(244, 67, 54, 0.2); color: <?php echo $blockInfo['color']; ?>; border: 1px solid <?php echo $blockInfo['color']; ?>;">Positions <?php echo $blockInfo['positions']; ?></span>
        </div>
        <p style="margin-top: 15px; font-size: 1.1em; color: #ddd;">Bottom 3 go down. Minimum 0.9 PPG needed for escape. Every point is precious.</p>
    </div>
    <div class="panel" style="background: rgba(244, 67, 54, 0.1); border-left: 3px solid <?php echo $blockInfo['color']; ?>;">
        <h3>🚨 Survival Statistics</h3>
        <p style="color: #ddd; margin-bottom: 15px;">Historical data shows harsh reality:</p>
        <ul style="padding-left: 20px; color: #ddd;">
            <li style="padding: 8px 0; border-bottom: 1px solid rgba(244,67,54,0.2);">📊 <strong>After 10 games with 0-7 pts:</strong> 88% relegated</li>
            <li style="padding: 8px 0; border-bottom: 1px solid rgba(244,67,54,0.2);">⚠️ <strong>After 10 games with 8-10 pts:</strong> 47% relegated</li>
            <li style="padding: 8px 0; border-bottom: 1px solid rgba(244,67,54,0.2);">✅ <strong>After 10 games with 11+ pts:</strong> 11% relegated</li>
            <li style="padding: 8px 0;">🎯 <strong>Typical survival:</strong> 35-40 points minimum</li>
        </ul>
    </div>
    <div class="panel">
        <h3>🎯 Characteristics</h3>
        <div class="characteristics-grid">
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>📊 Target</h4><ul><li><strong>PPG:</strong> 0.9-1.2</li><li><strong>Points:</strong> 34-45</li><li><strong>Fight for survival</strong></li></ul></div>
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>🧠 Mentality</h4><ul><li>Crisis mode</li><li>Manager changes common</li><li>Desperate measures</li></ul></div>
            <div class="char-card" style="border-left-color: <?php echo $blockInfo['color']; ?>;"><h4>⚽ Tactics</h4><ul><li>Ultra-defensive</li><li>Fight for every ball</li><li>Six-pointer mentality</li></ul></div>
        </div>
    </div>
    <div class="panel" style="background: rgba(244, 67, 54, 0.05);"><h3>💡 Escape Routes</h3><p style="color: #ddd;">Teams escape Block 5 through:</p><ul style="padding-left: 20px; color: #ddd; margin-top: 10px;"><li style="padding: 8px 0;">🆕 New manager bounce</li><li style="padding: 8px 0;">⚡ January signings making immediate impact</li><li style="padding: 8px 0;">🏠 Turning home into fortress</li><li style="padding: 8px 0;">🎯 Winning six-pointers vs direct rivals</li></ul></div>
    <div class="panel"><div class="block-navigation"><a href="?tab=premier-league&subtab=blocks-4" class="nav-btn">← Block 4</a><a href="?tab=premier-league&subtab=blocks-dynamic" class="nav-btn" style="background: rgba(244, 67, 54, 0.1); color: <?php echo $blockInfo['color']; ?>;">🔥 Live Data</a><a href="?tab=premier-league&subtab=blocks-overview" class="nav-btn">Overview →</a></div></div>
</div>
<style>.block-detail-page{max-width:1400px;margin:0 auto}.block-header-section{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}.block-header-section h2{margin:0;font-size:2em}.position-badge{padding:8px 15px;border-radius:6px;font-size:0.9em;font-weight:bold}.characteristics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}.char-card{background:rgba(255,255,255,0.03);padding:20px;border-radius:8px;border-left:4px solid}.char-card h4{margin:0 0 15px 0;color:#fff}.char-card ul{list-style:none;padding:0;margin:0}.char-card ul li{padding:8px 0;color:#bbb;font-size:0.95em}.block-navigation{display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}.nav-btn{flex:1;min-width:150px;padding:12px 20px;background:rgba(255,255,255,0.05);color:#fff;text-decoration:none;text-align:center;border-radius:6px;border:1px solid rgba(255,255,255,0.1);transition:all 0.2s;font-weight:500}.nav-btn:hover{background:rgba(255,255,255,0.1);transform:translateY(-2px)}</style>