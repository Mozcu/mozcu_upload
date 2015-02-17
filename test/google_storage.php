<?php
require '../vendor/autoload.php';

const CLIENT_ID = '461760934382-sv582dlpdj12tpmfou1rk4bb1ldp6fos.apps.googleusercontent.com';
const SERVICE_ACCOUNT_NAME = '461760934382-sv582dlpdj12tpmfou1rk4bb1ldp6fos@developer.gserviceaccount.com';
const KEY_FILE = '/home/mauro/keygoogleapi/f642cc312afd48fb55630352f119bde571caa941-privatekey.p12';

$client = new Google_Client();
$client->setApplicationName("Mozcu Upload Dev");

/*session_start();
if (isset($_SESSION['token'])) {
 $client->setAccessToken($_SESSION['token']);
}*/

// Load the key in PKCS 12 format (you need to download this from the
// Google API Console when the service account was created.
$key = file_get_contents(KEY_FILE);
$client->setAssertionCredentials(new Google_Auth_AssertionCredentials(
    SERVICE_ACCOUNT_NAME,
    array('https://www.googleapis.com/auth/devstorage.full_control'),
    $key)
);
$client->setScopes('https://www.googleapis.com/auth/devstorage.full_control');
$client->setClientId(CLIENT_ID);
$service = new Google_Service_Storage($client);


$objects = $service->objects;
$gso = new Google_Service_Storage_StorageObject();

$name = 'images/' . uniqid() . '.mp3';
$gso->setName($name);
$link = $gso->getMediaLink();
$songdata = file_get_contents('/home/mauro/Desktop/mp3 test/teamth.mp3');
$postbody = array('data' => $songdata, 'uploadType' => 'multipart', 'mimeType' => 'audio/mpeg');
//$imgdata = file_get_contents('/home/mauro/Pictures/PJLB.jpg');
//$postbody = array('data' => $imgdata, 'uploadType' => 'multipart', 'mimeType' => 'image/jpeg');

set_time_limit(0);
try {
    $result = $objects->insert('dev-static-mozcu', $gso, $postbody);
    echo '<pre>'; var_dump($result); echo '</pre>';
} catch(Exception $e) {
    echo $e->getMessage();
}