<?php

// Bootstraping
require_once 'config/parameters.php';
require_once 'lib/Config.php';
require_once 'lib/Mysql.php';
require_once 'lib/StringHelper.php';

$config = new Config($configParameters);
$mysql = new Mysql($config);

// Process
$queryBase = "SELECT a.id, a.name, u.username
          FROM album a
          INNER JOIN `profile` p ON a.profile_id = p.id
          INNER JOIN `user` u ON p.user_id = u.id ";
          

$stmt = $mysql->getPDO()->prepare($queryBase . "WHERE a.slug IS NULL OR a.slug = ''");
$stmt->execute();
$albums = $stmt->fetchAll();

foreach($albums as $album) {
    $slug = StringHelper::slugify($album['name']);
    
    $count = 0;
    $origSlug = $slug;
    do {
        $altSlug = ($count > 0) ? $origSlug . '_' . $count : $origSlug;
        $stmt = $mysql->getPDO()->prepare($queryBase . "WHERE a.slug = '$altSlug' AND u.username = '{$album['username']}'");
        $stmt->execute();
        $count++;
        $slug = $altSlug;
    } while($stmt->rowCount() > 0) ;
    
    $stmt = $mysql->getPDO()->prepare("UPDATE album SET `slug` = '$slug' WHERE id = {$album['id']}");
    $stmt->execute();
    echo $album['name'] . " => " . $slug . "\n";
}

$mysql->close();