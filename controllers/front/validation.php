<?php

/**
 * RocketFuel - A Payment Module for PrestaShop 1.7
 *
 * Order Validation Controller
 *
 */
require_once(dirname(__FILE__, 3) . '/classes/Curl.php');

class RocketfuelValidationModuleFrontController extends ModuleFrontController
{
    protected $environment;
    public function postProcess()
    {

        /**
         * Get current cart object from session
         */
        $cart = $this->context->cart;



        $authorized = false;

        /**
         * Verify if this module is enabled and if the cart has
         * a valid customer, delivery address and invoice address
         */
        if (
            !$this->module->active ||
            $cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Verify if this payment module is authorized
         */
        foreach (Module::getPaymentModules() as $module) {

            if ($module['name'] == 'rocketfuel') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);


        /**
         * Check if this is a valid customer account
         */
        if (!Validate::isLoadedObject($customer)) {

            Tools::redirect('index.php?controller=order&step=1');

        }

        /**
         * Place the order
         */
        $this->module->validateOrder(
            (int) $this->context->cart->id,
            //Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_BANKWIRE'),

            (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            null,
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        $result =  $this->processPayment($this->module->currentOrder, $cart, $customer);

        file_put_contents(__DIR__ . '/log.json', "\n" . 'Final proces charge result : ' . "\n" . json_encode($result) . "\n", FILE_APPEND);

        /**
         * Redirect the customer to the order confirmation page
         */
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key . $result['redirect']);
    }
    /**
     * Parse cart items and prepare for order
     * @param array $items 
     * @return array
     */
    public function sortCart($items)
    {
        $data = array();
        try {
            foreach ($items as $item) {
                $data[] = array(
                    'name' => $item['name'],
                    'id' => (string)$item['id_product_attribute'],
                    'price' => $item['total'],
                    'quantity' => (string)$item['cart_quantity']
                );
            }
        } catch (\Throwable $th) {

            //silently ignore
        }


        return $data;
    }
    /**
     * Process payment and redirect user to payment page
     * @param int $orderId
     * @return false|array
     */
    public function processPayment($orderId, $cartObj)
    {
        $this->environment = Configuration::get('ROCKETFUEL_ENVIRONMENT');

        $order = new Order($orderId);

        $cart = $this->sortCart($cartObj->getProducts(true));

        $userData = base64_encode(json_encode(array(
            'first_name' => '$order->get_billing_first_name()',
            'last_name' => '$order->get_billing_last_name()',
            'email' => '$order->get_billing_email()',
            'merchant_auth' =>     '$this->merchant_auth()'
        )));

        $merchantCred = array(
            'email' => Configuration::get('ROCKETFUEL_MERCHANT_EMAIL'),
            'password' => Configuration::get('ROCKETFUEL_MERCHANT_PASSWORD')
        );

        $data = array(
            'cred' => $merchantCred,
            'endpoint' => $this->getEndpoint($this->environment),
            'body' => array(
                'amount' => $order->getOrdersTotalPaid(),
                'cart' => $cart,
                'merchant_id' => Configuration::get('ROCKETFUEL_MERCHANT_ID'),
                'currency' => "USD",
                'order' => (string) $orderId,
                'redirectUrl' => ''
            )
        );


        $curl = new Curl();

        $paymentResponse = $curl->processPayment($data);

        unset($curl);

        if (!$paymentResponse) {
            return;
        }

        file_put_contents(__DIR__ . '/log.json', "\n" . 'Response from Process in validation : ' . "\n" . json_encode($paymentResponse) . "\n", FILE_APPEND);

        $result = $paymentResponse;

        if (!isset($result->result) && !isset($result->result->url)) {
            // wc_add_notice(__('Failed to place order', 'rocketfuel-payment-gateway'), 'error');
            return array('succcess' => 'false');

        }
        $urlArr = explode('/', $result->result->url);

        $uuid = $urlArr[count($urlArr) - 1];

        $buildUrl = "&uuid=" . $uuid;

        if ($this->environment !== 'prod') {

            $buildUrl .= '&env=' . $this->environment;

        }
        $buildUrl .=  "&user_data=" . $userData;

        return array(
            'success' => 'true',
            'redirect' => $buildUrl
        );
    }


    /**
     * Get endpoint 
     * @param string $environment The environment for Rocketfuel
     * @return string 
     */
    public function getEndpoint($environment)
    {
        $environmentData = array(
            'prod' => 'https://app.rocketfuelblockchain.com/api',
            'dev' => 'https://dev-app.rocketdemo.net/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
        );

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://app.rocketfuelblockchain.com/api';
    }
}
