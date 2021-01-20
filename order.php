<?php

/**
 * Order helper
 */
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/Callback.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/modules/rocketfuel/rocketfuel.php');

try {
    $callback = new Callback(Tools::getAllValues());
    echo json_encode(
        $callback->getOrderPayload(new Order(Tools::getValue('order_id')))
    );
} catch (Exception $e) {
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}

