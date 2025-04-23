<?php
/**
 * Process callbacks
 */
require_once(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
require_once(dirname(__FILE__, 2) . '/classes/Callback.php');
 
use RocketFuel\Classes\Callback;

try { 
    $callback = new Callback(json_decode(file_get_contents('php://input'),true));
    echo $callback->getResponse();
} catch (Exception $e){
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}
