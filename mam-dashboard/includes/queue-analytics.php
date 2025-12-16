<?php
// ============================================================================
// SHARED QUEUE ANALYTICS - Exit Classification & Metrics
// ============================================================================

class QueueAnalytics {
    private $db;
    private $voice_channel = '#anonamouse.net';
    
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    
    /**
     * Classify how a user exited the queue based on position
     * 
     * @param string $username The username to analyze
     * @param int $position Their position in queue (1-based)
     * @param int $snapshot_time Timestamp when they were seen
     * @param int $next_snapshot_time Timestamp of next snapshot
     * @return array Classification with type, icon, color, confidence
     */
    public function classifyExit($username, $position, $snapshot_time, $next_snapshot_time) {
        // Find -v event (removed from voice)
        $stmt = $this->db->prepare("
            SELECT time FROM messages
            WHERE channel = :channel
            AND type = 'mode'
            AND json_extract(msg, '$.text') = :pattern
            AND time >= :start
            AND time <= :end
            LIMIT 1
        ");
        $stmt->execute([
            'channel' => $this->voice_channel,
            'pattern' => '-v ' . $username,
            'start' => $snapshot_time * 1000,
            'end' => $next_snapshot_time * 1000
        ]);
        $exit_event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exit_event) {
            return [
                'type' => 'STILL_WAITING',
                'icon' => '⏳',
                'color' => '#faa61a',
                'confidence' => 100
            ];
        }
        
        $exit_time = $exit_event['time'];
        
        // Position 1 = almost always interviewed
        if ($position == 1) {
            // Check for staff interaction to confirm
            if ($this->checkStaffInteraction($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'INTERVIEWED',
                    'icon' => '✅',
                    'color' => '#43b581',
                    'confidence' => 100
                ];
            }
            
            // Even without visible interaction, position 1 is usually interviewed
            return [
                'type' => 'INTERVIEWED',
                'icon' => '✅',
                'color' => '#43b581',
                'confidence' => 95
            ];
        }
        
        // Position 2-5 = likely interviewed, check for disconnect
        elseif ($position <= 5) {
            // Check for disconnect first
            if ($this->checkDisconnect($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'DISCONNECTED',
                    'icon' => '🔌',
                    'color' => '#888',
                    'confidence' => 100
                ];
            }
            
            // Check staff interaction
            if ($this->checkStaffInteraction($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'INTERVIEWED',
                    'icon' => '✅',
                    'color' => '#43b581',
                    'confidence' => 95
                ];
            }
            
            // Front of queue, likely interviewed even without visible interaction
            return [
                'type' => 'LIKELY_INTERVIEWED',
                'icon' => '✓',
                'color' => '#43b581',
                'confidence' => 75
            ];
        }
        
        // Position 6-10 = mixed, need to check carefully
        elseif ($position <= 10) {
            if ($this->checkDisconnect($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'DISCONNECTED',
                    'icon' => '🔌',
                    'color' => '#888',
                    'confidence' => 100
                ];
            }
            
            if ($this->checkStaffInteraction($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'INTERVIEWED',
                    'icon' => '✅',
                    'color' => '#43b581',
                    'confidence' => 90
                ];
            }
            
            // Middle of queue, could go either way
            return [
                'type' => 'UNCERTAIN',
                'icon' => '❓',
                'color' => '#faa61a',
                'confidence' => 50
            ];
        }
        
        // Position 11+ = likely gave up
        else {
            if ($this->checkDisconnect($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'DISCONNECTED',
                    'icon' => '🔌',
                    'color' => '#888',
                    'confidence' => 100
                ];
            }
            
            // Staff interaction from back of queue = definitely interviewed
            if ($this->checkStaffInteraction($username, $snapshot_time * 1000, $exit_time)) {
                return [
                    'type' => 'INTERVIEWED',
                    'icon' => '✅',
                    'color' => '#43b581',
                    'confidence' => 100
                ];
            }
            
            // Back of queue, no interaction = gave up
            return [
                'type' => 'GAVE_UP',
                'icon' => '❌',
                'color' => '#f04747',
                'confidence' => 85
            ];
        }
    }
    
