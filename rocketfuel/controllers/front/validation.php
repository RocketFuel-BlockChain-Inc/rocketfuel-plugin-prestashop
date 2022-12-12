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

     
        /**
         * Redirect the customer to the order confirmation page
         */
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key . $result['redirect']);
        unset($cart);
        unset($customer);
    }
    /**
     * Parse cart items and prepare for order
     * @param array $items 
     * @return array
     */
    public function sortCart($items,$shippings)
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
  
            if (isset($shippings) && is_array($shippings)) {
                foreach ($shippings as $shipping) {
                    $data[] = array(
                        'name' => 'Shipping: '.$shipping['carrier_name'],
                        'id' => $shipping['id_order_invoice'],
                        'price' =>$shipping['shipping_cost_tax_incl'],
                        'quantity' => 1
                    );
                }
               
                
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
    public function processPayment($orderId, $cartObj, $customer)
    {
        $this->environment = Configuration::get('ROCKETFUEL_ENVIRONMENT');

        $merchantId = Configuration::get('ROCKETFUEL_MERCHANT_ID');
        
        $order = new Order($orderId);
        
        $shipping = $order->getShipping();

     
        $currency = new Currency($order->id_currency);

        $cart = $this->sortCart($cartObj->getProducts(true), $shipping );
      
        $userData = base64_encode(json_encode(array(
            'first_name' => $customer->firstname,
            'last_name' => $customer->lastname,
            'email' => $customer->email,
            'merchant_auth' =>     $this->merchant_auth($merchantId)
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
                'merchant_id' => $merchantId,
                'currency' =>  $currency->iso_code,
                'order' => (string) $orderId,
                'redirectUrl' => ''
            )
        );

        
       
        $curl = new Curl();

        $paymentResponse = $curl->processDataToRkfl($data);

        unset($curl);
        unset($order);
        unset($currency);
        
        if (!$paymentResponse) {
            throw new \Error();
            return;
        }

    

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
    public function merchant_auth($merchantId)
    {
        return $this->get_encrypted($merchantId);
    }
    /**
     * Encrypt Data
     *
     * @param $to_crypt string to encrypt
     * @return string
     */
    public function get_encrypted($to_crypt)
    {

        $out = '';

        $pub_key_path = dirname(__FILE__, 3) . '/key/.rf_public.key';

        if (!file_exists($pub_key_path)) {
            return false;
        }
        $cert = file_get_contents($pub_key_path);

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
