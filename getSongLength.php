<?php

// Bootstraping
require 'vendor/autoload.php';
require_once 'config/parameters.php';
require_once 'lib/Config.php';
require_once 'lib/Mysql.php';

$config = new Config($configParameters);
$mysql = new Mysql($config);

// Process
$stmt = $mysql->getPDO()->prepare("SELECT id, url FROM song WHERE `length` IS NULL OR `length` = '' LIMIT 100");
$stmt->execute();
$songs = $stmt->fetchAll();

$getId3 = new GetId3\GetId3Core();
foreach($songs as $song) {
    file_put_contents('song.mp3', file_get_contents($song['url']));
    
    $audio = $getId3
        ->setOptionMD5Data(true)
        ->setOptionMD5DataSource(true)
        ->setEncoding('UTF-8')
        ->analyze('song.mp3');

    if(isset($audio['playtime_seconds'])) {
        $length = gmdate('i:s', $audio['playtime_seconds']);
        echo $song['id'] . ": " . $length . "\n";
        $stmt = $mysql->getPDO()->prepare("UPDATE song SET `length` = '$length' WHERE id = {$song['id']}");
        $stmt->execute();
    }
    unlink('song.mp3');
}

$mysql->close();