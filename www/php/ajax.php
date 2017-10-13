<?php
require_once 'lib/DB.php';


if (isset($_GET['action']) || isset($_POST['action'])) {
	$db = new DB();
	$db->open();
	if ($db->is_away()) {
		header("HTTP/1.1 503 Service Unavailable");
		exit;
	}
}

if (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'getinventario':
			$objetos = $db->get_objetos();
			foreach ($objetos as &$objeto) $objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);
			foreach ($db->get_almacenes() as &$almacen) $almacenes[$almacen["id"]] = &$almacen;
			foreach ($db->get_secciones() as &$seccion) $secciones[$seccion["id"]] = &$seccion;
			
			echo json_encode(array(
				"almacenes" => $almacenes,
				"secciones" => $secciones,
				"objetos" => $objetos
			), 1);
			break;
		case 'getfile':
			if (isset($_GET["id"])) {
				$file = $db->get_file($_GET["id"]);
				if (isset($file[0])) {
					$file = $file[0];
					$etag = base64_encode(md5($file['id']));
					
					header('Etag: ' . $etag);
					header('Cache-Control: max-age=120, public'); // 2 min. This is a problem when developing. Force check.
					
					if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
						header('HTTP/1.1 304 Not Modified');
						exit;
					} else {
						header('Content-type: ' . $file["mimetype"]);
						echo $file["bin"];
					}
				} else {
					echo "not found";
				}
			}
			break;
	}
} else {
	switch($_POST['action']) {
		case 'update-object-image':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(isset($_FILES["imagen"]) && $_FILES["imagen"]["name"] != "", "No se ha enviado una imagen")) {
				
				$file_index = "";
				$db->add_file($_FILES["imagen"]["type"], file_get_contents($_FILES["imagen"]["tmp_name"]), $file_index);
				$db->object_set_image($_POST["id-object"], $file_index);
				
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Imagen actualizada",
					"EVAL" => "updateImagen('".$_POST["id-object"]."', '$file_index')"
				));
			}
			break;
		case 'update-object-name':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(isset($_POST["nombre"]), "No se ha enviado un nombre. Por favor comunica este error a un encargado de la app")
				&& check(strlen($_POST["nombre"]) > 0, "El nombre es demasiado corto")) {
				
				$db->object_set_name($_POST["id-object"], $_POST["nombre"]);
				
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Nombre actualizado",
					"EVAL" => "updateNombre('".$_POST["id-object"]."', '".$_POST["nombre"]."')"
				));
			}
			break;
		case 'update-object-minimo':
		
			break;
	}
}

function check($check, $errorMsg) {
	if (!$check) {
		echo json_encode(array(
			"STATUS" => "ERROR",
			"MESSAGE" => $errorMsg
		));
		return false;
	}
	return true;
}
