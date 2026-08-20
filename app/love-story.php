<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';

$config = load_config();
$loveStoryItems = $config['love_story']['items'] ?? [];

// Filter only enabled items
$enabledItems = array_filter($loveStoryItems, function($item) {
    return !empty($item['enabled']);
});

// Re-index array
$enabledItems = array_values($enabledItems);

// Sort by order
usort($enabledItems, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

// Build output with image URLs
$output = [];
foreach ($enabledItems as $item) {
    $imagePath = $item['image'] ?? '';
    $imageUrl = '';
    
    if (!empty($imagePath)) {
        // Check if it's a full URL or relative path
        if (strpos($imagePath, 'http') === 0) {
            $imageUrl = $imagePath;
        } else {
            $imageUrl = public_path($imagePath);
        }
    }
    
    $output[] = [
        'id' => $item['id'] ?? '',
        'title' => $item['title'] ?? '',
        'subtitle' => $item['subtitle'] ?? '',
        'description' => $item['description'] ?? '',
        'image' => $imageUrl,
        'image_alt' => $item['image_alt'] ?? '',
        'image_caption' => $item['image_caption'] ?? '',
        'icon' => $item['icon'] ?? '',
        'event_date' => $item['event_date'] ?? '',
        'order' => $item['order'] ?? 0
    ];
}

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
