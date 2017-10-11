<?php
function isInteger($input) {
    return ctype_digit(strval($input));
}
function insert_nocache_headers() {
	header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
	header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
	header("Pragma: no-cache");
}
function isset_and_default(&$array, $param, $default) {
	return isset($array[$param]) && $array[$param] !== '' ? $array[$param] : $default;
}