    /**
     * Check if user disconnected (quit/part events)
     */
    private function checkDisconnect($username, $start_time, $end_time) {
        $stmt = $this->db->prepare("
            SELECT * FROM messages
            WHERE channel = :channel
            AND type IN ('quit', 'part')
            AND json_extract(msg, '$.from.nick') = :username
            AND time >= :start
            AND time <= :end
            LIMIT 1
        ");
        $stmt->execute([
            'channel' => $this->voice_channel,
            'username' => $username,
            'start' => $start_time,
            'end' => $end_time
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Check for staff interaction indicating interview
     */
    private function checkStaffInteraction($username, $start_time, $end_time) {
        // Look for staff messages mentioning the user
        $stmt = $this->db->prepare("
            SELECT * FROM messages
            WHERE channel = :channel
            AND time >= :start
            AND time <= :end
            AND json_extract(msg, '$.text') LIKE '%' || :username || '%'
            AND (json_extract(msg, '$.text') LIKE '%interview%'
                 OR json_extract(msg, '$.text') LIKE '%PM%'
                 OR json_extract(msg, '$.text') LIKE '%message%'
                 OR json_extract(msg, '$.text') LIKE '%invite%')
            LIMIT 1
        ");
        $stmt->execute([
            'channel' => $this->voice_channel,
            'username' => $username,
            'start' => $start_time,
            'end' => $end_time + 300000 // +5 min buffer for after -v
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Analyze all exits between two snapshots
     */
    public function analyzeSnapshotTransition($prev_snapshot, $next_snapshot) {
        $exits = [];
        
        if (!$prev_snapshot || !$next_snapshot) return $exits;
        
        foreach ($prev_snapshot['members'] as $idx => $prev_member) {
            $found = false;
            foreach ($next_snapshot['members'] as $curr_member) {
                if ($curr_member['username'] == $prev_member['username']) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $classification = $this->classifyExit(
                    $prev_member['username'],
                    $idx + 1,
                    $prev_snapshot['time'],
                    $next_snapshot['time']
                );
                
                $exits[] = [
                    'username' => $prev_member['username'],
                    'position' => $idx + 1,
                    'wait_time' => $prev_member['wait_minutes'],
                    'classification' => $classification,
                    'prev_snapshot_time' => $prev_snapshot['time'],
                    'next_snapshot_time' => $next_snapshot['time']
                ];
            }
        }
        
        return $exits;
    }
    
    /**
     * Calculate comprehensive abandonment metrics for all snapshots
     */
    public function calculateAbandonmentMetrics($snapshots) {
        $stats = [
            'interviewed' => 0,
            'likely_interviewed' => 0,
            'gave_up' => 0,
            'disconnected' => 0,
            'uncertain' => 0,
            'by_position' => [
                '1-5' => ['interviewed' => 0, 'gave_up' => 0, 'disconnected' => 0, 'total' => 0],
                '6-10' => ['interviewed' => 0, 'gave_up' => 0, 'disconnected' => 0, 'total' => 0],
                '11-15' => ['interviewed' => 0, 'gave_up' => 0, 'disconnected' => 0, 'total' => 0],
                '16+' => ['interviewed' => 0, 'gave_up' => 0, 'disconnected' => 0, 'total' => 0]
            ],
            'all_exits' => []
        ];
        
        $prev = null;
        foreach ($snapshots as $snapshot) {
            if ($prev) {
                $exits = $this->analyzeSnapshotTransition($prev, $snapshot);
                
                foreach ($exits as $exit) {
                    $type = $exit['classification']['type'];
                    $pos = $exit['position'];
                    
                    // Store all exits
                    $stats['all_exits'][] = $exit;
                    
                    // Overall stats
                    if ($type == 'INTERVIEWED') {
                        $stats['interviewed']++;
                    } elseif ($type == 'LIKELY_INTERVIEWED') {
                        $stats['likely_interviewed']++;
                    } elseif ($type == 'GAVE_UP') {
                        $stats['gave_up']++;
                    } elseif ($type == 'DISCONNECTED') {
                        $stats['disconnected']++;
                    } elseif ($type == 'UNCERTAIN') {
                        $stats['uncertain']++;
                    }
                    
                    // By position
                    $pos_group = $pos <= 5 ? '1-5' : ($pos <= 10 ? '6-10' : ($pos <= 15 ? '11-15' : '16+'));
                    $stats['by_position'][$pos_group]['total']++;
                    
                    if (in_array($type, ['INTERVIEWED', 'LIKELY_INTERVIEWED'])) {
                        $stats['by_position'][$pos_group]['interviewed']++;
                    } elseif ($type == 'GAVE_UP') {
                        $stats['by_position'][$pos_group]['gave_up']++;
                    } elseif ($type == 'DISCONNECTED') {
                        $stats['by_position'][$pos_group]['disconnected']++;
                    }
                }
            }
            $prev = $snapshot;
        }
        
        return $stats;
    }
}
