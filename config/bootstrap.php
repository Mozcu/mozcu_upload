<?php
require 'vendor/autoload.php';

require_once 'config/parameters.php';
require_once 'lib/Config.php';
require_once 'lib/Mysql.php';
require_once 'lib/Queue.php';
require_once 'lib/Storage.php';
require_once 'lib/AlbumStorage.php';
require_once 'lib/Album.php';

$config = new Config($configParameters);
$mysql = new Mysql($config);
$queue = new Queue($mysql->getPDO());
$storage = new AlbumStorage($config);
$album = new Album($mysql->getPDO(), $storage);