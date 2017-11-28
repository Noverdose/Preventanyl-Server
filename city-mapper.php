<?php
/**
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-27
 * Time: 5:53 PM
 */

ini_set("memory_limit", "64M");
//
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq/Adapter');
//
//
//require_once('LINQ/Classes/PHPLinq.php');


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


        $boundingBoxes = getMapCached("data.json");
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $count = count($boundingBoxes);
        echo "sequential search from 0 to $count\n";

        $matchingBoxes = sequentialSearch($boundingBoxes, 0);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n\n";

        $rayCastMatches = [];
        foreach($matchingBoxes AS $box) {
            $inRegion = isInRegion($box['name']);
            if($inRegion) $rayCastMatches[] = $box;

        }

        if(empty($rayCastMatches)) {
            $regionBoxes = getMapCached("regions.json");

            $count = count($regionBoxes);

            foreach($regionBoxes AS $box) {var_dump($box);}

            echo "region sequential search from 0 to $count\n";
            $matchingBoxes = sequentialSearch($boundingBoxes, 0);

            echo "found ".count($matchingBoxes)." matching region boxes!\n";

            /*
             * array(3) {
  ["name"]=>
  string(16) "British Columbia"
  ["min"]=>
  array(2) {
    ["lat"]=>
    float(51.8735938)
    ["long"]=>
    float(-133.2260288)
  }
  ["max"]=>
  array(2) {
    ["lat"]=>
    float(54.2933915)
    ["long"]=>
    float(-130.8566278)
  }
}

             */


            $rayCastMatches = [];
            foreach($matchingBoxes AS $box) {
                $inRegion = isInRegion($box['name'], 'regions');
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


        $boundingBoxes = getMapCached("data.json");
//$locations = $data['locations'];
        //$keys = array_keys($boundingBoxes);

        //$possibleRegions = [];

        $startIndex = binarySearch($boundingBoxes, count($boundingBoxes) - 1);

        echo "(binary) sequential search from $startIndex to 0\n";
        $matchingBoxes = sequentialSearch($boundingBoxes, $startIndex);

        echo "found ".count($matchingBoxes)." matching bounding boxes!\n";

        $rayCastMatches = [];
        foreach($matchingBoxes AS $box) {
            $inRegion = isInRegion($box['name']);
            if($inRegion) $rayCastMatches[] = $box;
        }

        if(empty($rayCastMatches)) {
            $regionBoxes = getMapCached("regions.json");


            $startIndex = binarySearch($regionBoxes, count($regionBoxes) - 1);

            echo "region (binary) sequential search from $startIndex to 0\n";
            $matchingBoxes = sequentialSearch($boundingBoxes, $startIndex);

            echo "found ".count($matchingBoxes)." matching region boxes!\n";

            $rayCastMatches = [];
            foreach($matchingBoxes AS $box) {
                $inRegion = isInRegion($box['name'], 'regions');
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

    saveFile($file, $boundingBoxes);


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

function saveFile($fileName, $boundingBoxes) {

    $path_parts = pathinfo($fileName);
    $noExtension = $path_parts['filename'];


    //$loc = $data['locations'];

    usort($boundingBoxes, "lat_sort");

    //$data['locations'] = $loc;

    print_r($boundingBoxes);

    file_put_contents("$noExtension.serialized", serialize($boundingBoxes));

}

function getMapCached($fileName) {

    $path_parts = pathinfo($fileName);
    $noExtension = $path_parts['filename'];


    $data = unserialize(file_get_contents("$noExtension.serialized"));

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

    for($i=$startIndex; $i < $count; ++$i) {

        $location = $boundingBoxes[$i];

        $name = $location['name'];

        //if(!isset($location['Bounding Box'])) continue;


        $regionMin = $location['min'];
        $regionMax = $location['max'];


        //echo "checking $name ($myLat:$myLong) (between $regionMin[lat]:$regionMin[long] and $regionMax[lat]:$regionMax[long] \n";



        if( $myLat >= $regionMin['lat'] && $myLat <= $regionMax['lat']
            && $myLong >= $regionMin['long'] && $myLong <= $regionMax['long'] ) {
            $matching[] = $location;
            echo "Bounding Box: possible region found: $name @$i\n";
        }



//    //if($name != 'Alma') continue;
//    echo $name . ": \n";
//    print_r($location['Bounding Box']);
    }

    return $matching;

}




//$locations = $data["locations"];


function binarySearch($locations, $max) {

    //echo "binarySearch(\$locations, $min, $max) \n";

    global $myLat, $myLong;
    //global $locations;
    //global $keys;

    // $index = intval(floor($min + (($max - $min) / 2)));

    $low = 0;
    $high = $max; // numElems is the size of the array i.e arr.size()
    while ($low != $high) {
        $mid = intval(floor(($low + $high) / 2)); // Or a fancy way to avoid int overflow
//        var_dump ($mid);
//        var_dump ($low);
//        var_dump ($high);

        //echo "***\n";

        //echo "{$locations[$mid]['min']['lat']} < $myLat\n";


        //echo "***\n\n";
        if ($locations[$mid]["min"]["lat"] < $myLat) {
            /* This index, and everything below it, must not be the first element
             * greater than what we're looking for because this element is no greater
             * than the element.
             */
            $high = $mid;
        }
        else if ($locations[$mid]["min"]["lat"] == $myLat) {
            return $mid;
        }
        else {
            /* This element is at least as large as the element, so anything after it can't
             * be the first element that's at least as large.
             */
            $low = $mid + 1;
        }
    }

    return $low;
    /* Now, low and high both point to the element in question. */

}

function isInRegion($name, $controller='locations') {

    global $myLat, $myLong;
    require_once 'Raycast.php';

    //$name = "Vancouver";

    $name = str_replace(" ","%20",$name);


    $coordinateFolder = "$controller/{$name}/geometry";
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
        echo "\n-----------------\nRaycast: inside $name!\n";
    } else {
        echo "Raycast: NOT inside $name!\n";
    }

    return $isInside;

    //$data = curl_exec($ch);

    //var_dump($data);

    //var_dump($jsonResponse);

}