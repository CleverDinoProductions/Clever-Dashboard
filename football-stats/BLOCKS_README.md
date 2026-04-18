# Blocks of 4 Analysis Feature

## Overview
This feature implements your analytical framework for dividing the Premier League table into 5 equal blocks of 4 teams each, providing clear insights into competitive zones, team trajectories, and survival/success probabilities.

## Structure

### Main Tab: Blocks Overview
**File:** `tabs/premier-league/blocks-overview.php`
- General information about the framework
- Explanation of all 5 blocks
- Why blocks of 4 works universally across English football
- Mathematical reality of point accumulation
- Historical survival statistics
- Quick navigation to individual blocks

### Individual Block Tabs

#### Block 1: Title Contenders (Positions 1-4)
**File:** `tabs/premier-league/blocks-1.php` ✅ Created
- 🏆 Champions League qualification zone
- Current teams in Block 1
- Block characteristics and mentality
- Movement analysis (teams entering/leaving)
- Head-to-head records
- Historical context

#### Block 2: European Zone (Positions 5-8)
**File:** `tabs/premier-league/blocks-2.php` ⚠️ To be created
- 🌟 Europa League / Conference League qualification
- Use `blocks-1.php` as template
- Adjust colors to green theme (#4CAF50)
- Focus on teams pushing for Europe

#### Block 3: Safe Mid-Table (Positions 9-12)
**File:** `tabs/premier-league/blocks-3.php` ⚠️ To be created
- ✅ Premier League security zone (Leeds' target position)
- Use `blocks-1.php` as template
- Adjust colors to blue theme (#2196F3)
- Emphasize stability and lack of relegation concerns

#### Block 4: Danger Zone (Positions 13-16)
**File:** `tabs/premier-league/blocks-4.php` ⚠️ To be created
- ⚠️ Teams requiring attention to avoid relegation
- Use `blocks-1.php` as template
- Adjust colors to orange theme (#FF9800)
- Focus on pressure and tactical changes needed

#### Block 5: Relegation Battle (Positions 17-20)
**File:** `tabs/premier-league/blocks-5.php` ⚠️ To be created
- 🔻 Immediate relegation danger
- Use `blocks-1.php` as template
- Adjust colors to red theme (#F44336)
- Emphasize mathematical escape probabilities

## Navigation Structure

### URL Parameters
- Overview: `?tab=2025-2026&league=premier-league&subtab=blocks-overview`
- Block 1: `?tab=2025-2026&league=premier-league&subtab=blocks-1`
- Block 2: `?tab=2025-2026&league=premier-league&subtab=blocks-2`
- Block 3: `?tab=2025-2026&league=premier-league&subtab=blocks-3`
- Block 4: `?tab=2025-2026&league=premier-league&subtab=blocks-4`
- Block 5: `?tab=2025-2026&league=premier-league&subtab=blocks-5`

### Navigation Levels
1. **Main Pills:** 2025/26 | 2026/27 | World Cup 2026
2. **Sub Pills:** Premier League | Championship
3. **Tertiary Pills:** League-specific pages for the selected season and league
4. **Blocks Sub-Navigation** (only shows in blocks section): Overview | Live Data | Block 1 | Block 2 | Block 3 | Block 4 | Block 5

## Key Features from Your Analysis

### Universal Application
- **Premier League:** 20 teams = 5 blocks of 4
- **Championship:** 24 teams = 6 blocks of 4
- **League One:** 24 teams = 6 blocks of 4
- **League Two:** 24 teams = 6 blocks of 4

### Psychological Boundaries
Each block represents:
- Distinct team mentalities
- Different tactical approaches
- Specific pressure levels
- Clear success/survival metrics

### Mathematical Reality
By October-November (Matchday 10-12):
- Block positions typically stabilize
- Bottom blocks struggle to catch up as top blocks accumulate points faster
- Historical survival rates become statistically predictive

### Survival Statistics (After 10 Games)
- **11+ points:** 89% survival rate ✅
- **8-10 points:** 53% survival rate ⚠️
- **0-7 points:** Only 12% survival rate 🔻

## Creating Additional Block Tabs

To create blocks 2-5, follow this template:

1. Copy `tabs/premier-league/blocks-1.php`
2. Replace block number and title
3. Update color scheme:
   - Block 1: Gold (#FFD700) - Already created
   - Block 2: Green (#4CAF50)
   - Block 3: Blue (#2196F3)
   - Block 4: Orange (#FF9800)
   - Block 5: Red (#F44336)
4. Update position ranges in header badge
5. Adjust characteristics based on block positioning
6. Update placeholder team data (or connect to database)
7. Modify navigation buttons at bottom

## Database Integration

Currently using placeholder data. To connect to your database:

```php
// In each block file, replace placeholder data:
$blockTeams = $pdo->query("
    SELECT * FROM premier_league_table 
    WHERE position BETWEEN ? AND ? 
    ORDER BY position
")->execute([startPos, endPos])->fetchAll();
```

Example for Block 1:
```php
$block1Teams = $pdo->query("
    SELECT * FROM premier_league_table 
    WHERE position BETWEEN 1 AND 4 
    ORDER BY position
")->fetchAll();
```

## Styling

All necessary CSS has been added to `assets/style.css`:
- Tertiary navigation pills (.tertiary-pills, .pill-tab-small)
- Responsive design for mobile/tablet
- Block-specific color themes
- Smooth animations and transitions

## Next Steps

1. ✅ Create blocks overview page
2. ✅ Create Block 1 detailed page
3. ✅ Update navigation structure
4. ✅ Add tertiary navigation styling
5. ⚠️ Create Block 2 page (copy Block 1, adjust colors/content)
6. ⚠️ Create Block 3 page (copy Block 1, adjust colors/content)
7. ⚠️ Create Block 4 page (copy Block 1, adjust colors/content)
8. ⚠️ Create Block 5 page (copy Block 1, adjust colors/content)
9. ⚠️ Connect to live database for real-time team data
10. ⚠️ Add matchday filtering capability
11. ⚠️ Add historical block movement tracking

## Related Files

- `index.php` - Main routing logic
- `includes/header.php` - Navigation structure
- `assets/style.css` - Styling for all blocks
- `tabs/premier-league/blocks-overview.php` - Framework overview
- `tabs/premier-league/blocks-1.php` - Block 1 template

## Your Analytical Insights Incorporated

Based on your conversation history files:
- Block stabilization by October-November
- Point accumulation mathematics (rich get richer phenomenon)
- Historical survival rates and thresholds
- Universal applicability across English football pyramid
- Leeds United's curse-breaking trajectory in Block 3
- Movement patterns between blocks throughout the season

---

**Created:** December 27, 2025
**Version:** 1.0
**Status:** Core structure complete, individual block pages 2-5 to be created