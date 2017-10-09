<?php

/**
 * Function that scans all directories and files from path given
 * Searches for geojson files to be parsed
 * To use for current directory simply pass in .
 */
function scan_dir ($path) {
    $contents = scandir ($path);
    for ($i = 2; $i < count ($contents); ++$i) {
        $item = $contents[$i];
        $full_path = "$path/$item";
        if (is_dir ($full_path)) {
            scan_dir ($full_path);
            continue;
        }
        $path_parts = pathinfo ($full_path);
        if ($path_parts == null || empty ($path_parts) || !array_key_exists ('extension', $path_parts) || !array_key_exists('filename', $path_parts))
            continue;

        $extension  = $path_parts['extension'];
        $filename   = $path_parts['filename'];
        if (strcmp ($extension, 'geojson') === 0 && strcmp ($filename, 'regions') !== 0) {
            extract_info ($full_path);
            echo "******************************************************************************************************************<br>";
        }
    }
}

/**
 * Function that parses the geojson files 
 */
function extract_info ($path) {
    $geojson = file_get_contents($path);
    $lines = explode("\n", $geojson);

    echo "************{$path}**************<br>";

    foreach ($lines as $line) { // for every city
        $json = json_decode($line, true); // decode the json
        echo $json['name'] . "<br>";
        foreach($json['geometry']['coordinates'][0][0] as $point) { // for every point
            echo "$point[0], $point[1]<br>"; // echo the coordinates
        }
    }
}

/** 
 * Future function to be implemented, essentially convert slim.php into this function 
 * so that before parsing a file it can be slimmed
 */
function slim ($path) {
}

scan_dir ('.');

?>
