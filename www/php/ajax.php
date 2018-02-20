<?php
require_once 'lib/DB.php';

$SAME_MSG = "Sin cambios";

if (isset($_GET['action']) || isset($_POST['action'])) {
	$db = new DB();
	$db->open();
	//$db->debug_mode(true);
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
			if (isset($_GET["id"])) {
				$objeto = $db->get_objeto($_GET["id"]);
				$objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);

				echo json_encode($objeto, 1);
			}
			break;
		case 'getfile':
			if (isset($_GET["id"])) {
				$file = $db->get_file($_GET["id"]);
				if (isset($file[0])) {
					$file = $file[0];
					$etag = base64_encode(md5($file['id']));

					header('Etag: ' . $etag);
					header('Cache-Control: max-age=120, public'); // 2 min. This is a problem when developing. Force checkOrExit.

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
		case 'getbusquedaspreparadas':
			insert_nocache_headers();
			$busquedas = $db->get_busquedaspreparadas();
			echo count($busquedas) == 1 ? $busquedas[0]["value"] : "[]";
			break;
		case 'gethistory':
			insert_nocache_headers();
			echo json_encode($db->get_historico(), 1);
			break;
	}
}
else {
	// Enviar siempre lo que había antes, y si coincide con lo que hay ahora, actualizar.
	// No vamos a usar relaciones con DELETE CASCADE o similar
	switch($_POST['action']) {
		case 'update-object-image':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			checkOrExit(null !== $db->get_objeto($_POST["id-object"]), "El objeto no existe");

			if (!(isset($_FILES["imagen"]) && $_FILES["imagen"]["name"] != "")) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			
			$file_index = "";
			if ($db->add_file($_FILES["imagen"]["type"], file_get_contents($_FILES["imagen"]["tmp_name"]), $file_index)
				&& $db->set_objeto_image($_POST["id-object"], $file_index)) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Imagen actualizada"
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}			
			break;
		case 'update-object-name':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			checkOrExit(null !== $db->get_objeto($_POST["id-object"]), "El objeto no existe");
			checkOrExit(isset($_POST["nombre"]), "No se ha enviado un nombre");
			checkOrExit(strlen($_POST["nombre"]) > 0, "El nombre es demasiado corto");

			if ($_POST["nombre"] == $db->get_objeto($_POST["id-object"])["nombre"]) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			
			if ($db->set_objeto_name($_POST["id-object"], $_POST["nombre"])) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Nombre actualizado"
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'update-object-minimo':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			checkOrExit(null !== $db->get_objeto($_POST["id-object"]), "El objeto no existe");
			checkOrExit(isset($_POST["minimo"]), "No se ha enviado una cantidad mínima");
			checkOrExit(intval($_POST["minimo"]) >= 0, "El valor mínimo debe de ser un número mayor o igual que cero");

			if ($_POST["minimo"] == $db->get_objeto($_POST["id-object"])["minimo"]) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			
			if ($db->set_objeto_minimo($_POST["id-object"], $_POST["minimo"])) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Mínimo actualizado"
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'update-object-cantidades':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			checkOrExit(null !== $db->get_objeto($_POST["id-object"]), "El objeto no existe");
			
			$cantidades = array();
			foreach ($_POST as $key => $value) {
				if (preg_match('@(id_seccion|cantidad)-([0-9]+)@', $key, $matches)) {
					$cantidades[$matches[2]][$matches[1]] = $value;
				}
			}
			$cantidadesFiltradas = array();
			foreach ($cantidades as $cantidad) {
				if (checkOrExit(isset($cantidad["id_seccion"]) && isset($cantidad["cantidad"]), "Una de las entradas del almacen está incompleta")) {
					$cantidadesFiltradas[] = $cantidad;
				}
			}

			if (json_encode($cantidadesFiltradas) == json_encode($db->get_objeto_secciones($_POST["id-object"]))) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			
			if ($db->set_objeto_cantidades($_POST["id-object"], $cantidadesFiltradas)) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Cantidades actualizadas",
					"FIRST" => json_encode($cantidadesFiltradas),
					"SECOND" => json_encode($db->get_objeto_secciones($_POST["id-object"])),
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => strpos($db->mysqli->error, "Duplicate entry") !== false ? "No se pueden repetir secciones de un almacen" : $db->mysqli->error
				));
			}
			break;
		case 'update-object-tags':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			checkOrExit(null !== $db->get_objeto($_POST["id-object"]), "El objeto no existe");
			checkOrExit(isset($_POST["tags"]), "No se ha enviado una lista de tags");

			if ($_POST["tags"] == $db->get_objeto($_POST["id-object"])["tags"]) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			
			if ($db->set_objeto_tags($_POST["id-object"], $_POST["tags"])) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Palabras clave actualizadas"
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'create-empty-object':
			if ($db->add_empty_objeto()) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => $db->LAST_MYSQL_ID
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'remove-object':
			checkOrExit(isset($_POST["id-object"]), "No se ha enviado la id del objeto");
			
			if ($db->remove_objeto($_POST["id-object"])) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Objeto borrado"
				));
				$db->add_history_spacing($_POST['action']);
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'update-almacenes-secciones':
			checkOrExit(isset($_POST["almacenes"]), "No se ha enviado el listado de almacenes");
			checkOrExit(isset($_POST["secciones"]), "No se ha enviado el listado de secciones");
			
			$new_alm = json_decode($_POST["almacenes"], 1);
			$new_sec = json_decode($_POST["secciones"], 1);

			// Obtener información de los objetos, almacenes y secciones actuales
			$old_obj = $db->get_objetos();
			foreach ($old_obj as &$objeto) $objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);
			foreach ($db->get_almacenes() as &$almacen) $old_alm[$almacen["id"]] = &$almacen;
			foreach ($db->get_secciones() as &$seccion) $old_sec[$seccion["id"]] = &$seccion;

			if (!isset($_POST["forzar"]) || $_POST["forzar"] !== "OK") {
				// Comprobar si alguno de los objetos usa una sección que ya no existe
				$obj_sec_borradas = array();
				foreach ($old_obj as &$obj) {
					foreach ($obj["secciones"] as &$obj_sec) {
						$encontrada = intval($obj_sec["cantidad"]) == 0;
						if (!$encontrada) {
							foreach ($new_sec as $sec_id => &$sec_content) {
								if ($obj_sec["id_seccion"] == $sec_id) {
									$encontrada = true;
									break;
								}
							}
						}

						if (!$encontrada) {
							if (!isset($obj_sec_borradas[$obj["id"]])) $obj_sec_borradas[$obj["id"]] = [];
							$obj_sec_borradas[$obj["id"]][] = $obj_sec;
						}
					}
				}

				if (count($obj_sec_borradas) > 0) {
					echo json_encode(array(
						"STATUS" => "ASK",
						"MESSAGE" => $obj_sec_borradas
					));
					exit;
				}
			}

			// Borrar secciones
			$new_sec_plain = array();
			foreach ($new_sec as $sec_id => &$_) $new_sec_plain[] = $sec_id;
			if ($db->remove_secciones_not_in($new_sec_plain)) {

				// Borrar almacenes
				$new_alm_plain = array();
				foreach ($new_alm as $alm_id => &$_) $new_alm_plain[] = $alm_id;
				if ($db->remove_almacenes_not_in($new_alm_plain)) {

					$error = false;

					// Actualizar nombre almacenes + insertar nuevos almacenes
					foreach ($new_alm as &$alm) {
						if (!$db->add_or_update_almacen($alm["id"], $alm["nombre"])) {
							$error = true;
							break;
						}
					}
					if ($error) {
						echo json_encode(array(
							"STATUS" => "FAIL",
							"MESSAGE" => "Ha surgido un fallo al actualizar e instertar los nuevos almacenes: " . $db->mysqli->error
						));
						exit;
					}

					// Actualizar nombre secciones + insertar nuevas secciones
					foreach ($new_sec as &$sec) {
						if (!$db->add_or_update_seccion($sec["id"], $sec["nombre"], $sec["id_almacen"])) {
							$error = true;
							break;
						}
					}
					if ($error) {
						echo json_encode(array(
							"STATUS" => "FAIL",
							"MESSAGE" => "Ha surgido un fallo al actualizar e instertar las nuevas secciones: " . $db->mysqli->error
						));
						exit;
					}
				} else {
					echo json_encode(array(
						"STATUS" => "FAIL",
						"MESSAGE" => "Ha surgido un fallo al borrar almacenes: " . $db->mysqli->error
					));
					exit;
				}
			} else {
				echo json_encode(array(
					"STATUS" => "FAIL",
					"MESSAGE" => "Ha surgido un fallo al borrar secciones: " . $db->mysqli->error
				));
				exit;
			}

			echo json_encode(array(
				"STATUS" => "OK",
				"MESSAGE" => "Actualizado con éxito."
			));
			$db->add_history_spacing($_POST['action']);
			break;
		case 'update-busquedaspreparadas':
			checkOrExit(isset($_POST["busquedaspreparadas"]), "No se ha enviado el nuevo listado de búsquedas preparadas");
			
			if ($_POST["busquedaspreparadas"] == $db->get_busquedaspreparadas()[0]["value"]) {
				echo json_encode(array(
					"STATUS" => "SAME",
					"MESSAGE" => $SAME_MSG
				));
				break;
			}
			if ($db->set_busquedaspreparadas($_POST["busquedaspreparadas"])) {
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Búsquedas preparadas actualizadas"
				));
			} else {
				echo json_encode(array(
					"STATUS" => "ERROR",
					"MESSAGE" => $db->mysqli->error
				));
			}
			break;
		case 'rollback-history':
			checkOrExit(isset($_POST["step"]), "No se ha enviado la id del historial que se debe deshacer");
			
			$history = $db->get_history_by_ids($_POST["step"]);
			if (count($history) > 0) {
				// Ejecutar deshacer
				foreach ($history as &$step) {
					var_dump($step["ACCION"]);
					switch ($step["ACCION"]) {
						case "DELETE ALMACEN":
							$db->add_or_update_almacen($step["I1"], $step["T1"]);
							break;
						case "INSERT ALMACEN":
							$db->remove_seccion($step["I1"]);
							break;
						case "UPDATE ALMACEN":
							$db->add_or_update_almacen($step["I1"], $step["T1"]);
							break;
						case "DELETE FILE":
							$file_index = 0;
							$db->add_file($step["T1"], $step["B1"], $file_index);
							break;
						case "INSERT FILE":
							$db->remove_file($step["I1"]);
							break;
						case "DELETE OBJETO":
							$db->add_or_update_objeto($step["I1"], $step["T1"], $step["I2"], $step["T2"], $step["T3"]);
							break;
						case "INSERT OBJETO":
							$db->remove_objeto($step["I1"]);
							break;
						case "UPDATE OBJETO":
							var_dump($db->add_or_update_objeto($step["I1"], $step["T1"], $step["I2"], $step["T3"], $step["T5"]));
							break;
						case "DELETE OBJETO_SECCION":
							$db->add_or_update_objeto_cantidades($step["I1"], $step["I2"], $step["I3"]);
							break;
						case "INSERT OBJETO_SECCION":
							$db->remove_objeto_cantidades($step["I1"], $step["I2"]);
							break;
						case "UPDATE OBJETO_SECCION":
							$db->add_or_update_objeto_cantidades($step["I1"], $step["I2"], $step["I3"]);
							break;
						case "DELETE SECCION":
							$db->add_or_update_seccion($step["I1"], $step["T1"], $step["I2"]);
							break;
						case "INSERT SECCION":
							$db->remove_seccion($step["I1"]);
							break;
						case "UPDATE SECCION":
							$db->add_or_update_seccion($step["I1"], $step["T1"], $step["I2"]);
							break;
					}
				}
				// Terminar con un spacing the tipo deshacer
				$db->add_history_spacing_id($_POST['action'], $_POST['step']);
				echo json_encode(array(
					"STATUS" => "OK",
					"MESSAGE" => "Se han deshecho los cambios con éxito."
				));
			}
			break;
	}
}

function checkOrExit($checkOrExit, $errorMsg) {
	if (!$checkOrExit) {
		echo json_encode(array(
			"STATUS" => "ERROR",
			"MESSAGE" => $errorMsg
		));
		exit;
	}
	return true;
}

function checkOrReload($checkOrReload) {
	if (!$checkOrReload) {
		echo json_encode(array(
			"STATUS" => "RELOAD",
			"MESSAGE" => "El valor que se esperaba modificar ha sido alterado por otra persona antes que usted. Para evitar sobreescribir los cambios de otras personas, por favor recarga la web."
		));
		exit;
	}
	return true;
}
