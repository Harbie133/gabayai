<?php
echo "<h2>Speed Test</h2>";

$start = microtime(true);
$regions = file_get_contents('http://localhost/GABAYAI/get_ph_locations.php?type=regions');
$time1 = round((microtime(true) - $start) * 1000, 2);

echo "✅ Regions loaded in: {$time1}ms<br>";
echo "Data: " . substr($regions, 0, 200) . "...<br><br>";

$start = microtime(true);
$cities = file_get_contents('http://localhost/GABAYAI/get_ph_locations.php?type=cities&code=130000000');
$time2 = round((microtime(true) - $start) * 1000, 2);

echo "✅ NCR Cities loaded in: {$time2}ms<br>";
$cities_data = json_decode($cities, true);
echo "Found " . count($cities_data) . " unique cities<br>";
echo "Sample: " . substr($cities, 0, 300) . "...<br>";
?>
