<?php

namespace RocketFuel\Classes;

/**
 * Callback Class for andling webhook
 * 
 * @author Blessing Udor<reachme@blessingudor.com>
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */

use Customer;
use Address;
use Order;
use Cart;
use Currency;
use Language;
use Context;
use Module;
use Configuration;
use Exception;
use Validate;
use OrderHistory;
use Tools;
use PrestaShopLogger; // Add this at the top with other imports
use RocketFuel\Classes\Plugin;

require_once(dirname(__FILE__, 2) . '/classes/Curl.php');
require_once(dirname(__FILE__, 2) . '/classes/Plugin.php');

class Callback
{
    /**
     * Request data
     *
     * @var string
     */
    protected $request;

    /**
     * RocketFuel merchant ID
     *
     * @var string
     */
    protected $merchant_id, $environment, $public_key;

    public function __construct($request = null)
    {
        $this->merchant_id = Configuration::get('ROCKETFUEL_MERCHANT_ID');

        $this->environment = Configuration::get('ROCKETFUEL_ENVIRONMENT');
        $this->public_key = Configuration::get('ROCKETFUEL_MERCHANT_PUBLIC_KEY');
        $this->request = $request;
    }

    /**
     * Validate request Data
     *
     * @return array
     * @throws Exception
     */
    protected function validate()
    {
        if (!is_array($this->request)) {
            throw new Exception('request data invalid');
        }
        $body = isset($this->request['data']['data']) ? $this->request['data']['data'] : '';

        $signature = $this->request['signature'];

        $public_key = openssl_pkey_get_public(
            Tools::file_get_contents(dirname(__FILE__) . '/../key/.rf_public.key')
        );

        $verify = openssl_verify(
            $body,
            base64_decode($signature),
            $public_key,
            OPENSSL_ALGO_SHA256
        );

        if ($verify !== 1) {
            throw new Exception('signature not valid');
        }


        $data = json_decode($this->request['data']['data'], true);
        PrestaShopLogger::addLog('Request' . "\n" . json_encode($data), 1);

        $temp_order_delimiter = Plugin::getPluginInfo()['temp_order_delimiter'];
        // Check if 'offerId' contains '__rkfl_temp_order__'
        if (strpos($data['offerId'], $temp_order_delimiter) !== false) {

            PrestaShopLogger::addLog('Webhook Log' . "\n" . "Creating new order " . $temp_order_delimiter, 1);

            $temp_order_id = $data['offerId'];
            $cart_id = explode($temp_order_delimiter, $data['offerId'])[0];
            $data['offerId'] = $this->handlePlaceOrder($cart_id);
            // $data['offerId'] = explode($temp_order_delimiter, $data['offerId'])[0];
            //swap order id
            $swap = $this->swapOrderId([
                'temporaryOrderId' => $temp_order_id,
                'newOrderId' => $data['offerId']
            ]);
            PrestaShopLogger::addLog('Webhook Log' . "\n" . "Swap result " . json_encode($swap), 1);
        }

        $order = new Order($data['offerId']);

        if (!$order->reference) {
            throw new Exception('order not found');
        }

        if (((int)$order->getCurrentState() <> (int)Configuration::get('PS_OS_BANKWIRE'))) {
            throw new Exception('Order status has already been changed: ' . $order->id);
        }

        return $order;
    }
    private function manuallyCreateOrderFromCart(string $cart_id)
    {
        $cart = new Cart($cart_id);

        if (!Validate::isLoadedObject($cart)) {
            throw new Exception("Cart not found");
        }

        PrestaShopLogger::addLog('Webhook log' . "\n" . "manually creating", 1);

        $plugin_info = Plugin::getPluginInfo(); // Replace with the desired payment module name
        $payment_method = $plugin_info['name'];
        $order_status_id = Configuration::get('PS_OS_PAYMENT'); // Or another status
        $customer = new Customer($cart->id_customer);
        $address_delivery = new Address($cart->id_address_delivery);

        if (!Validate::isLoadedObject($customer) || !Validate::isLoadedObject($address_delivery)) {
            throw new Exception("Missing customer or address");
        }

        $context = Context::getContext();
        $context->cart = $cart;
        $context->customer = $customer;
        $context->currency = new Currency($cart->id_currency);
        $context->language = new Language($cart->id_lang);

        $payment_amount = $cart->getOrderTotal(true, Cart::BOTH);

        $module = Module::getInstanceByName($payment_method); // Or your chosen module

        if (!$module) {
            throw new Exception("Payment module not found");
        }

        PrestaShopLogger::addLog('Webhook log' . "\n" . $cart->id . '  ==== ' . $cart_id, 1);

        // Create the order
        $module->validateOrder(
            $cart->id,
            $order_status_id,
            $payment_amount,
            $payment_method,
            null, // message
            [], // extra vars
            $cart->id_currency,
            false, // don't use secure key here unless needed
            $customer->secure_key
        );

        PrestaShopLogger::addLog('Webhook log' . "\n This is the current Order" . $module->currentOrder, 1);


        return $module->currentOrder;
    }

