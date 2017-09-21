<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../functions/generic.php';
// All the queries to the database are here. Change the database engine or the queries only have to be done here.
class DB {
	// Login data to access the database. change in the config file.
	private $host = MYSQL_HOST;
	private $user = MYSQL_USER;
	private $pass = MYSQL_PASSWORD;
	private $bd   = MYSQL_DATABASE;
	
	var $mysqli;
	
	private $opened_connection = false;
	
	// Auto inserted id number
	var $LAST_MYSQL_ID = '';
	
	function Open($host=null, $user=null, $pass=null, $bd=null) {
		if($this->d) $this->debug('Opening database');
		if ($host !== null)
			$this->host = $host;
		if ($user !== null)
			$this->user = $user;
		if ($pass !== null)
			$this->pass = $pass;
		if ($bd !== null)
			$this->bd = $bd;
			
		// Persistent connection:
		// http://www.php.net/manual/en/mysqli.persistconns.php
		// To open a persistent connection you must prepend p: to the hostname when connecting. 
		$this->mysqli = new mysqli('p:'.$this->host, $this->user, $this->pass, $this->bd);
		if ($this->mysqli->connect_errno) {
			if($this->d) $this->debug('Failed to connect to MySQL: (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error);
			$this->away = true;
			return false;
		}
		$this->away = false;
		$this->mysqli->set_charset('utf8');
		if($this->d) $this->debug('Database opened');
		return true;
	}
	
	// Make a SQL query. Returns false if there is an error, and throws an exception.
	// Queries are only done here. This way a connection can be opened if necessary
	// $this->LAST_MYSQL_ID stores the ID of the last insert query
	private function query($query) {
		if ($this->opened_connection === false) {
			if (!$this->Open()) {
				if($this->d) $this->debug('Can\'t open the database');
				return false;
			}
			$this->opened_connection = true;
		}
		
		$result = $this->mysqli->query($query, MYSQLI_USE_RESULT);
		if (strpos($query, 'INSERT') !== false) {
			$this->LAST_MYSQL_ID = $this->mysqli->insert_id;
		} else {
			$this->LAST_MYSQL_ID = null;
		}
		if ($result === false || $result === true) {
			if($this->d) $this->debug('<span class="info">query</span>: <span class="query">'.$query."</span>\r\n<span class='info'>result</span>: <b class=\"".($result?'ok">TRUE':'fail">FALSE ('.$this->mysqli->error.')')."</b>\r\n");
			return $result;
		}
		
		$resultArray = array();
		while ($rt = $result->fetch_array(MYSQLI_ASSOC)) $resultArray[] = $rt;
		if($this->d) $this->debug('<span class="info">query</span>: <span class="query">'.$query."</span>\r\n<span class='info'>result</span>: ".print_r($resultArray)."\r\n");
		return $resultArray;
	}
	
	// Variables
	private $away = false;
	
	function is_away() {
		return $this->away;
	}
	
	//debug mode
	var $debug_array = array();
	private $d = false;
	private $d_array = false;
	function debug_mode($bool) {
		$this->d = $bool;
		$this->debug('<span class="info">debug mode: ' . $bool.'</span>');
	}
	function debug_to_array($bool) {
		$this->d_array = $bool;
	}
	private function debug($txt) {
		if ($this->d) {
			if ($this->d_array) {
				$this->debug_array[] = $txt;
			} else {
				echo $txt . "\r\n";
			}
		}
	}
	
	
	
	// Not the best option
	function create_tables(&$content) {
		//remove comments
		$instructions = preg_replace('/--.*?[\r\n]/', '', $content);
		$instructions = preg_replace('|/\*.*?\*/|', '', $instructions);
		$instructions = str_replace("\n", '', $instructions);
		$instructions = str_replace("\r", '', $instructions);
		$instructions = explode(";", $instructions);
		foreach ($instructions as $instruction)
			if ($instruction !== '')
				$this->query($instruction);
	}
	
	
	
	function get_almacenes() {
		return $this->query("SELECT * FROM almacen");
	}
	
	function get_secciones($id_almacen) {
		$id_almacen = mysql_escape_mimic($id_almacen);
		return $this->query("SELECT id, nombre, descripcion FROM seccion_almacen WHERE id_almacen = {$id_almacen}");
	}
	
	function get_all_objetos() {
		return $this->query("SELECT * FROM objeto");
	}
	
	function get_objeto_secciones($id_objeto) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		return $this->query("SELECT id_seccion_almacen, cantidad FROM objeto_seccion_almacen WHERE id_objeto = {$id_objeto}");
	}
	
	function get_tags_objeto($id_objeto) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		return $this->query("SELECT tag.nombre FROM tag RIGHT JOIN tag_objeto ON tag.id = tag_objeto.id_tag where tag_objeto.id_objeto = {$id_objeto}");
	}
}
// Copy of mysql_real_escape_string to use it without an opened connection.
// http://es1.php.net/mysql_real_escape_string
function mysql_escape_mimic($inp) {
	if (is_array($inp))
		return array_map(__METHOD__, $inp);
	if (!empty($inp) && is_string($inp))
		return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
	return $inp;
}