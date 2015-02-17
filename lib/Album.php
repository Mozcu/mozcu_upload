<?php

class Album {
    
    /**
     *
     * @var PDO
     */
    private $pdo;
    
    /**
     *
     * @var Storage
     */
    private $storage;
    
    public function __construct($pdo, $storage = null) {
        $this->pdo = $pdo;
        $this->storage = $storage;
    }
    
    public function updateAlbum($albumId, $data) {
        $this->updateSongs($data['songs']);
        if(isset($data['image'])) {
            $this->updateImage($albumId, $data['image']);
        }
        
        $query = "UPDATE album SET is_active = 1, zipUrl = :zipUrl, static_zip_file_name = :zipName, ";
        if(isset($data['static_directory'])) {
            $query .= "staticDirectory = :dirName";
        }
        $query .= " WHERE id = :albumId";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam('zipUrl', $data['zip']['url'], PDO::PARAM_STR);
        $stmt->bindParam('zipName', $data['zip']['name'], PDO::PARAM_STR);
        $stmt->bindParam('albumId', $albumId, PDO::PARAM_INT);
        if(isset($data['static_directory'])) {
            $stmt->bindParam('dirName', $data['static_directory'], PDO::PARAM_STR);
        }
        $stmt->execute();
    }
    
    private function updateSongs($songs) {
        foreach($songs as $song) {
            //var_dump($song);
            $query = "UPDATE song SET url = :songUrl, static_file_name = :songName WHERE id = :songId";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam('songUrl', $song['url'], PDO::PARAM_STR);
            $stmt->bindParam('songName', $song['name'], PDO::PARAM_STR);
            $stmt->bindParam('songId', $song['song_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    private function updateImage($albumId, $image) {
        foreach($image['presentations'] as $pres) {
            $query = "UPDATE image_presentation SET url = :presUrl, static_file_name = :presName WHERE id = :presId";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam('presUrl', $pres['url'], PDO::PARAM_STR);
            $stmt->bindParam('presName', $pres['name'], PDO::PARAM_STR);
            $stmt->bindParam('presId', $pres['presentation_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $query = "UPDATE image SET temporal_file_name = NULL where album_id = :albumId";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam('albumId', $albumId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    /**
     * 
     * @param array $album
     * @param array $songs
     * @return array
     */
    public function selectSongs($album, $songs) {
        $selected['add'] = array();
        $songFileNames = array();
        foreach($songs as $song) {
            if(!empty($song['static_file_name'])) {
                $songFileNames[] = $song['static_file_name'];
            }
            if(empty($song['static_file_name']) && !empty($song['temporal_file_name'])) {
                $selected['add'][] = $song;
            }
        }
        
        $oldSongs = $this->storage->getSongs($album);
        $selected['remove'] = array();
        foreach($oldSongs as $song) {
            if(!in_array($song['name'], $songFileNames)) {
                $selected['remove'][] = $song['name'];
            }
        }
        
        return $selected;
    }
    
}
