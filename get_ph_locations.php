<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? '';
$code = $_GET['code'] ?? '';

function quickFetch($url, $timeout = 5) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode != 200) {
        return null;
    }
    
    return json_decode($response, true);
}

switch($type) {
    case 'regions':
        // Static regions - loads instantly
        $regions = [
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
        ];
        echo json_encode($regions);
        break;
        
    case 'cities':
        if (empty($code)) {
            echo json_encode(['error' => 'Region code required']);
            exit;
        }
        
        // First try: Direct cities from region (works for NCR and some regions)
        $cities = quickFetch("https://psgc.gitlab.io/api/regions/{$code}/cities-municipalities/");
        
        if ($cities === null || empty($cities)) {
            // Second try: Get provinces first, then cities
            $provinces = quickFetch("https://psgc.gitlab.io/api/regions/{$code}/provinces/");
            
            if ($provinces && is_array($provinces)) {
                $all_cities = [];
                $seen_codes = []; // Track unique city codes
                
                foreach ($provinces as $province) {
                    if (!isset($province['code'])) continue;
                    
                    $province_cities = quickFetch("https://psgc.gitlab.io/api/provinces/{$province['code']}/cities-municipalities/");
                    
                    if ($province_cities && is_array($province_cities)) {
                        foreach ($province_cities as $city) {
                            // Only add if code hasn't been seen before (removes duplicates)
                            if (isset($city['code']) && !in_array($city['code'], $seen_codes)) {
                                $all_cities[] = $city;
                                $seen_codes[] = $city['code'];
                            }
                        }
                    }
                }
                
                $cities = $all_cities;
            }
        }
        
        if (empty($cities)) {
            echo json_encode(['error' => 'No cities found']);
            exit;
        }
        
        // Remove any remaining duplicates by code
        $unique_cities = [];
        $codes = [];
        foreach ($cities as $city) {
            if (isset($city['code']) && !in_array($city['code'], $codes)) {
                $unique_cities[] = $city;
                $codes[] = $city['code'];
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
        
        $barangays = quickFetch("https://psgc.gitlab.io/api/cities-municipalities/{$code}/barangays/");
        
        if ($barangays === null || empty($barangays)) {
            echo json_encode(['error' => 'No barangays found']);
            exit;
        }
        
        // Remove duplicates by code
        $unique_barangays = [];
        $codes = [];
        foreach ($barangays as $barangay) {
            if (isset($barangay['code']) && !in_array($barangay['code'], $codes)) {
                $unique_barangays[] = $barangay;
                $codes[] = $barangay['code'];
            }
        }
        
        // Sort alphabetically
        usort($unique_barangays, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode($unique_barangays);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid type']);
}
?>
