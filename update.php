<?php

require_once 'config/bootstrap.php';

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
        $storage->delete($song);
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
            $storage->delete($imageToDelete['name']);
        }
        $uploadData['image'] = $storage->uploadImage($image, $pendingAlbum['staticDirectory']);
    }
    
    //Zip
    /*if($deleteImage) {
        $uploadData['zip'] = $storage->updateZip($pendingAlbum, $selectedSongs, $image);
    } else {
        $uploadData['zip'] = $storage->updateZip($pendingAlbum, $selectedSongs);
    }*/
    
    $album->updateAlbum($pendingAlbum['id'], $uploadData);
    
    $queue->finnishPendingQueue($pendingAlbum['queue_id']);
    $storage->deleteTempFiles($songs);
    
    echo "Disco actualizado correctamente\n";
    
} catch(Exception $e) {
    echo $e->getMessage() . "\n";
    $queue->moveToFailed($pendingAlbum['queue_id'], true);
}