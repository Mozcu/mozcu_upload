<?php

// Composer
require_once 'config/bootstrap.php';

$pendingAlbum = $queue->getPendingAlbum();
if(empty($pendingAlbum)) {
    exit();
}

try {
    $queue->setPendingInProcess($pendingAlbum['queue_id']);
    $songs = $queue->getSongs($pendingAlbum['id']);
    $image = $queue->getImage($pendingAlbum['id']);

    echo "Procesando el disco: {$pendingAlbum['id']} - {$pendingAlbum['name']}\n";
    
    $uploadData = $storage->uploadAlbum($pendingAlbum, $songs, $image);
    
    $album->updateAlbum($pendingAlbum['id'], $uploadData);
    
    echo "Finalizando el proceso del disco: {$pendingAlbum['id']} - {$pendingAlbum['name']}\n";
    
    $queue->finnishPendingQueue($pendingAlbum['queue_id']);
    $storage->deleteTempFiles($songs, $image);
    
    echo "Disco subido correctamente\n";
    
} catch(Exception $e) {
    echo $e->getMessage();
    $queue->moveToFailed($pendingAlbum['queue_id']);
}