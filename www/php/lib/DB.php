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
	
	function Open($host = null, $user = null, $pass = null, $bd = null) {
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
	
	function add_history_spacing($action) {
		$action = escape($action);
		// Marcar como una transacción en el historial
		$this->query("INSERT INTO historico (ACCION, T1) VALUES ('SPACING', '{$action}')");
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
		if ($result === true && strpos($query, 'INSERT') !== false) {
			$this->LAST_MYSQL_ID = $this->mysqli->insert_id;
		} else {
			$this->LAST_MYSQL_ID = null;
		}
		
		if ($result === false || $result === true) {
			if($this->d) $this->debug('<span class="info">query</span>: <span class="query">' . $this->query_debug_str($query)
				."</span>\r\n<span class='info'>result</span>: <b class=\"" . ($result ? 'ok">TRUE' : 'fail">FALSE (' . $this->mysqli->error . ')')."</b>\r\n");
			return $result;
		}
		
		$resultArray = array();
		while ($rt = $result->fetch_array(MYSQLI_ASSOC)) $resultArray[] = $rt;
		if($this->d) $this->debug('<span class="info">query</span>: <span class="query">' . $this->query_debug_str($query) . "</span>\r\n<span class='info'>result</span>: "
			. print_r($resultArray) . "\r\n");
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
	private $d_queryLength = 256;
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
	private function query_debug_str(&$query) {
		return strlen($query) > $this->d_queryLength ? substr($query, 0, $this->d_queryLength) : $query;
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
	
	
	
	function get_busquedaspreparadas() {
		return $this->query("SELECT value FROM variables WHERE name = 'busquedas_preparadas';");
	}
	// $busquedas es un array que se recorrera con foreach cuyos elementos son otro array con indices nombre y busqueda
	function set_busquedaspreparadas($busquedas) {
		$busquedas = escape($busquedas);
		return $this->query("INSERT INTO variables (name, value) VALUES ('busquedas_preparadas', '{$busquedas}') ON DUPLICATE KEY UPDATE value = '{$busquedas}';");
	}
	
	function get_almacenes() {
		return $this->query("SELECT * FROM almacen;");
	}
	function get_secciones() {
		return $this->query("SELECT * FROM seccion;");
	}
	function get_objetos() {
		return $this->query("SELECT * FROM objeto;");
	}
	function get_objeto($id) {
		$id = escape($id);
		return $this->query("SELECT * FROM objeto WHERE id = {$id};");
	}
	function get_objeto_secciones($id_objeto) {
		$id_objeto = escape($id_objeto);
		return $this->query("SELECT id_seccion, cantidad FROM objeto_seccion WHERE id_objeto = {$id_objeto};");
	}
	function get_file($file_index) {
		$file_index = escape($file_index);
		return $this->query("SELECT * FROM file WHERE id = '{$file_index}';");
	}
	function get_historico() {
		return $this->query("SELECT * FROM historico;");
	}
	
	function add_file($mimetype, $blob, &$file_index) {
		$file_index = md5($blob);
		$mimetype = escape($mimetype);
		$blob = escape($blob);
		return $this->query("INSERT INTO file (id, mimetype, bin) VALUES ('{$file_index}', '{$mimetype}', '{$blob}');");
	}
	function add_empty_objeto() {
		return $this->query("INSERT INTO objeto () VALUES ();");
	}
	function remove_objeto($id_objeto) {
		$id_objeto = escape($id_objeto);
		return $this->query("DELETE FROM objeto_seccion WHERE id_objeto = '{$id_objeto}';")
			&& $this->query("DELETE FROM objeto WHERE id = '{$id_objeto}';");
	}
	function set_objeto_image($id_objeto, $id_file) {
		$id_objeto = escape($id_objeto);
		$id_file = escape($id_file);
		return $this->query("UPDATE objeto SET imagen = '{$id_file}' WHERE id = '{$id_objeto}' LIMIT 1;");
	}
	function set_objeto_name($id_objeto, $name) {
		$id_objeto = escape($id_objeto);
		$name = escape($name);
		return $this->query("UPDATE objeto SET nombre = '{$name}' WHERE id = '{$id_objeto}' LIMIT 1;");
	}
	function set_objeto_minimo($id_objeto, $minimo) {
		$id_objeto = escape($id_objeto);
		$minimo = escape($minimo);
		return $this->query("UPDATE objeto SET minimo = '{$minimo}' WHERE id = '{$id_objeto}' LIMIT 1;");
	}
	function set_objeto_tags($id_objeto, $tags) {
		$id_objeto = escape($id_objeto);
		$tags = escape($tags);
		return $this->query("UPDATE objeto SET tags = '{$tags}' WHERE id = '{$id_objeto}' LIMIT 1;");
	}
	// $cantidades es un array que se recorrera con foreach cuyos elementos son otro array con indices seccion y cantidad
	function set_objeto_cantidades($id_objeto, $cantidades) {
		$id_objeto = escape($id_objeto);
		if ($this->query("DELETE FROM objeto_seccion WHERE id_objeto = '{$id_objeto}' && id_seccion NOT IN (" . ToList($cantidades, function($v) { return $v["id_seccion"]; }) . ");")) {
			foreach ($cantidades as $cantidad) {
				$id_seccion = escape($cantidad["id_seccion"]);
				$cantidad = escape($cantidad["cantidad"]);
			if (!$this->query("INSERT INTO objeto_seccion (id_objeto, id_seccion, cantidad) VALUES ({$id_objeto}, {$id_seccion}, {$cantidad}) ON DUPLICATE KEY UPDATE cantidad = {$cantidad};")) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
	
	function remove_secciones_not_in($new_sections) {
		$seccionesList = ToList($new_sections);
		return $this->query("DELETE FROM objeto_seccion WHERE id_seccion NOT IN (" . $seccionesList . ");")
			&& $this->query("DELETE FROM seccion WHERE id NOT IN (" . $seccionesList . ");");
	}
	function remove_almacenes_not_in($new_almacenes) {
		$almacenesList = ToList($new_almacenes);
		return $this->query("DELETE FROM objeto_seccion WHERE id_seccion IN (SELECT id FROM seccion WHERE id_almacen NOT IN (" . $almacenesList . "));")
			&& $this->query("DELETE FROM seccion WHERE id_almacen NOT IN (" . $almacenesList . ");")
			&& $this->query("DELETE FROM almacen WHERE id NOT IN (" . $almacenesList . ");");
	}
	function add_or_update_almacen($id, $nombre) {
		$id = escape($id);
		$nombre = escape($nombre);
		return $this->query("INSERT INTO almacen (id, nombre) VALUES ('{$id}', '{$nombre}') ON DUPLICATE KEY UPDATE nombre = '{$nombre}';");
	}
	// Se puede actualizar una sección moviéndola a otro almacen, aunque la interfaz web todavía no lo soporta
	function add_or_update_seccion($id, $nombre, $id_almacen) {
		$id = escape($id);
		$nombre = escape($nombre);
		$id_almacen = escape($id_almacen);
		return $this->query("INSERT INTO seccion (id, nombre, id_almacen) VALUES ('{$id}', '{$nombre}', '{$id_almacen}') ON DUPLICATE KEY UPDATE nombre = '{$nombre}', id_almacen = '{$id_almacen}';");
	}
}

// Copy of mysql_real_escape_string to use it without an opened connection.
// http://es1.php.net/mysql_real_escape_string
function escape($inp) {
	if (is_array($inp))
		return array_map(__METHOD__, $inp);
	if (!empty($inp) && is_string($inp))
		return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
	return $inp;
}

function ToList($arr, callable $func = null) {
	if (is_null($func)) $func = function($v) { return $v; };
	return implode(", ", array_map(function($v) use(&$func) { return escape($func($v)); }, $arr));
}
