<?php
/**
 * @author Blessing Udor
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */



require_once(dirname(__FILE__, 2) . '/classes/RKFLOrder.php');
 
require_once(dirname(__FILE__,2) .  '/rocketfuel.php');
require_once(dirname(__FILE__) . '/../../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../../init.php');
require_once(dirname(__FILE__, 2) . '/classes/Callback.php');

use RocketFuel\Classes\RKFLOrder;
try {

 
    $callback = new RKFLOrder(Tools::getAllValues());
    echo $callback->updateOrder();

} catch (Exception $e){
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}
