<?php
function isInteger($input) {
    return ctype_digit(strval($input));
}
function truncate_filename($name, $max) {
	if (strlen($name) > $max) {
		if (strpos($name, '.') !== false) {
			$dot      = strrpos($name, '.');
			$name_ext = substr($name, $dot +1);
			$name     = substr($name, 0, $max -1 -strlen($name_ext)) . '.' . $name_ext;
		} else {
			$name     = substr($name, 0, $max);
		}
	}
	return $name;
}
function insert_nocache_headers() {
	header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
	header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
	header("Pragma: no-cache");
}
class G {
	public static $mimetype_extensions = array(
		'image/bmp' => array('bmp'),
		'image/gif' => array('gif'),
		'image/x-icon' => array('ico'),
		'image/jpeg' => array('jpe', 'jpeg', 'jpg'),
		'image/png' => array('png'),
		'image/tiff' => array('tif', 'tiff')
	);
}
function file_mimetype($filename) {
	$pos = strrpos($filename, '.');
	if ($pos === false) {
		return 'text/html';
	}
	$extension = substr($filename, $pos + 1);
	
	foreach(G::$mimetype_extensions as $mimetype => $extensions) {
		if (in_array($extension, $extensions)) {
			return $mimetype;
		}
	}
	
	return false;
}
function isset_and_default(&$array, $param, $default) {
	return isset($array[$param]) && $array[$param] !== '' ? $array[$param] : $default;
}
function file_upload_widget(DB &$db, $widgetID, &$FILE_REFERENCE, $name = NULL){
	if($FILE_REFERENCE['size'] <= MAX_FILE_SIZE_BYTES){
		$content = file_get_contents($FILE_REFERENCE['tmp_name']);
		
		// Innecesario borrarlo, php lo borra automaticamente.
		//unlink($FILE_REFERENCE['tmp_name']);
		
		if ($name === NULL) {
			$name = truncate_filename($FILE_REFERENCE['name'], FILENAME_MAX_LENGTH);
		}
		//$mimetype = $FILE_REFERENCE['type'];
		
		$db->upload_widget_file($widgetID, $name, $content);
	}
}
