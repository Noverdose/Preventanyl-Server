<?php
/**
 * RegionFinder: Web PHP script to find the user's current region.
 *
 * Created by PhpStorm.
 * User: brayden
 * Date: 2017-11-28
 * Time: 3:27 AM
 */

define("NOTIFICATION_API_KEY", "AAAAGgXbi_A:APA91bHh4M4aX5JZtojc6t_IOoPa6-fKXgKMA4ksVmdcVDdH3bFxy9fXv3YsKQK0mD8T0OIjm_vbscCCbtzhSxGrRrhP8SbsNFDgbzVnpnwUgCOhU_wn0jsFd4i-0kUxVQye-iedQtAH");

ini_set("memory_limit", "64M");

//
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq');
//set_include_path(get_include_path() . PATH_SEPARATOR . '/LINQ/Classes/PhpLinq/Adapter');
//
//
//require_once('LINQ/Classes/PHPLinq.php');

require_once 'mapping/MapTools.php';

$timeStart = floatval(microtime(true));

$myLat  = @$_REQUEST['lat'];
$myLong = @$_REQUEST['long'];

if(empty($myLat) || empty($myLong)) die("no location set!");



$boundingBoxes = MapTools::getMapCached("locations.json");

$startIndex = MapTools::binarySearch($boundingBoxes, count($boundingBoxes) - 1);

//echo "(binary) sequential search from $startIndex to 0\n";
$matchingBoxes = MapTools::sequentialSearch($boundingBoxes, $startIndex);

//echo "found ".count($matchingBoxes)." matching bounding boxes!\n";

$rayCastMatches = [];
foreach($matchingBoxes AS $box) {
    $inRegion = MapTools::isInRegion($box['name']);

    $name = str_replace(" ","%20",$box['name']);
    $box['url'] = $url = "https://preventanyl.firebaseio.com/locations/$name/geometry/coordinates.json";

    if($inRegion) $rayCastMatches[] = $box;
}

// find outer region
if(empty($rayCastMatches)) {
    $regionBoxes = MapTools::getMapCached("regions.json");

    $startIndex = MapTools::binarySearch($regionBoxes, count($regionBoxes) - 1);

    //echo "region (binary) sequential search from $startIndex to 0\n";
    $matchingBoxes = MapTools::sequentialSearch($boundingBoxes, $startIndex);

    //echo "found ".count($matchingBoxes)." matching region boxes!\n";

    $rayCastMatches = [];
    foreach($matchingBoxes AS $box) {
        $inRegion = MapTools::isInRegion($box['name'], 'regions');
        if($inRegion) {
            $name = str_replace(" ","%20",$box['name']);
            $box['url'] = $url = "https://preventanyl.firebaseio.com/regions/$name/geometry/coordinates.json";
            $rayCastMatches[] = $box;
        }
    }

    if(empty($rayCastMatches)) {
        echo "No Raycast matches... using bounding box matches\n";

        foreach($matchingBoxes AS $key => $box) {
            $name = str_replace(" ","%20",$box['name']);
            $matchingBoxes[$key]['url'] = $url = "https://preventanyl.firebaseio.com/locations/$name/geometry/coordinates.json";
        }

        $rayCastMatches = $matchingBoxes;
    }

}

$users = getUsers();

foreach($users AS $user) {

    $token = $user['id'];
    echo "notifying to $token\n";

    $place = empty($rayCastMatches[0]['name']) ? "nearby" : "in " . $rayCastMatches[0]['name'];

    notify2($token,
        "Someone needs help",
        "Overdose reported $place!",
        [   "latitude" => $myLat,
            "longitude" => $myLong,
            "region_url" => $url = $rayCastMatches[0]['url']]);

}




function notify($tokenArray, $title, $message)
{
    // API access key from Google API's Console
    if (!defined('API_ACCESS_KEY')) define( 'API_ACCESS_KEY', 'Insert here' );
    $tokenarray = array($tokenArray);
    // prep the bundle
    $msg = array
    (
        'title'     => $title,
        'message'     => $message,
        'MyKey1'       => 'MyData1',
        'MyKey2'       => 'MyData2',

    );
    $fields = array
    (
        'registration_ids'     => $tokenarray,
        'data'            => $msg
    );

    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
    return $result;
}

function notify2($token, $title, $body, $keyValues=[]) {
    $ch = curl_init("https://fcm.googleapis.com/fcm/send");

    //The device token.
    //$token = "DEVICE_TOKEN_HERE"; //token here

    //Title of the Notification.
    //$title = "Title Notification";

    //Body of the Notification.
    //$body = "This is the body show Notification";

    //Creating the notification array.
    $notification = array(
        'title' =>$title,
        'text' => $body,
        'sound' => 'default',
        'badge' => '1');


    $notification = array_merge($keyValues, $notification);


    //This array contains, the token and the notification. The 'to' attribute stores the token.
    $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');

    //Generating JSON encoded string form the above array.
    $json = json_encode($arrayToSend);
    //Setup headers:
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key=' . NOTIFICATION_API_KEY; // key here

    //Setup curl, add headers and post parameters.
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

    //Send the request
    $response = curl_exec($ch);

    //Close request
    curl_close($ch);
    return $response;

}


function getUsers() {
    $url = "https://preventanyl.firebaseio.com/userLocations.json";

    //if(DEBUG) echo "downloading $url\n";
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

    return json_decode($json, true);

}


//
//
//https://fcm.googleapis.com/fcm/send
//Content-Type:application/json
//Authorization:key=SERVER_API_KEY
//{
//    "condition": "'condition1' in topics && 'condition2' in topics",
//  "notification": {
//    "category": "notification_category",
//      "title_loc_key": "notification_title",
//      "body_loc_key": "notification_body",
//      "badge": 1
//  },
//  "data": {
//    "data_type": "notification_data_type",
//    "data_id": "111111",
//    "data_detail": "FOO",
//    "data_detail_body": "BAR"
//  }
//}

//    https://preventanyl.firebaseio.com/userLocations

//        $time = floatval(microtime(true));
//        echo "\n\n---------------------\n";
//        echo "binary search  total time elapsed: " . floor(($time - $timeStart) * 1000) . " ms" . PHP_EOL;
//        exit();

