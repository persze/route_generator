<?php
// ors_random_20.php
// Teljes, kész másolható PHP oldal
// ORS API kulcs
$apiKey = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjVkYjU5NWUxYjg4OTRiZmI4NDhiZjE1NjIzNzY4OGU0IiwiaCI6Im11cm11cjY0In0=';

// Telephely koordinátái [lon, lat] formátumban
$depot = [18.369111, 48.230145]; 

// Hány címet választunk véletlenszerűen
$select_count = 50;

// JSON fájl neve
$jsonFile = 'database.json';

// ORS profil
$profile = 'driving-car';

// ---------------- SEGÉDFÜGGVÉNYEK ----------------
function read_json($filename) {
    if (!file_exists($filename)) return null;
    $data = file_get_contents($filename);
    $json = json_decode($data, true);
    if (!$json) return null;
    return $json['elements'] ?? $json; // Overpass JSON esetén
}

function call_matrix_api($locations, $apiKey, $profile) {
    $url = "https://api.openrouteservice.org/v2/matrix/$profile";
    $payload = json_encode(['locations'=>$locations,'metrics'=>['distance','duration']]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 60
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if (!$resp) return ['error'=>"cURL error: $err"];
    $data = json_decode($resp,true);
    if (!$data) return ['error'=>"JSON decode error"];
    return $data;
}

// ----------------- ADATOK BETÖLTÉSE ----------------
$elements = read_json($jsonFile);
if (!$elements) die("<pre>Hiba: Nem sikerült beolvasni a database.json-t vagy rossz a JSON formátum.</pre>");

// Kiválasztott pontok
$all_points = [];
foreach($elements as $el){
    if(!isset($el['lat']) || !isset($el['lon'])) continue;
    $tags = $el['tags'] ?? [];
    $city = $tags['addr:city'] ?? '';
    $street = $tags['addr:street'] ?? '';
    $housenumber = $tags['addr:housenumber'] ?? '';
    if(!$city && !$street && !$housenumber) continue;
    $all_points[] = [
        'city'=>$city,
        'street'=>$street,
        'housenumber'=>$housenumber,
        'lat'=>$el['lat'],
        'lon'=>$el['lon']
    ];
}

// Véletlenszerűen kiválasztunk 20 címet
shuffle($all_points);
$selected = array_slice($all_points,0,$select_count);

// ----------------- ORS LOCATIONS ----------------
$locations = [$depot]; // telephely
foreach($selected as $p) $locations[] = [$p['lon'],$p['lat']];

// ----------------- ORS LEKÉRDEZÉS ----------------
$apiResult = call_matrix_api($locations,$apiKey,$profile);
$distances = $apiResult['distances'] ?? null;
$durations = $apiResult['durations'] ?? null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<title>Távolságok</title>
<style>
body {font-family: Arial,sans-serif;background:#f4f4f4;padding:20px;color:#222;}
h1{color:#0b4da2;text-align:center;}
table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 0 10px rgba(0,0,0,0.1);}
th,td{padding:8px 12px;border-bottom:1px solid #ddd;}
th{background:#0b4da2;color:white;}
tr:hover{background:#eef;}
</style>
</head>
<body>
<h1>Távolságok a telephelytől</h1>
<table>
<thead><tr><th>#</th><th>Város</th><th>Utca</th><th>Házszám</th><th>Lat</th><th>Lon</th><th>Távolság (km)</th><th>Idő (perc)</th></tr></thead>
<tbody>
<?php
if(!$distances){
    echo "<tr><td colspan='8'>Hiba a lekérdezés során.</td></tr>";
} else {
    for($i=1;$i<=count($selected);$i++){
        $p = $selected[$i-1];
        $dist = round($distances[0][$i]/1000,2); // m -> km
        $time = round($durations[0][$i]/60,1); // sec -> perc
        echo "<tr>";
        echo "<td>$i</td>";
        echo "<td>{$p['city']}</td>";
        echo "<td>{$p['street']}</td>";
        echo "<td>{$p['housenumber']}</td>";
        echo "<td>{$p['lat']}</td>";
        echo "<td>{$p['lon']}</td>";
        echo "<td>$dist</td>";
        echo "<td>$time</td>";
        echo "</tr>";
    }
}
?>
</tbody>
</table>
</body>
</html>
