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
$album = new Album($mysql->getPDO(), $storage);

$pendingAlbum = $queue->getPendingAlbum(true);
if(empty($pendingAlbum)) {
    exit();
}

try {
    echo "Actualizando el disco: {$pendingAlbum['id']} - {$pendingAlbum['name']}\n";
    $queue->setPendingInProcess($pendingAlbum['queue_id']);
    
    // Songs
    $songs = $queue->getSongs($pendingAlbum['id']);
    $selectedSongs = $album->selectSongs($pendingAlbum, $songs);
    
    foreach($selectedSongs['remove'] as $song) {
        echo "Eliminando cancion: $song\n";
        $storage->deleteFile($song);
    }
    $uploadData['songs'] = $storage->uploadSongs($selectedSongs['add'], $pendingAlbum['staticDirectory']);
    
    // Images
    $image = $queue->getImage($pendingAlbum['id']);
    $deleteImage = false;
    if(isset($image['temporal_file_name']) && !empty($image['temporal_file_name'])) {
        $deleteImage = true;
        $imagesToDelete = $storage->getImages($pendingAlbum);
        foreach($imagesToDelete as $imageToDelete) {
            echo "Eliminando imagen: {$imageToDelete['name']}\n";
            $storage->deleteFile($imageToDelete['name']);
        }
        $uploadData['image'] = $storage->uploadImage($image, $pendingAlbum['staticDirectory']);
    }
    
    //Zip
    if($deleteImage) {
        $uploadData['zip'] = $storage->updateZip($pendingAlbum, $selectedSongs, $image);
    } else {
        $uploadData['zip'] = $storage->updateZip($pendingAlbum, $selectedSongs);
    }
    
    
    $album->updateAlbum($pendingAlbum['id'], $uploadData);
    
    $queue->finnishPendingQueue($pendingAlbum['queue_id']);
    $storage->deleteTempFiles($songs);
    
    echo 'ok';
    
} catch(Exception $e) {
    echo $e->getMessage() . "\n";
    $queue->moveToFailed($pendingAlbum['queue_id'], true);
}