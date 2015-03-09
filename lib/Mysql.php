<?php

class Mysql {

        private $pdo;

	public function __construct(Config $config) {
                $this->pdo = new PDO("mysql:dbname=" . $config->get('mysql_dbname') . ";host=" . $config->get('mysql_host'), 
                                      $config->get('mysql_user'), $config->get('mysql_passwd'));
	}

	public function getPDO() {
		return $this->pdo;
	}
        
        public function close() {
            $this->pdo = null;
        }
}