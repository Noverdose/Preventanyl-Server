<?php
/**
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-27
 * Time: 5:53 PM
 */

ini_set("memory_limit", "512M");
//
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq/Adapter');
//
//
//require_once('LINQ/Classes/PHPLinq.php');

require_once 'mapping/MapTools.php';

$timeStart = floatval(microtime(true));

// to run: > php city-mapper.php <lat> <long>
// NOTE: firebase stores them in backwards format but this script accepts in correct format. Input a lat / long from

$mode = $argv[1];

// generates bounding box cache if
//generateBoundingBoxCache('-1 days');

//$cached = isset($argv[3]) && $argv[3] == '--cached';


switch(strtolower(trim($mode))) {
    case 'cache' :
            MapTools::getMap($argv[2]);
            $time = floatval(microtime(true));
            die("caching total time elapsed: ". (($time - $timeStart) * 1000) . " ms\n");
    case 'sequential' :

        $myLat = $argv[2];
        $myLong = $argv[3];


        $boundingBoxes = MapTools::getMapCached("locations.json");
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $count = count($boundingBoxes);
        echo "sequential search from 0 to $count\n";

        $matchingBoxes = MapTools::sequentialSearch($boundingBoxes, 0);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n\n";

        $rayCastMatches = [];
        foreach($matchingBoxes AS $box) {
            $inRegion = MapTools::isInRegion($box['name']);
            if($inRegion) $rayCastMatches[] = $box;

        }

        if(empty($rayCastMatches)) {
            $regionBoxes = MapTools::getMapCached("regions.json");

            $count = count($regionBoxes);

            //foreach($regionBoxes AS $box) {var_dump($box);}

            echo "region sequential search from 0 to $count\n";
            $matchingBoxes = MapTools::sequentialSearch($boundingBoxes, 0);

            echo "found ".count($matchingBoxes)." matching region boxes!\n";




            $rayCastMatches = [];
            foreach($matchingBoxes AS $box) {
                $inRegion = MapTools::isInRegion($box['name'], 'regions');
                if($inRegion) $rayCastMatches[] = $box;
            }

        }


        $time = floatval(microtime(true));
        echo "\n\n---------------------\n";
        echo "sequential search  total time elapsed: " . floor(($time - $timeStart) * 1000) . " ms" . PHP_EOL;
        exit();


    case 'find'  :


        $myLat = $argv[2];
        $myLong = $argv[3];


        $boundingBoxes = MapTools::getMapCached("locations.json");
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $startIndex = MapTools::binarySearch($boundingBoxes, count($boundingBoxes) - 1);

        echo "(binary) sequential search from $startIndex to 0\n";
        $matchingBoxes = MapTools::sequentialSearch($boundingBoxes, $startIndex);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n";

        $rayCastMatches = [];
        foreach($matchingBoxes AS $box) {
            $inRegion = MapTools::isInRegion($box['name']);
            if($inRegion) $rayCastMatches[] = $box;
        }

        if(empty($rayCastMatches)) {
            $regionBoxes = MapTools::getMapCached("regions.json");


            $startIndex = MapTools::binarySearch($regionBoxes, count($regionBoxes) - 1);

            echo "region (binary) sequential search from $startIndex to 0\n";
            $matchingBoxes = MapTools::sequentialSearch($boundingBoxes, $startIndex);

            echo "found ".count($matchingBoxes)." matching region boxes!\n";

            $rayCastMatches = [];
            foreach($matchingBoxes AS $box) {
                $inRegion = MapTools::isInRegion($box['name'], 'regions');
                if($inRegion) $rayCastMatches[] = $box;
            }

        }



        $time = floatval(microtime(true));
        echo "\n\n---------------------\n";
        echo "binary search  total time elapsed: " . floor(($time - $timeStart) * 1000) . " ms" . PHP_EOL;
        exit();

    default : die("usage: city-mapper.php cache|find <lat> <long>");
}


//function generateBoundingBoxCache($oldestValid) {
//
//    $name = "tmp";
//
//    $coordinateFolder = "locations/{$name}/geometry";
//    $coordinateFile = "$coordinateFolder/coordinates.json";
//    $cachedFile = "./firebase-cached/$coordinateFile";
//
//    if(!file_exists($cachedFile) || filemtime($cachedFile) < strtotime('-1 days')) {
//        // cache file
//
//        $url = "https://preventanyl.firebaseio.com/$coordinateFile";
//
//        echo "downloading $url\n";
////    $ch = curl_init($url);
////    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
////    curl_setopt($ch, CURLOPT_POST, 1);
////    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
//        $json = curl_exec($ch);
//        curl_close($ch);
//
//
//        $dir = "firebase-cached/$coordinateFolder";
//
//        echo "caching to $dir";
//
//        if (!is_dir($dir)) {
//            // dir doesn't exist, make it
//
//            mkdir($dir, 0777, true);
//        }
//
//        file_put_contents($cachedFile, $json);
//
//    }
//}

