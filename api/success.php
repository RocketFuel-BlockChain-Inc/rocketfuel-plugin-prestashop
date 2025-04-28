<?php

/**
 * Order helper
 */
require_once(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
require_once(dirname(__FILE__, 2) . '/classes/Callback.php');
require_once(dirname(__FILE__,2 ).  '/rocketfuel.php');

use RocketFuel\Classes\Callback;

try {
    $callback = new Callback(json_decode(file_get_contents('php://input'),true));
 
    echo json_encode(
        $callback->handleSuccess()
    );
} catch (Exception $e) {
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}
