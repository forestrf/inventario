<?php
require_once 'lib/DB.php';


if (isset($_GET['action'])) {
	$db = new DB();
	$db->open();
	if ($db->is_away()) {
		header("HTTP/1.1 503 Service Unavailable");
		exit;
	}
	
	switch($_GET['action']) {
		case 'getinventario':
			$res = array();
			$almacenes = $db->get_almacenes();
			foreach ($almacenes as $almacen) {
				$almacen["contenido"] = array();
				$secciones = $db->get_secciones($almacen["id"]);
				foreach ($secciones as $seccion) {
					$seccion["contenido"] = array();
					$objetos = $db->get_objetos_seccion($seccion["id"]);
					foreach ($objetos as $objeto) {
						$objeto["tags"] = array_map(function ($d) {return $d["nombre"];}, $db->get_tags_objeto($objeto["id"]));
						$seccion["contenido"][] = $objeto;
					}
					$almacen["contenido"][] = $seccion;
				}
				$res[] = $almacen;
			}
			echo json_encode($res, 1);
			break;
		case 'getimagen':
			
			break;
	}
}
