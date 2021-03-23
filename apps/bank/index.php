<?php
/**
 * Core bootloader
 *
 * @author Serhii Shkrabak
 */

/* RESULT STORAGE */
$RESULT = [
	'state' => 0,
	'data' => [],
	'debug' => []
];

/* ENVIRONMENT SETUP */
define('ROOT', $_SERVER['DOCUMENT_ROOT'] . '/'); // Unity entrypoint;

register_shutdown_function('shutdown', 'OK'); // Unity shutdown function

spl_autoload_register('load'); // Class autoloader

set_exception_handler('handler'); // Handle all errors in one function

/* HANDLERS */

/*
 * Class autoloader
 */
function load (String $class):void {
	$class = strtolower(str_replace('\\', '/', $class));
	$file = "$class.php";

	if (file_exists($file))
		include $file;
}

/*
 * Error logger
 */
function handler (Throwable $e):void {
	global $RESULT;
	$codes = ['RESOURCE_LOST' => 4, 'INTERNAL_ERROR' => 6];
	$message = $e -> getMessage();
	$RESULT['state'] = (isset($codes[$message])) ? $codes[$message] : 6;
	$RESULT[ 'debug' ][] = [
		'type' => get_class($e),
		'details' => $message,
		'file' => $e -> getFile(),
		'line' => $e -> getLine(),
		'trace' => $e -> getTrace()
	];
}

/*
 * Shutdown handler
 */
function shutdown():void {
	global $RESULT;
	$error = error_get_last();
	if ( ! $error ) {
		header("Content-Type: application/json");
		echo json_encode($GLOBALS['RESULT'], JSON_UNESCAPED_UNICODE);
	}
}

$CORE = new Controller\Main;
$data = $CORE->exec();

if ($data !== null) {
	$RESULT['data'] = $data;
}
else { // Error happens
	$RESULT['state'] = 2;
	$RESULT['errors'] = ['REQUEST_INCORRECT'];
	$RESULT['info'] = ['Entered path is not currently available']; // Дополнительное сообщение для пользователя
	unset($RESULT['data']);
	unset($RESULT['debug']);
}