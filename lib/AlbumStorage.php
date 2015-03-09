<?php

class AlbumStorage extends Storage {
    
     public function uploadAlbum($album, $songs, $image = null) {
        $date = new DateTime;
        $tstamp = $date->getTimestamp();
        
        $folder = $tstamp . $album['id'] . uniqid();
        
        $uploadData['songs'] = $this->uploadSongs($songs, $folder);
        if(!is_null($image)) {
            $uploadData['image'] = $this->uploadImage($image, $folder);
        }
        //$uploadData['zip'] = $this->uploadZip($album['name'], $songs, $folder);
        $uploadData['static_directory'] = $folder;
        
        return $uploadData;
    }
    
    public function uploadSongs($songs, $folder) {
        $return = array();
        
        foreach($songs as $song) {
            if(!is_null($song['url'])) {
                continue;
            }
            
            echo "Subiendo cancion: {$song['id']} - {$song['name']}\n";
            
            $songPath = $this->config->get('song_tmp_path') . "/" . $song['temporal_file_name'];
            //$trackNumber = sprintf("%02s", $song['trackNumber']);
            $sanitizedName = $this->sanitizeString($song['name']);
            //$name = "$folder/$trackNumber - $sanitizedName";
            $name = "$folder/$sanitizedName";
            
            $response = $this->upload($songPath, $name, 'mp3', 'audio/mpeg');
            if(is_null($response)) {
                throw new Exception('Error al intentar subir una cancion');
            }
            $return[] = array('id' => $response['id'], 'name'=>$response['name'], 'song_id' => $song['id'], 'url' => $response['mediaLink']);
        }
        
        return $return;
    }
    
    public function uploadImage($image, $folder) {
        echo "Subiendo imagen: {$image['id']}\n";
        
        $return['image_id'] = $image['id'];
        $return['presentations'] = array();
        
        $date = new \DateTime;
        $dir = $date->getTimestamp() . uniqid();
        if(!is_dir($this->config->get('image_tmp_path') . '/' . $dir)) {
            mkdir($this->config->get('image_tmp_path') . '/' . $dir);
        }
        
        $this->tmpImagickDir = $this->config->get('image_tmp_path') . '/' . $dir;
        
        foreach($image['presentations'] as $pres) {
            $tmpPath = $this->config->get('image_tmp_path') . '/' . $image['temporal_file_name'];
            $imagick = new Imagick($tmpPath);

            if(isset($pres['thumbnail']) && $pres['thumbnail']) {
                $imagick->cropthumbnailimage($pres['width'], $pres['height']);
            } else {
                $imagick->resizeImage($pres['width'], $pres['height'], null, 0.9, true);
            }
            
            $ext = pathinfo($tmpPath, PATHINFO_EXTENSION);
            $path = $this->config->get('image_tmp_path') . '/' . $dir . '/' . $pres['name'] . '.' . $ext;

            $imagick->writeimage($path);
            $response = $this->upload($path, $folder . '/' .$pres['name']);
            if(is_null($response)) {
                unlink($path);
                throw new Exception('Error al intentar subor una imagen');
            }
            $return['presentations'][] = array('id' => $response['id'], 'name'=>$response['name'], 'presentation_id' => $pres['id'], 'url' => $response['mediaLink']);
            
            unlink($path);
        }
        
        return $return;
        
    }
    
    public function deleteTempFiles($songs, $image = null) {
        echo "Borranodo archivos temporales\n";
        
        foreach($songs as $song) {
            if(!is_null($song['temporal_file_name'])) {
                $songPath = $this->config->get('song_tmp_path') . "/" . $song['temporal_file_name'];
                if(file_exists($songPath)) {
                    unlink($songPath);
                }
            }
        }
        
        if(!is_null($image)) {
            unlink($this->config->get('image_tmp_path') . "/" . $image['temporal_file_name']);
            rmdir($this->tmpImagickDir);
        }
    }
    
    public function deleteAlbumStaticData($album, $songs) {
        $staticDir = $album['staticDirectory'];
        $files = $this->getStaticFiles($staticDir);
        
        foreach($files->getItems() as $item) {
            $delete = true;
            if(in_array($item['contentType'], self::$audioTypes)) {
                foreach($songs as $song) {
                    if($song['static_file_name'] == $item['name']) {
                        $delete = false;
                    }
                }
            }
            
            if(in_array($item['contentType'], self::$imageTypes)) {
                $delete = false;
            }
            
            if($delete) {
                echo "Borrando {$item['name']}\n";
                $this->delete($item['name']);
            }
        }
    }
    
    /**
     * 
     * @param array $album
     * @return array
     */
    public function getSongs($album) {
        $files = $this->getStaticFiles($album['staticDirectory']);
        $return = array();
        foreach($files->getItems() as $file) {
            if(in_array($file['contentType'], self::$audioTypes)) {
                $return[] = $file;
            }
        }
        return $return;
    }
    
    /**
     * 
     * @param array $album
     * @return array
     */
    public function getImages($album) {
        $files = $this->getStaticFiles($album['staticDirectory']);
        $return = array();
        foreach($files->getItems() as $file) {
            if(in_array($file['contentType'], self::$imageTypes)) {
                $return[] = $file;
            }
        }
        return $return;
    }
}
