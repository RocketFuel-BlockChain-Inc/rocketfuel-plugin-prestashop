<?php

namespace RocketFuel\Classes;

/**
 * Order class
 * @author Blessing Udor
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */

use Module;
use Configuration;
use Exception;

class RKFLOrder
{
    /**
     * Request data
     *
     * @var string
     */
    protected $request;
    private $module;

    public function __construct($request = null)
    {
        $this->module = Module::getInstanceByName(Plugin::getPluginInfo()['name']);
        $this->request = $request;
    }
    /**
     * Update order when payment has been confirmed
     * @param WP_REST_REQUEST $request_data
     * @return bool
     */
    public function updateOrder()
    {

        switch ($this->request['status']) {
            case '101':
                $status = (int)Configuration::get('PS_OS_PAYMENT');
                break;
            case '1':
                $status = (int)Configuration::get('PS_OS_PAYMENT'); //Fix partial payment
                break;
            case '-1':
            default:
                $status = (int)Configuration::get('PS_OS_CANCELED');
                break;
        }

        //now we have validated the order, we swap next
        $callback = new Callback();

        $swapResponse = $callback->swapOrderId(['temporaryOrderId' => $this->request['temp_order_id'], 'newOrderId' => $this->request['order_id']]);

        return json_encode(array(
            "swap_response" => $swapResponse,
        ));
    }
}
