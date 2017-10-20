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
            slim ($full_path);
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

        if ($json == null || empty ($json))
            continue;
        if (!array_key_exists ('name', $json) || !array_key_exists ('geometry', $json))
           continue; 
        echo $json['name'] . "<br>";
        foreach($json['geometry']['coordinates'][0][0] as $point) { // for every point
            echo "$point[0], $point[1]<br>"; // echo the coordinates
        }
    }
}

/** 
 * Function that essentially slims all the geojson files given its path
 * Removes unnecessary data and syntax, that reduces file size and makes processing the file faster
 * when extracting the information
 */
function slim ($path) {
    // $json = file_get_contents ($path, FILE_USE_INCLUDE_PATH);
    $file = fopen ($path, "a+");
    $write_string = "";
    if ($file)
        while (($line = fgets ($file)) !== false) {
            $pos = strpos ($line, '"osm_type":"way"');
            $match = preg_match ('/^,\r\n/', $line);
            if ($pos !== false || $match == 1 || strcmp ($line, ',') === 0 || $line[0] == ',')
                continue;
            $write_string .= $line;
        }

    fclose ($file);
}

scan_dir ('.');

?>
