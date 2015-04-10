<?php

require_once 'config/parameters.php';

$now = new DateTime();
$limit = 3600;

//Songs
$songsIterator = new FilesystemIterator($configParameters['song_tmp_path']);
foreach ($songsIterator as $fileinfo) {
    if (($now->getTimestamp() - $fileinfo->getMTime()) > 3600) {
        unlink($fileinfo->getPathname());
    }   
}

//Images
$imagesIterator = new FilesystemIterator($configParameters['image_tmp_path']);
foreach ($imagesIterator as $fileinfo) {
    if (($now->getTimestamp() - $fileinfo->getMTime()) > 3600) {
        unlink($fileinfo->getPathname());
    }   
}
