<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$type = $_GET['type'] ?? 'regions';
$code = $_GET['code'] ?? '';

$api_url = 'https://psgc.cloud/api/';

// Function to make API calls with error handling
function fetchAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || $http_code !== 200) {
        return ['error' => 'API request failed: ' . $error, 'http_code' => $http_code];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }
    
    return $decoded;
}

switch($type) {
    case 'regions':
        $url = $api_url . 'regions';
        $result = fetchAPI($url);
        
        if (isset($result['error'])) {
            echo json_encode(['error' => $result['error']]);
        } else {
            echo json_encode($result);
        }
        break;
        
    case 'cities':
        if (empty($code)) {
            echo json_encode(['error' => 'Region code required']);
            exit;
        }
        
        // Get all provinces first
        $provinces_url = $api_url . 'provinces?region=' . urlencode($code);
        $provinces = fetchAPI($provinces_url);
        
        if (isset($provinces['error'])) {
            echo json_encode(['error' => 'Failed to fetch provinces: ' . $provinces['error']]);
            exit;
        }
        
        // If no provinces (like NCR), try to get cities directly
        if (empty($provinces)) {
            $cities_url = $api_url . 'cities-municipalities?region=' . urlencode($code);
            $cities = fetchAPI($cities_url);
            
            if (isset($cities['error'])) {
                echo json_encode(['error' => 'Failed to fetch cities: ' . $cities['error']]);
            } else {
                // Sort cities alphabetically
                usort($cities, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                echo json_encode($cities);
            }
            exit;
        }
        
        $all_cities = [];
        foreach ($provinces as $province) {
            if (!isset($province['code'])) continue;
            
            $cities_url = $api_url . 'cities-municipalities?province=' . urlencode($province['code']);
            $cities = fetchAPI($cities_url);
            
            if (!isset($cities['error']) && is_array($cities)) {
                $all_cities = array_merge($all_cities, $cities);
            }
        }
        
        // Remove duplicates based on code
        $unique_cities = [];
        $seen_codes = [];
        foreach ($all_cities as $city) {
            if (!isset($city['code'])) continue;
            if (!in_array($city['code'], $seen_codes)) {
                $unique_cities[] = $city;
                $seen_codes[] = $city['code'];
            }
        }
        
        // Sort cities alphabetically
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
        $result = fetchAPI($url);
        
        if (isset($result['error'])) {
            echo json_encode(['error' => $result['error']]);
        } else {
            // Sort barangays alphabetically
            usort($result, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            echo json_encode($result);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid type']);
        exit;
}
?>
