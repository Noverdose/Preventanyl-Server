<?php

    function scan_directories () {
        $dir = '.';
        $contents = scandir ($dir);

        for ($i = 2; $i < count($contents); ++$i) {
            $json = file_get_contents ($contents[$i], FILE_USE_INCLUDE_PATH);
            $lines = explode ("\n", $json);
            for ($j = 0; $j < count($lines); ++$j) {
                $pos = strpos ($lines[$j], "\"osm_type\":\"way\"");
                $match = preg_match ("/^,\r?\n/", $lines[$j]);
                if ($pos !== false || $match == 1) {
                    unset ($lines[$j]);
                    // var_dump ($lines[$j]);
                    // array_splice ($lines, $j, $j + 1);
                }
            }
            $output = implode ("\n", $lines);
            file_put_contents ($contents[$i], $output);
        }
           
        // print_r ($contents);
    }

    scan_directories ();

?>
