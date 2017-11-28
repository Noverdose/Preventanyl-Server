<?php
/**
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-27
 * Time: 5:53 PM
 */


// to run: > php city-mapper.php <lat> <long>
// NOTE: firebase stores them in backwards format but this script accepts in correct format. Input a lat / long from

$myLat = $argv[2];
$myLong = $argv[1];


$map = [];

$data = json_decode(file_get_contents("data.json"), true);

//print_r($data["locations"]["Alma"]);

foreach($data['locations'] AS $key => $location) {

    //echo $key . "\n";

    //if($key != 'Alma') continue;

    $minLong = 0;
    $minLat = 0;
    $maxLong = 0;
    $maxLat = 0;


    //if($key == 'Alma') echo "minMaxLat: $minLat:$maxLat, minMaxLong: $minLong:$maxLong\n";


    foreach($location['geometry']['coordinates'] AS $coordinate) {
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

    $data['locations'][$key]['Bounding Box'] = [    'min' => ['lat' => $minLat, 'long' => $minLong],
                                                    'max' => ['lat' => $maxLat, 'long' => $maxLong] ];

    //if($key != 'Alma') continue;
    //echo $key . ": ";
    //print_r($data['locations'][$key]['Bounding Box']);
}


$possibleRegions = [];

foreach($data['locations'] AS $name => $location) {

    $regionMin = $location['Bounding Box']['min'];
    $regionMax = $location['Bounding Box']['max'];


    //echo "checking $name (between $regionMin[lat]:$regionMin[long] and $regionMax[lat]:$regionMax[long] \n";


    if( $myLat >= $regionMin['lat'] && $myLat <= $regionMax['lat']
        && $myLong >= $regionMin['long'] && $myLong <= $regionMax['long'] ) {
        echo "Possible region: $name\n";
    }



//    //if($name != 'Alma') continue;
//    echo $name . ": \n";
//    print_r($location['Bounding Box']);
}



//echo "\n\nCount: ".count($data);