    public function handlePlaceOrder($cart_id)
    {
        $order = new Order($cart_id);

        if ($order->reference) {
            return $cart_id;
        }

        return  $this->manuallyCreateOrderFromCart($cart_id);

        // $customer = new Customer($cart->id_customer);


        // die();

        // /**
        //  * Place the order
        //  */
        // $module->validateOrder(
        //     (int) $cart->id,
        //     //Configuration::get('PS_OS_PAYMENT'),
        //     Configuration::get('PS_OS_BANKWIRE'),

        //     (float) $cart->getOrderTotal(true, Cart::BOTH),
        //     $this->module->displayName,
        //     null,
        //     null,
        //     1,
        //     false,
        //     $customer->secure_key
        // );
    }
    /**
     * Make order paid
     *
     * @param $order
     */
    protected function makeOrderPaid($order)
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $history->id_order);
        $history->addWithemail();
        $history->save();
    }

    /**
     *  get serialized payload from order
     *
     * {
     * "cart":[{
     * "id":"38",
     * "name":"Beanie with Logo"
     * "price":0.05,
     * "quantity": 1
     * },{
     * "id":"22",
     * "name":"Belt"
     * "price":0.05,
     * "quantity": 2
     * }],
     * "amount":0.133,
     * "merchant_id":"b49e76e5-34a4-474e-9ab5-dad303f98891",
     * "order":"374"
     * }
     */
    public function getOrderPayload($order)
    {
        $out = [];

        foreach ($order->getProducts() as $product) {
            $out['cart'][] = [
                'id' => $product['product_id'],
                'name' => $product['product_name'],
                'price' => $product['total_price'],
                'quantity' => $product['product_quantity']
            ];
        };

        $out['amount'] = $order->total_paid;
        $out['merchant_id'] = $this->merchant_id;
        $out['order'] = $order->id;

        return $this->sortPayload($out);
    }


    public function getCartPayload($cart)
    {
        $out = [];
        if (!Validate::isLoadedObject($cart)) {
            throw new Exception("Cart not found");
        }

        $product_amount = 0;
        foreach ($cart->getProducts() as $product) {
            $out['cart'][] = [
                'id' => $product['id_product'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['cart_quantity']
            ];
            $product_amount += $product['price'] * $product['cart_quantity'];
        };
        // Add shipping details

        $total_amount = (float)$cart->getOrderTotal();

        if ($total_amount - $product_amount > 0) {
            $out['cart'][] = [
                'id' => 'shipping_carrier',
                'name' => 'Shipping & other fees',
                'price' =>  $total_amount - $product_amount,
                'quantity' => 1
            ];
        }

        $currency = new Currency(Context::getContext()->cookie->id_currency);
        $temp_order_delimiter = Plugin::getPluginInfo()['temp_order_delimiter'];
        $temp_order_id = $cart->id . $temp_order_delimiter . time();
        $data = [
            'cred' => $this->merchantCred(),
            'endpoint' => $this->getEndpoint($this->environment),
            'body' => [
                'amount' => (string)$cart->getOrderTotal(),
                'cart' => $out['cart'], //$cart,//cart
                'merchant_id' => $this->merchant_id,
                'currency' =>  $currency->iso_code,
                'order' => (string)$temp_order_id,
                'redirectUrl' => ''
            ]
        ];

        $out['amount'] = (string)$cart->getOrderTotal();
        $out['merchant_auth'] = $this->getEncrypted($this->merchant_id);
        $out['environment'] = $this->environment;
        $out['order'] = $temp_order_id;
        $uuid = $this->getUUID($data);

        if (!$uuid) {
            return array('success' => 'false', 'message' => 'Failed to place order');
        }
        $out['uuid'] = $uuid;
        $out['customer'] = json_encode(new Customer($cart->id_customer));

        return $this->sortPayload($out);
    }

    /**
     * custom serialize array
     *
     * @param $payload
     * @return array
     */
    protected function sortPayload($payload)
    {
        $sorted = [];
        if (is_object($payload)) {
            $payload = (array)$payload;
        }
        $keys = array_keys($payload);

        sort($keys);

        foreach ($keys as $key) {
            $sorted[$key] = is_array($payload[$key]) ? $this->sortPayload($payload[$key]) : (string)$payload[$key];
        }
        return $sorted;
    }

    /**
     * Get json response for RocketFuel service
     *
     * @return false|string
     */
    public function getResponse()
    {

        $order = $this->validate();


        // if ($verify) {
        $this->makeOrderPaid($order);
        //todo response
        echo json_encode(['status' => 'ok']);
        // } else {
        //     echo json_encode([
        //         'status' => 'error',
        //         'message' => 'signature not valid'
        //     ]);
        // }
    }

    protected function getEncrypted($to_crypt, $useMerchantPublicKey = false)
    {

        $out = '';

        if (!$useMerchantPublicKey) {
            $pub_key_path = dirname(__FILE__, 2) . '/key/.rf_public.key';
            if (!file_exists($pub_key_path)) {
                return false;
            }
            $cert = file_get_contents($pub_key_path);
        } else {
            $cert = $this->public_key;
        }

        $public_key = openssl_pkey_get_public($cert);

        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;

        $parts = str_split($to_crypt, $part_len);

        foreach ($parts as $part) {

            $encrypted_temp = '';

            openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);

            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }

    /**
     * Get UUID
     *
     * @param array $data 
     * - 'cred' => string, 
     * -   'endpoint' =>string,
     * -   'body' => arrray
     * -           'amount' =>string,
     * -            'cart' => array,
     * -            'merchant_id',
     * -            'currency','order',
     * -             redirectUrl
     * @return string
     */
    protected function getUUID($data)
    {
        $curl = new Curl();

        $paymentResponse = $curl->processDataToRkfl($data);

        PrestaShopLogger::addLog('checkout log' .  "\n response" . json_encode($paymentResponse), 1);

        if (!$paymentResponse) {
            return false;
        }

        $result = $paymentResponse;

        if (!isset($result->result) && !isset($result->result->url)) {
            return false;
        }
        $urlArr = explode('/', $result->result->url);

        return $urlArr[count($urlArr) - 1];
    }


    public function getEndpoint($environment)
    {
        $environmentData = [
            'prod' => 'https://app.rocketfuel.inc',
            'dev' => 'https://dev-app.rocketdemo.net/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
            'sandbox' => 'https://app-sandbox.rocketfuel.inc/api'
        ];

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://app.rocketfuel.inc/api';
    }

    public function merchantCred()
    {
        return [
            'email' => Configuration::get('ROCKETFUEL_MERCHANT_EMAIL'),
            'password' => Configuration::get('ROCKETFUEL_MERCHANT_PASSWORD')
        ];
    }

    public function swapOrderId(array $data)
    {
        $data = json_encode([
            'tempOrderId' => $data['temporaryOrderId'],
            'newOrderId' =>  $data['newOrderId']
        ]);

        $order_payload = $this->getEncrypted($data, true);

        $merchant_id = base64_encode($this->merchant_id);

        $body = json_encode(['merchantAuth' => $order_payload, 'merchantId' => $merchant_id]);

        $data = array(
            'endpoint' => $this->getEndpoint($this->environment),
            'body' => $body
        );
        $curl = new Curl();
        return $curl->swapOrderId($data);
    }
}
