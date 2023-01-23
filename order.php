<?php

/**
 * Order helper
 */
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/Callback.php');

try {
    $callback = new Callback(Tools::getAllValues());
    echo json_encode(
        $callback->getCartPayload(Context::getContext()->cart)
    );
} catch (Exception $e) {
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}

