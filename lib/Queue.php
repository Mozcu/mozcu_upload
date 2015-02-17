<?php

class Queue {
    
    /**
     *
     * @var PDO
     */
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getPendingAlbum($toUpdate = false) {
        $query = "SELECT a.*, q.id as queue_id FROM album_upload_queue_pending q "
               . "INNER JOIN album a ON q.album_id = a.id "
               . "WHERE q.success = 0 AND q.in_process = 0 ";
        
        if($toUpdate) {
            $query .= "AND q.to_update = 1 ";
        } else {
            $query .= "AND q.to_update = 0 ";
        }
        
        $query .= "ORDER BY q.created_at ASC LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function setPendingInProcess($queueId) {
        $query = "UPDATE album_upload_queue_pending SET in_process = 1 WHERE id = $queueId";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }
    
    public function getSongs($albumId) {
        $query = "SELECT * FROM song WHERE album_id = $albumId ORDER BY id ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getImage($albumId) {
        $query = "SELECT i.id, i.temporal_file_name FROM album a "
               . "INNER JOIN image i ON a.id = i.album_id WHERE a.id = $albumId";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $image = $stmt->fetch();
        
        $query = "SELECT * FROM image_presentation WHERE image_id = {$image['id']}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $image['presentations'] = $stmt->fetchAll();
        return $image;
    }
    
    public function finnishPendingQueue($queueId) {
        $stmt = $this->pdo->prepare("UPDATE album_upload_queue_pending SET success = 1 WHERE id = $queueId");
        //$stmt = $this->pdo->prepare("DELETE FROM album_upload_queue_pending WHERE id = $queueId");
        $stmt->execute();
    }
    
    public function moveToFailed($queueId, $toUpdate = null) {
        $stmt = $this->pdo->prepare("SELECT * FROM album_upload_queue_pending WHERE id = $queueId");
        $stmt->execute();
        $p = $stmt->fetch();
        
        $stmt = $this->pdo->prepare("DELETE FROM album_upload_queue_pending WHERE id = $queueId");
        $stmt->execute();
        
        $query = "INSERT INTO album_upload_queue_failed (id, album_id, created_at, success, attempts, to_update) "
                . "VALUES({$p['id']}, {$p['album_id']}, '{$p['created_at']}', 0, 0, {$toUpdate})";
                
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }
    
    public function getFailedAlbum($toUpdate = false) {
        $query = "SELECT a.*, q.id as queue_id FROM album_upload_queue_failed q "
               . "INNER JOIN album a ON q.album_id = a.id "
               . "WHERE q.success = 0 AND q.attempts < 3 AND q.in_process = 0 ";
        
        if($toUpdate) {
            $query .= "AND q.to_update = 1 ";
        } else {
            $query .= "AND q.to_update = 0 ";
        }

        $query .= "ORDER BY q.created_at ASC LIMIT 1";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function setFailedInProcess($queueId) {
        $stmt = $this->pdo->prepare("SELECT attempts FROM album_upload_queue_failed WHERE id = $queueId");
        $stmt->execute();
        $attempts = (int)$stmt->fetchColumn();
        
        $attempts++;
        
        $query = "UPDATE album_upload_queue_failed SET in_process = 1, attempts = $attempts WHERE id = $queueId";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }
    
    public function finnishFailedQueue($queueId) {
        $stmt = $this->pdo->prepare("UPDATE album_upload_queue_failed SET success = 1 WHERE id = $queueId");
        $stmt->execute();
    }
    
    public function resetFailed($queueId) {
        $stmt = $this->pdo->prepare("UPDATE album_upload_queue_failed SET in_process = 0 WHERE id = $queueId");
        $stmt->execute();
    }
    
}
