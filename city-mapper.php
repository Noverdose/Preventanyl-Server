<?php
/**
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-27
 * Time: 5:53 PM
 */

ini_set("memory_limit", "512M");

$timeStart = floatval(microtime(true));

// to run: > php city-mapper.php <lat> <long>
// NOTE: firebase stores them in backwards format but this script accepts in correct format. Input a lat / long from

$mode = $argv[1];

// generates bounding box cache if
//generateBoundingBoxCache('-1 days');

//$cached = isset($argv[3]) && $argv[3] == '--cached';


switch(strtolower(trim($mode))) {
    case 'cache' :
            getMap($argv[2]);
            $time = floatval(microtime(true));
            die("caching total time elapsed: ". (($time - $timeStart) * 1000) . " ms\n");
    case 'sequential' :

        $myLat = $argv[2];
        $myLong = $argv[3];


        $boundingBoxes = getMapCached();
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $startIndex = count($boundingBoxes) - 1;
        echo "sequential search from $startIndex to 0\n";

        $matchingBoxes = sequentialSearch($boundingBoxes, $startIndex);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n";

        foreach($matchingBoxes AS $box) {
            getRegionPoints($box['name']);
        }

        $time = floatval(microtime(true));
        echo "\n\n---------------------\n";
        echo "sequential search  total time elapsed: " . floor(($time - $timeStart) * 1000) . " ms" . PHP_EOL;
        exit();


    case 'find'  :


        $myLat = $argv[2];
        $myLong = $argv[3];


        $boundingBoxes = getMapCached();
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $startIndex = binarySearch($boundingBoxes, 0, count($boundingBoxes) - 1);

        echo "sequential search from $startIndex to 0\n";

        $matchingBoxes = sequentialSearch($boundingBoxes, $startIndex);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n";

        foreach($matchingBoxes AS $box) {
            getRegionPoints($box['name']);
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


function getMap($file) {

    $data = json_decode(file_get_contents($file), true);

    $boundingBoxes = [];

    global $timeStart;

    foreach($data AS $key => $location) {

        //echo $key . "\n";

        //if($key != 'Alma') continue;

        $minLong = 0;
        $minLat = 0;
        $maxLong = 0;
        $maxLat = 0;


        //if($key == 'Alma') echo "minMaxLat: $minLat:$maxLat, minMaxLong: $minLong:$maxLong\n";


        if(!isset($location['geometry']['coordinates'])) {
            continue;
        }

        foreach(@$location['geometry']['coordinates'] AS $coordinate) {
            $lat = $coordinate['lat'];
            $long = $coordinate['long'];

            //      if($key == 'Alma') echo "minMaxLat: $minLat:$maxLat, minMaxLong: $minLong:$maxLong\n";


//        if($key == 'Alma') echo "lat: $lat, long: $long\n";

            if(empty($minLat) || $lat < $minLat) $minLat = $lat;
            if(empty($minLong) || $long < $minLong) $minLong = $long;

            if(empty($maxLat) || $lat > $maxLat) $maxLat = $lat;
            if(empty($maxLong) || $long > $maxLong) $maxLong = $long;

            //if($key == 'Alma') echo "minMaxLat: $minLat:$maxLat, minMaxLong: $minLong:$maxLong\n";


            $lat = null;
            $long = null;



        }

        $boundingBoxes[$key] = ['name' => $key,
                                'min' => ['lat' => $minLat, 'long' => $minLong],
                                'max' => ['lat' => $maxLat, 'long' => $maxLong]];

        $data['locations'][$key]['Bounding Box'] = $boundingBoxes[$key];

        //if($key != 'Alma') continue;
        //echo $key . ": ";
        //print_r($data['locations'][$key]['Bounding Box']);
    }

    $time = floatval(microtime(true));
    echo "mapping time elapsed: " . floor(($time - $timeStart) * 1000) . " ms" . PHP_EOL;

    // file_put_contents("mapped.serialized", serialize($data));

    saveFile($boundingBoxes);


    // apc_store('map', $data);


    return $boundingBoxes;

}

function lat_sort($a,$b)
{

    //if(!isset($a['Bounding Box']) || !isset($b['Bounding Box'])) return -1;

    $aMinLat = @$a['min']['lat'];
    $bMinLat = @$b['min']['lat'];

    //$aGreaterThanB =  > $b['Bounding Box']['min']['lat'];
    //$regionMax = $location['Bounding Box']['max'];


    //if($aMinLat == $bMinLat) return 0;

    return $aMinLat < $bMinLat ? 1 : -1;

}
//
//$a=array(4,2,8,6);
//usort($a,"my_sort");

function saveFile($boundingBoxes) {

    //$loc = $data['locations'];

    usort($boundingBoxes, "lat_sort");

    //$data['locations'] = $loc;

    print_r($boundingBoxes);

    file_put_contents("mapped.serialized", serialize($boundingBoxes));

}

function getMapCached() {
    $data = unserialize(file_get_contents("mapped.serialized"));

    //print_r($data);

    //$data = apc_fetch('map');

    return $data;

}


function sequentialSearch($boundingBoxes, $startIndex) {

    //print_r($boundingBox);

    global $myLat, $myLong;

    $boundingBoxes = array_values($boundingBoxes);

    $count = count($boundingBoxes);

    //echo "checking sequential search starting at $startIndex: {$boundingBoxes[$startIndex]['name']}\n";

    $matching = [];

    for($i=$startIndex; $i>=0; $i--) {

        $location = $boundingBoxes[$i];

        $name = $location['name'];

        //if(!isset($location['Bounding Box'])) continue;


        $regionMin = $location['min'];
        $regionMax = $location['max'];


        //echo "checking $name ($myLat:$myLong) (between $regionMin[lat]:$regionMin[long] and $regionMax[lat]:$regionMax[long] \n";



        if( $myLat >= $regionMin['lat'] && $myLat <= $regionMax['lat']
            && $myLong >= $regionMin['long'] && $myLong <= $regionMax['long'] ) {
            $matching[] = $location;
            echo "\nPossible region: $name @$i\n";
        }



//    //if($name != 'Alma') continue;
//    echo $name . ": \n";
//    print_r($location['Bounding Box']);
    }

    return $matching;

}




//$locations = $data["locations"];


function binarySearch($locations, $min, $max) {

    //echo "binarySearch(\$locations, $min, $max) \n";

    global $myLat, $myLong;
    //global $locations;
    //global $keys;

    $index = intval(floor($min + (($max - $min) / 2)));

    //$key = $keys[$index];



    if ($myLat < $locations[$index]["min"]["lat"]) {
        echo $locations[$index]["min"]["lat"] . PHP_EOL;
        if ($index == $max - 1) {
            var_dump ($myLat);
            return -1;
        }
        return binarySearch($locations, $index, $max);
    } else {
        if ($myLat > $locations[$index]["min"]["lat"] && $myLat < $locations[$index - 1]["min"]["lat"]) {
            return $index; //$locations[$index]['name'];
        }
        for (; $index > 0; --$index) {
            if ($myLat > $locations[$index]["min"]["lat"] && $myLat < $locations[$index - 1]["min"]["lat"]) {
                echo "\n\n FOUND {$locations[$index]['name']} @$index\n\n";
                return $index; //$locations[$index]['name'];
            }
        }

        if ($myLat > $locations[$index]["min"]["lat"])
            return $index;

        return -1;
        // ;

    }
/*
    foreach($locations AS $name => $location) {


        if(!isset($location['Bounding Box'])) continue;



        $regionMin = $location['Bounding Box']['min'];

        $regionMax = $location['Bounding Box']['max'];


        //echo "checking $name (between $regionMin[lat]:$regionMin[long] and $regionMax[lat]:$regionMax[long] \n";


        if( $myLat >= $regionMin['lat'] && $myLat <= $regionMax['lat']
            && $myLong >= $regionMin['long'] && $myLong <= $regionMax['long'] ) {
            echo "Possible region: $name\n";
        } */



//    //if($name != 'Alma') continue;
//    echo $name . ": \n";
//    print_r($location['Bounding Box']);
}

function getRegionPoints($name) {

    global $myLat, $myLong;
    require_once 'Raycast.php';

    //$name = "Vancouver";

    $name = str_replace(" ","%20",$name);


    $coordinateFolder = "locations/{$name}/geometry";
    $coordinateFile = "$coordinateFolder/coordinates.json";
    $cachedFile = "./firebase-cached/$coordinateFile";

    if(!file_exists($cachedFile) || filemtime($cachedFile) < strtotime('-1 days')) {
        // cache file

        $url = "https://preventanyl.firebaseio.com/$coordinateFile";

        echo "downloading $url\n";
//    $ch = curl_init($url);
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_POST, 1);
//    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        $json = curl_exec($ch);
        curl_close($ch);


        $dir = "firebase-cached/$coordinateFolder";

        echo "caching to $dir";

        if (!is_dir($dir)) {
            // dir doesn't exist, make it

            mkdir($dir, 0777, true);
        }

        file_put_contents($cachedFile, $json);

    } else {
        $json = file_get_contents($cachedFile);
    }


    $points = json_decode($json, true);


    $isInside = Raycast::isInside(['lat'=>$myLat,'long'=>$myLong], $points);

    if($isInside) {
        echo "\n-----------------\nRaycast: inside $name!\n\n";
    } else {
        echo "Raycast: NOT inside $name!\n";
    }

    //$data = curl_exec($ch);

    //var_dump($data);

    //var_dump($jsonResponse);

}