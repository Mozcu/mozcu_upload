<?php

set_include_path("lib/googleapi/src/" . PATH_SEPARATOR . get_include_path());
require_once 'Google/Client.php';
require_once 'Google/Service/Storage.php';

require_once 'lib/Mysql.php';
require_once 'lib/Queue.php';
require_once 'lib/Storage.php';
require_once 'lib/Album.php';

$mysql = new Mysql();
$queue = new Queue($mysql->getPDO());
$storage = new Storage();
$album = new Album($mysql->getPDO());

$failedAlbum = $queue->getFailedAlbum();
if(empty($failedAlbum)) {
    exit();
}

try {
    $queue->setFailedInProcess($failedAlbum['queue_id']);
    $songs = $queue->getSongs($failedAlbum['id']);
    $image = $queue->getImage($failedAlbum['id']);

    $uploadData = $storage->uploadAlbum($failedAlbum, $songs, $image);

    $album->updateAlbum($failedAlbum['id'], $uploadData);

    $queue->finnishFailedQueue($failedAlbum['queue_id']);
    $storage->deleteTempFiles($songs, $image);
    
    echo 'ok';
    
} catch(Exception $e) {
    echo $e->getMessage();
    $queue->resetFailed($failedAlbum['queue_id']);
}