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
			$objetos = $db->get_all_objetos();
			foreach ($objetos as &$objeto) {
				$objeto["tags"] = array_map(function ($d) {return $d["nombre"];}, $db->get_tags_objeto($objeto["id"]));
				foreach ($db->get_objeto_secciones($objeto["id"]) as &$seccion) {
					$objeto["secciones"][$seccion["id_seccion_almacen"]] = &$seccion;
				}
			}
			
			$almacenes = $db->get_almacenes();
			foreach ($almacenes as &$almacen) {
				$almacen["secciones"] = $db->get_secciones($almacen["id"]);
			}
			
			echo json_encode(array(
				"almacenes" => $almacenes,
				"objetos" => $objetos
			), 1);
			break;
		case 'getimagen':
			
			break;
	}
}
