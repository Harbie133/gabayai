<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? 'regions';
$code = $_GET['code'] ?? '';

// Static regions data
$regions = [
    ['code' => '010000000', 'name' => 'Region I (Ilocos Region)'],
    ['code' => '020000000', 'name' => 'Region II (Cagayan Valley)'],
    ['code' => '030000000', 'name' => 'Region III (Central Luzon)'],
    ['code' => '040000000', 'name' => 'Region IV-A (CALABARZON)'],
    ['code' => '050000000', 'name' => 'Region V (Bicol Region)'],
    ['code' => '060000000', 'name' => 'Region VI (Western Visayas)'],
    ['code' => '070000000', 'name' => 'Region VII (Central Visayas)'],
    ['code' => '080000000', 'name' => 'Region VIII (Eastern Visayas)'],
    ['code' => '090000000', 'name' => 'Region IX (Zamboanga Peninsula)'],
    ['code' => '100000000', 'name' => 'Region X (Northern Mindanao)'],
    ['code' => '110000000', 'name' => 'Region XI (Davao Region)'],
    ['code' => '120000000', 'name' => 'Region XII (SOCCSKSARGEN)'],
    ['code' => '130000000', 'name' => 'Region XIII (Caraga)'],
    ['code' => '140000000', 'name' => 'NCR (National Capital Region)'],
    ['code' => '150000000', 'name' => 'CAR (Cordillera Administrative Region)'],
    ['code' => '160000000', 'name' => 'BARMM (Bangsamoro Autonomous Region in Muslim Mindanao)'],
    ['code' => '170000000', 'name' => 'Region IV-B (MIMAROPA)']
];

if ($type === 'regions') {
    echo json_encode($regions);
    exit;
}

// For cities and barangays, use the API
$api_url = 'https://psgc.cloud/api/';

function fetchWithCURL($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $http_code !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

switch($type) {
    case 'cities':
        if (empty($code)) {
            echo json_encode(['error' => 'Region code required']);
            exit;
        }
        
        // First try to get provinces
        $provinces_url = $api_url . 'provinces?region=' . urlencode($code);
        $provinces = fetchWithCURL($provinces_url);
        
        $all_cities = [];
        
        if ($provinces && is_array($provinces) && !empty($provinces)) {
            // Get cities from each province
            foreach ($provinces as $province) {
                $cities_url = $api_url . 'cities-municipalities?province=' . urlencode($province['code']);
                $cities = fetchWithCURL($cities_url);
                
                if ($cities && is_array($cities)) {
                    $all_cities = array_merge($all_cities, $cities);
                }
            }
        } else {
            // Try to get cities directly (for NCR)
            $cities_url = $api_url . 'cities-municipalities?region=' . urlencode($code);
            $cities = fetchWithCURL($cities_url);
            
            if ($cities && is_array($cities)) {
                $all_cities = $cities;
            }
        }
        
        if (empty($all_cities)) {
            echo json_encode(['error' => 'No cities found for this region']);
            exit;
        }
        
        // Remove duplicates
        $unique_cities = [];
        $seen_codes = [];
        foreach ($all_cities as $city) {
            if (isset($city['code']) && !in_array($city['code'], $seen_codes)) {
                $unique_cities[] = $city;
                $seen_codes[] = $city['code'];
            }
        }
        
        // Sort alphabetically
        usort($unique_cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($unique_cities);
        break;
        
    case 'barangays':
        if (empty($code)) {
            echo json_encode(['error' => 'City code required']);
            exit;
        }
        
        $url = $api_url . 'barangays?city-municipality=' . urlencode($code);
        $barangays = fetchWithCURL($url);
        
        if (!$barangays || !is_array($barangays)) {
            echo json_encode(['error' => 'Failed to fetch barangays']);
            exit;
        }
        
        // Sort alphabetically
        usort($barangays, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($barangays);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid type']);
}
?>
