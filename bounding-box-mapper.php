<?php
/**
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-27
 * Time: 5:53 PM
 */


$timeStart = floatval(microtime());

// to run: > php city-mapper.php <lat> <long>
// NOTE: firebase stores them in backwards format but this script accepts in correct format. Input a lat / long from

$largerRegions = [];

$data = getCityBoundingBoxes();

foreach($data['locations'] AS $name => $location) {

    if(!isset($location['Bounding Box'])) continue;

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

function getCityBoundingBoxes() {
    $data = unserialize(file_get_contents("mapped.serialized"));

    //$data = apc_fetch('map');

    return $data;

}