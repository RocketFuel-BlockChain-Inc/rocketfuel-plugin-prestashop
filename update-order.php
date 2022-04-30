<?php
/**
 * @author Blessing Udor
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/RKFLOrder.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/modules/rocketfuel/rocketfuel.php');

try {

   
    $callback = new RKFLOrder(Tools::getAllValues());
    echo $callback->updateOrder();
// echo json_encode(file_get_contents('php://input'));
} catch (Exception $e){
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}
