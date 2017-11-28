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
			insert_nocache_headers();
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
		case 'getinventarioitem':
			insert_nocache_headers();
			if (check(isset($_GET["id"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")) {
				$objetos = $db->get_objeto($_GET["id"]);
				foreach ($objetos as &$objeto) $objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);
				
				echo json_encode($objetos, 1);	
			}
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
				&& check(count($db->get_objeto($_POST["id-object"])) === 1, "El objeto no existe")
				&& check(isset($_FILES["imagen"]) && $_FILES["imagen"]["name"] != "", "No se ha enviado una imagen")) {
				
				$file_index = "";
				if ($db->add_file($_FILES["imagen"]["type"], file_get_contents($_FILES["imagen"]["tmp_name"]), $file_index)
					&& $db->object_set_image($_POST["id-object"], $file_index)) {
					echo json_encode(array(
						"STATUS" => "OK",
						"MESSAGE" => "Imagen actualizada"
					));
				} else {
					echo json_encode(array(
						"STATUS" => "ERROR",
						"MESSAGE" => $db->mysqli->error
					));
				}
				
			}
			break;
		case 'update-object-name':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(count($db->get_objeto($_POST["id-object"])) === 1, "El objeto no existe")
				&& check(isset($_POST["nombre"]), "No se ha enviado un nombre. Por favor comunica este error a un encargado de la app")
				&& check(strlen($_POST["nombre"]) > 0, "El nombre es demasiado corto")) {
				
				if ($db->object_set_name($_POST["id-object"], $_POST["nombre"])) {				
					echo json_encode(array(
						"STATUS" => "OK",
						"MESSAGE" => "Nombre actualizado"
					));
				} else {
					echo json_encode(array(
						"STATUS" => "ERROR",
						"MESSAGE" => $db->mysqli->error
					));
				}
			}
			break;
		case 'update-object-minimo':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(count($db->get_objeto($_POST["id-object"])) === 1, "El objeto no existe")
				&& check(isset($_POST["minimo"]), "No se ha enviado una cantidad mínima. Por favor comunica este error a un encargado de la app")
				&& check(intval($_POST["minimo"]) >= 0, "El valor mínimo debe de ser un número mayor o igual que cero")) {
				
				if ($db->object_set_minimo($_POST["id-object"], $_POST["minimo"])) {
					echo json_encode(array(
						"STATUS" => "OK",
						"MESSAGE" => "Mínimo actualizado"
					));					
				} else {
					echo json_encode(array(
						"STATUS" => "ERROR",
						"MESSAGE" => $db->mysqli->error
					));
				}
			}
			break;
		case 'update-object-cantidades':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(count($db->get_objeto($_POST["id-object"])) === 1, "El objeto no existe")) {
				$cantidades = array();
				foreach ($_POST as $key => $value) {
					if (preg_match('@(seccion|cantidad)-([0-9]+)@', $key, $matches)) {
						$cantidades[$matches[2]][$matches[1]] = $value;
					}
				}
				$cantidadesFiltradas = array();
				foreach ($cantidades as $cantidad) {
					if (check(isset($cantidad["seccion"]) && isset($cantidad["cantidad"]), "Una de las entradas del almacen está incompleta")) {
						$cantidadesFiltradas[] = $cantidad;
					}
				}
				
				if ($db->object_set_cantidades($_POST["id-object"], $cantidadesFiltradas)) {				
					echo json_encode(array(
						"STATUS" => "OK",
						"MESSAGE" => "Cantidades actualizadas"
					));
				} else {
					echo json_encode(array(
						"STATUS" => "ERROR",
						"MESSAGE" => strpos($db->mysqli->error, "Duplicate entry") !== false ? "No se pueden repetir secciones de un almacen" : $db->mysqli->error
					));
				}
			}
			break;
		case 'update-object-tags':
			if (check(isset($_POST["id-object"]), "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app")
				&& check(count($db->get_objeto($_POST["id-object"])) === 1, "El objeto no existe")
				&& check(isset($_POST["tags"]), "No se ha enviado una lista de tags. Por favor comunica este error a un encargado de la app")) {
				
				if ($db->object_set_tags($_POST["id-object"], $_POST["tags"])) {				
					echo json_encode(array(
						"STATUS" => "OK",
						"MESSAGE" => "Tags actualizados"
					));
				} else {
					echo json_encode(array(
						"STATUS" => "ERROR",
						"MESSAGE" => $db->mysqli->error
					));
				}
			}
			break;
		case 'create-empty-object':
			if ($db->add_object()) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => $db->LAST_MYSQL_ID
				));
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
	}
}

function check($check, $errorMsg) {
	if (!$check) {
		echo json_encode(array(
			"STATUS" => "ERROR",
			"MESSAGE" => $errorMsg
		));
		exit;
	}
	return true;
}
