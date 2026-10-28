<?php
// Derived labels only: RSS does not expose the creator's YouTube metadata tags.
function dashboard_tags($title, $locations = []) {
    preg_match_all('/(?<![\p{L}\p{N}_])#([\p{L}\p{N}_]+)/u', $title, $matches);
    $tags = array_map('strtolower', $matches[1]);
    $topics = [
        'travel' => '/\b(travel|trip|journey|tour|exploring)\b/iu',
        'food' => '/\b(food|restaurant|cooking|breakfast|dinner|street food)\b/iu',
        'walking' => '/\b(walk|walking|hike|hiking)\b/iu',
        'transport' => '/\b(train|railway|bus|flight|airport|ferry)\b/iu',
        'live' => '/\b(livestream|live stream)\b/iu',
        'shorts' => '/(?:#shorts\b|\byoutube shorts\b)/iu',
        'review' => '/\b(review|reviewing)\b/iu',
        'guide' => '/\b(guide|tutorial|how to|tips)\b/iu',
        'history' => '/\b(history|historic|historical)\b/iu',
    ];
    foreach ($topics as $tag => $pattern) {
        if (preg_match($pattern, $title)) $tags[] = $tag;
    }
    $normalize = static function ($value) { return preg_replace('/[^\p{L}\p{N}]/u', '', strtolower($value)); };
    $excluded = array_map($normalize, array_merge($locations, [
        'UK', 'England', 'British', 'London', 'Hull', 'Germany', 'Berlin', 'Munich',
        'Spain', 'Barcelona', 'Madrid', 'France', 'Paris', 'USA', 'California', 'America', 'Home'
    ]));
    return array_values(array_filter(array_unique($tags), static function ($tag) use ($excluded, $normalize) {
        return !in_array($normalize($tag), $excluded, true);
    }));
}
