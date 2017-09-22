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
			$objetos = $db->get_objetos();
			foreach ($objetos as &$objeto) {
				$objeto["tags"] = array_map(function ($d) {return $d["nombre"];}, $db->get_tags_objeto($objeto["id"]));
				$objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);
			}
			
			foreach ($db->get_almacenes() as &$almacen) {
				$almacenes[$almacen["id"]] = &$almacen;
			}
			
			foreach ($db->get_secciones() as &$seccion) {
				$secciones[$seccion["id"]] = &$seccion;
			}
			
			echo json_encode(array(
				"almacenes" => $almacenes,
				"secciones" => $secciones,
				"objetos" => $objetos
			), 1);
			break;
		case 'getimagen':
			
			break;
	}
}
