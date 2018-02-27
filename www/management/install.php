<pre>
Importante: Cambiar en la configuración de MySQL los parametros a:
max_allowed_packet = 16M
innodb_log_file_size = 160M

Rellenar los ajustes de acceso a la base de datos en el archivo php/config.php
</pre>

<form method="post">
PASSWORD: <input type="password" name="password"/><br/>
<input type="submit" value="Instalar"/>
</form>



<?php
require_once '../php/lib/DB.php';

if (!isset($_POST["password"])) exit;

$passwordhash = "6ee6909b4b6152fc0f3fba3df7b7d11b88eb3eef";
if ($passwordhash !== sha1($_POST["password"])) {
	echo "Contraseña equivocada";
	exit;
}

$db = new DB();
$db->open();
$db->debug_mode(true);
if ($db->is_away()) {
	echo "Fallo: DB no accesible";
	exit;
}

$sql = file_get_contents("db.sql");

//remove comments
$sql = preg_replace('/--.*?[\r\n]/', '', $sql);
$sql = preg_replace('|/\*.*?\*/|', '', $sql);
$sql = str_replace("\n", '', $sql);
$sql = str_replace("\r", '', $sql);
$sql = explode(";", $sql);

echo "<pre>";
var_dump($sql);

foreach ($sql as $instruction) {
	if ($instruction !== '') {
		echo "Executing: " . $instruction . "\n";
		var_dump($db->query($instruction));
		echo "\n\n";
	}
}
