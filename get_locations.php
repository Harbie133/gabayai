<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? '';
$code = $_GET['code'] ?? '';

// Quick fetch with short timeout
function fetch($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result ? json_decode($result, true) : null;
}

if ($type === 'regions') {
    // Static regions - loads instantly
    echo json_encode([
        ['code' => '010000000', 'name' => 'Region I - Ilocos Region'],
        ['code' => '020000000', 'name' => 'Region II - Cagayan Valley'],
        ['code' => '030000000', 'name' => 'Region III - Central Luzon'],
        ['code' => '040000000', 'name' => 'Region IV-A - CALABARZON'],
        ['code' => '050000000', 'name' => 'Region V - Bicol Region'],
        ['code' => '060000000', 'name' => 'Region VI - Western Visayas'],
        ['code' => '070000000', 'name' => 'Region VII - Central Visayas'],
        ['code' => '080000000', 'name' => 'Region VIII - Eastern Visayas'],
        ['code' => '090000000', 'name' => 'Region IX - Zamboanga Peninsula'],
        ['code' => '100000000', 'name' => 'Region X - Northern Mindanao'],
        ['code' => '110000000', 'name' => 'Region XI - Davao Region'],
        ['code' => '120000000', 'name' => 'Region XII - SOCCSKSARGEN'],
        ['code' => '130000000', 'name' => 'NCR - National Capital Region'],
        ['code' => '140000000', 'name' => 'CAR - Cordillera Administrative Region'],
        ['code' => '150000000', 'name' => 'BARMM - Bangsamoro'],
        ['code' => '160000000', 'name' => 'Region XIII - Caraga'],
        ['code' => '170000000', 'name' => 'Region IV-B - MIMAROPA']
    ]);
    exit;
}

if ($type === 'cities') {
    if (empty($code)) {
        echo json_encode(['error' => 'Region code required']);
        exit;
    }
    
    // Fetch cities filtered by region
    $data = fetch("https://psgc.gitlab.io/api/regions/{$code}/cities-municipalities/");
    
    if (!$data) {
        echo json_encode(['error' => 'Failed to load cities']);
        exit;
    }
    
    // Remove duplicates based on code
    $unique = [];
    $seen = [];
    foreach ($data as $item) {
        if (!in_array($item['code'], $seen)) {
            $unique[] = $item;
            $seen[] = $item['code'];
        }
    }
    
    // Sort alphabetically
    usort($unique, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode($unique);
    exit;
}

if ($type === 'barangays') {
    if (empty($code)) {
        echo json_encode(['error' => 'City code required']);
        exit;
    }
    
    // Fetch barangays for specific city
    $data = fetch("https://psgc.gitlab.io/api/cities-municipalities/{$code}/barangays/");
    
    if (!$data) {
        echo json_encode(['error' => 'Failed to load barangays']);
        exit;
    }
    
    // Remove duplicates
    $unique = [];
    $seen = [];
    foreach ($data as $item) {
        if (!in_array($item['code'], $seen)) {
            $unique[] = $item;
            $seen[] = $item['code'];
        }
    }
    
    // Sort alphabetically
    usort($unique, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode($unique);
    exit;
}

echo json_encode(['error' => 'Invalid type']);
?>
