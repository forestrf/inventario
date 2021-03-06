<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../functions/generic.php';

define ("DB_OK", 1);
define ("DB_FAIL", 2);
define ("DB_VERSION", 3);

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
	var $AFFECTED_ROWS = 0;
	
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
	
	// Make a SQL query. Returns false if there is an error, and throws an exception.
	// Queries are only done here. This way a connection can be opened if necessary
	// $this->LAST_MYSQL_ID stores the ID of the last insert query
	public function query($query) {
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
			if (strpos($query, 'UPDATE') !== false) {
				$this->AFFECTED_ROWS = $this->mysqli->affected_rows;
			}
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
	
	// -------------------------------------------------------------
	
	function get_busquedas() {
		$busquedas = $this->query("SELECT value, version FROM variables WHERE name = 'busquedas_preparadas' LIMIT 1;");
		return count($busquedas) === 1 ? $busquedas[0] : false;
	}
	// $busquedas es un array que se recorrera con foreach cuyos elementos son otro array con indices nombre y busqueda
	function set_busquedas($busquedas, $version) {
		$busquedas = escape($busquedas);
		$version = escape($version);
		if ($this->query("INSERT INTO variables (name, value) VALUES ('busquedas_preparadas', '{$busquedas}');")) {
			return DB_OK;
		} else if ($this->query("UPDATE variables SET value = '{$busquedas}', version = version + 1 WHERE name = 'busquedas_preparadas' AND version = '{$version}';")) {
			return $this->AFFECTED_ROWS === 1 ? DB_OK : DB_VERSION;
		}
		return DB_FAIL;
	}
	
	function get_almacenes() {
		return $this->query("SELECT * FROM almacen;");
	}
	function get_objetos() {
		return $this->query("SELECT * FROM objeto;");
	}
	function get_objeto($id) {
		$id = escape($id);
		$response = $this->query("SELECT * FROM objeto WHERE id = '{$id}' LIMIT 1;");
		return $response !== false && count($response) === 1 ? $response[0] : false;
	}
	function get_objeto_almacenes($id_objeto) {
		$id_objeto = escape($id_objeto);
		return $this->query("SELECT id_almacen, cantidad FROM objeto_almacen WHERE id_objeto = '{$id_objeto}';");
	}
	function get_file($file_index) {
		$file_index = escape($file_index);
		return $this->query("SELECT * FROM file WHERE id = '{$file_index}';");
	}
	function remove_file($file_index) {
		$file_index = escape($file_index);
		return $this->query("DELETE FROM file WHERE id = '{$file_index}';");
	}
	function get_historico() {
		return $this->query("SELECT * FROM historico;");
	}
	
	function add_file($mimetype, $blob, &$file_index) {
		$file_index = md5($blob);
		$mimetype = escape($mimetype);
		$blob = escape($blob);
		$this->query("INSERT INTO file (id, mimetype, bin) VALUES ('{$file_index}', '{$mimetype}', '{$blob}');");
	}
	function add_empty_objeto() {
		return $this->query("INSERT INTO objeto () VALUES ();");
	}
	function add_or_update_objeto($id, $name, $minimo, $id_file, $tags) {
		$id = escape($id);
		$name = escape($name);
		$minimo = escape($minimo);
		$id_file = escape($id_file);
		$tags = escape($tags);
		return $this->query("INSERT INTO objeto (id, nombre, minimo, imagen, tags) VALUES ('{$id}', '{$name}', '{$minimo}', '{$id_file}', '{$tags}') ON DUPLICATE KEY UPDATE nombre = '{$name}', minimo = '{$minimo}', imagen = '{$id_file}', tags = '{$tags}';");
	}
	function remove_objeto($id_objeto) {
		$id_objeto = escape($id_objeto);
		return $this->query("DELETE FROM objeto_almacen WHERE id_objeto = '{$id_objeto}';")
			&& $this->query("DELETE FROM objeto WHERE id = '{$id_objeto}';");
	}
	function set_objeto_image($id_objeto, $id_file, $version) {
		$id_objeto = escape($id_objeto);
		$id_file = escape($id_file);
		$version = escape($version);
		if ($this->query("UPDATE objeto SET imagen = '{$id_file}', version = version + 1 WHERE id = '{$id_objeto}' AND version = '{$version}' LIMIT 1;")) {
			return $this->AFFECTED_ROWS === 1 ? DB_OK : DB_VERSION;
		}
		return DB_FAIL;
	}
	function set_objeto_name($id_objeto, $name, $version) {
		$id_objeto = escape($id_objeto);
		$name = escape($name);
		$version = escape($version);
		if ($this->query("UPDATE objeto SET nombre = '{$name}', version = version + 1 WHERE id = '{$id_objeto}' AND version = '{$version}' LIMIT 1;")) {
			return $this->AFFECTED_ROWS === 1 ? DB_OK : DB_VERSION;
		}
		return DB_FAIL;
	}
	function set_objeto_minimo($id_objeto, $minimo, $version) {
		$id_objeto = escape($id_objeto);
		$minimo = escape($minimo);
		$version = escape($version);
		if ($this->query("UPDATE objeto SET minimo = '{$minimo}', version = version + 1 WHERE id = '{$id_objeto}' AND version = '{$version}' LIMIT 1;")) {
			return $this->AFFECTED_ROWS === 1 ? DB_OK : DB_VERSION;
		}
		return DB_FAIL;
	}
	function set_objeto_tags($id_objeto, $tags, $version) {
		$id_objeto = escape($id_objeto);
		$tags = escape($tags);
		$version = escape($version);
		if ($this->query("UPDATE objeto SET tags = '{$tags}', version = version + 1 WHERE id = '{$id_objeto}' AND version = '{$version}' LIMIT 1;")) {
			return $this->AFFECTED_ROWS === 1 ? DB_OK : DB_VERSION;
		}
		return DB_FAIL;
	}
	// $cantidades es un array que se recorrera con foreach cuyos elementos son otro array con indices almacen y cantidad
	function set_objeto_cantidades($id_objeto, $cantidades, $version) {
		$id_objeto = escape($id_objeto);
		$version = escape($version);
		
		if ($this->query("UPDATE objeto SET version = version + 1 WHERE id = '{$id_objeto}' AND version = '{$version}' LIMIT 1;")) {
			if ($this->AFFECTED_ROWS !== 1) {
				return DB_VERSION;
			}
		} else {
			return DB_FAIL;
		}

		$sql = 0 === count($cantidades) ?
			"DELETE FROM objeto_almacen WHERE id_objeto = '{$id_objeto}';" :
			"DELETE FROM objeto_almacen WHERE id_objeto = '{$id_objeto}' AND id_almacen NOT IN (" . ToList($cantidades, function($v) { return $v["id_almacen"]; }) . ");";
		if ($this->query($sql)) {
			foreach ($cantidades as $cantidad) {
				$id_almacen = escape($cantidad["id_almacen"]);
				$cantidad = escape($cantidad["cantidad"]);

				if ($this->query("INSERT INTO objeto_almacen (id_objeto, id_almacen, cantidad) VALUES ('{$id_objeto}', '{$id_almacen}', '{$cantidad}');")) {
					// Perfect
				} else if ($this->query("UPDATE objeto_almacen SET cantidad = '{$cantidad}' WHERE id_objeto = '{$id_objeto}' AND id_almacen = '{$id_almacen}';")) {
					if ($this->AFFECTED_ROWS !== 1 && 1 !== count($this->query("SELECT * FROM objeto_almacen WHERE id_objeto = '{$id_objeto}' AND id_almacen = '{$id_almacen}';")))
						return DB_VERSION;
				} else {
					return DB_FAIL;
				}
			}
			return DB_OK;
		}
		return DB_FAIL;
	}
	function add_or_update_objeto_cantidades($id_objeto, $id_almacen, $cantidad) {
		$id_objeto = escape($id_objeto);
		$id_almacen = escape($id_almacen);
		$cantidad = escape($cantidad);
		return $this->query("INSERT INTO objeto_almacen (id_objeto, id_almacen, cantidad) VALUES ('{$id_objeto}', '{$id_almacen}', '{$cantidad}') ON DUPLICATE KEY UPDATE cantidad = '{$cantidad}';");
	}
	function remove_objeto_cantidades($id_objeto, $id_almacen) {
		$id_objeto = escape($id_objeto);
		$id_almacen = escape($id_almacen);
		return $this->query("DELETE FROM objeto_almacen WHERE id_objeto = '{$id_objeto}' AND id_almacen = '{$id_almacen}';");
	}
	
	function remove_almacenes_not_in($new_almacenes) {
		$almacenesList = ToList($new_almacenes);
		if (count($new_almacenes) > 0) {
			return $this->query("DELETE FROM objeto_almacen WHERE id_almacen NOT IN ({$almacenesList});")
				&& $this->query("DELETE FROM almacen WHERE id NOT IN ({$almacenesList});");
		} else {
			return $this->query("DELETE FROM objeto_almacen;")
				&& $this->query("DELETE FROM almacen;");
		}
	}
	function remove_seccion($id_seccion) {
		$id_seccion = escape($id_seccion);
		return $this->query("DELETE FROM objeto_seccion WHERE id_seccion = '{$id_seccion}';")
			&& $this->query("DELETE FROM seccion WHERE id = '{$id_seccion}';");
	}
	function remove_almacen($id_almacen) {
		$id_almacen = escape($id_almacen);
		return $this->query("DELETE FROM objeto_seccion WHERE id_seccion IN (SELECT id FROM seccion WHERE id_almacen = '{$id_almacen}');")
			&& $this->query("DELETE FROM seccion WHERE id_almacen = '{$id_almacen}';")
			&& $this->query("DELETE FROM almacen WHERE id = '{$id_almacen}';");
	}
	// Se puede actualizar un almacen moviéndolo dentro de otro almacen
	function add_or_update_almacen($id, $nombre, $padre) {
		$id = escape($id);
		$nombre = escape($nombre);
		$padre = NULL !== $padre ? "'" . escape($padre). "'" : "NULL";
		return $this->query("INSERT INTO almacen (id, nombre, padre) VALUES ('{$id}', '{$nombre}', {$padre}) ON DUPLICATE KEY UPDATE nombre = '{$nombre}', padre = {$padre};");
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
