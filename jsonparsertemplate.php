<?php

$file = "level8.geojson";

$geojson = file_get_contents("$file");
$lines = explode("\n", $geojson);

foreach ($lines as $line) { // for every city
    $json = json_decode($line, true); // decode the json
    echo $json['name'] . "<br>";
    foreach($json['geometry']['coordinates'][0][0] as $point) { // for every point
        echo "$point[0], $point[1]<br>"; // echo the coordinates
    }
}
