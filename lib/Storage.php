<?php

class Storage {
    
    /**
     *
     * @var Config
     */
    protected $config;
    
    /**
     *
     * @var Google_Client 
     */
    protected $client;
    
    /**
     *
     * @var Google_Service_Storage
     */
    protected $service;
    
    /**
     *
     * @var string
     */
    protected $tmpImagickDir;
    
    protected static $audioTypes = array('audio/mpeg', 'audio/mpeg3', 'audio/x-mpeg-3', 'video/mpeg', 'video/x-mpeg', 'application/octet-stream');
    protected static $imageTypes = array('image/jpeg', 'image/pjpeg', 'image/png');
    
    public function __construct(Config $config) {
        $this->config = $config;
        $this->client = new Google_Client();
        $this->client->setApplicationName($this->config->get('app_name'));
        
        $key = file_get_contents($this->config->get('key_file'));
        $this->client->setAssertionCredentials(new Google_Auth_AssertionCredentials(
            $this->config->get('service_account_name'),
            array('https://www.googleapis.com/auth/devstorage.full_control'),
            $key)
        );
        $this->client->setScopes('https://www.googleapis.com/auth/devstorage.full_control');
        $this->client->setClientId($this->config->get('client_id'));
        $this->service = new Google_Service_Storage($this->client);
    }
    
    protected function upload($filePath, $fileName, $backupExt = null, $backupMimeType = null) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if(empty($ext)) {
            if(is_null($backupExt)) {
                throw new Exception('No se pudo obtener la extension del archivo');
            }
            $ext = $backupExt;
        }
        
        $mimeType = $this->getMimeType($filePath);
        if(empty($mimeType)) {
            if(is_null($backupMimeType)) {
                throw new Exception('No se pudo obtener el mimetype del archivo');
            }
            $mimeType = $backupMimeType;
        }
        
        $objects = $this->service->objects;
        $gso = new Google_Service_Storage_StorageObject();
        $fileName = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileName);
        $gso->setName($fileName . '.' . $ext);
        $fileData = file_get_contents($filePath);
        $postbody = array('data' => $fileData, 'uploadType' => 'multipart', 'mimeType' => $mimeType);
        try {
            return $objects->insert($this->config->get('bucket_name'), $gso, $postbody);
        } catch(Exception $e) {
            echo "Error: {$e->getMessage()} \n";
            return null;
        }
        
        
    }
    
    public function delete($path) {
        return $this->service->objects->delete($this->config->get('bucket_name'), $path);
    }
    
    protected function getStaticFiles($path) {
        return $this->service->objects->listObjects($this->config->get('bucket_name'), array('prefix' => $path));
    }
    
    /**
     * 
     * @param string $filePath
     * @return string
     */
    protected function getMimeType($filePath) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        return $mime;
    }
    
    /**
     * 
     * @param string $string
     * @return string
     */
    protected function sanitizeString($string) {
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;:[]().
        $string = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $string);
        // Remove any runs of periods
        $string = preg_replace("([\.]{2,})", '', $string);
        
        return $string;
    }
}
