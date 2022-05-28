<?php
/**
 * Callback Class for andling webhook
 * 
 * @author Blessing Udor<reachme@blessingudor.com>
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */

require_once(dirname(__FILE__, 2) . '/classes/Curl.php');

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
    protected $merchant_id, $environment;

    public function __construct($request = null)
    {
        $this->merchant_id = Configuration::get('ROCKETFUEL_MERCHANT_ID');
        
        $this->environment = Configuration::get('ROCKETFUEL_ENVIRONMENT');

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

        $data = json_decode($this->request['data'], true);
        $order = new Order($data['offerId']);

        if (!$order->reference) {
            throw new Exception('order not found');
        }

        if (((int)$order->getCurrentState() <> (int)Configuration::get('PS_OS_BANKWIRE'))) {
            throw new Exception('order payed');
        }

        return $order;
    }

    /**
     * Make order payed
     *
     * @param $order
     */
    protected function makeOrderPayed($order)
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

    public function getCartPayload($order)
    {
        $out = [];

        foreach ($order->getProducts() as $product) {
            $out['cart'][] = [
                'id' => $product['id_product'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $product['cart_quantity']
            ];
        };

        $currency = new Currency(Context::getContext()->cookie->id_currency);

        $tempId = (string) md5(time());

        $data = [
            'cred' => $this->merchantCred(),
            'endpoint' => $this->getEndpoint($this->environment),
            'body' => [
                'amount' => (string)$order->getOrderTotal(),
                'cart' => $out['cart'], //$order,//cart
                'merchant_id' => $this->merchant_id,
                'currency' =>  $currency->iso_code,
                'order' => $tempId,// (string) $order->id.' '.time(),//cart id
                'redirectUrl' => ''
            ]
        ];

        $out['amount'] = (string)$order->getOrderTotal();
        
        $out['merchant_auth'] = $this->get_encrypted($this->merchant_id);
        
        $out['environment'] = $this->environment;
        
        $out['order'] = $tempId;
        
        $out['uuid'] = $this->get_uuid($data);
        
        $out['customer'] = json_encode(new Customer($order->id_customer));

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

        $body = isset($this->request['data']['data']) ? $this->request['data']['data'] : '';

        $signature = $this->request['signature'];

        $public_key = openssl_pkey_get_public(
            Tools::file_get_contents(dirname(__FILE__) . '/../key/.rf_public.key')
        );

        // $verify = openssl_verify(
        //     $body ,
        //     base64_decode($signature),
        //     $public_key,
        //     'SHA256'
        // );

        $verify = openssl_verify($body, base64_decode($signature), $public_key, OPENSSL_ALGO_SHA256);

        if ($verify) {
            $this->makeOrderPayed($order);
            //todo response
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode([
                'status' => 'error',
                'signature not valid'
            ]);
        }
    }

    protected function get_encrypted($to_crypt)
    {

        $out = '';

        $pub_key_path = dirname(__FILE__, 2) . '/key/.rf_public.key';

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

    protected function get_uuid($data)
    {
        $curl = new Curl();
file_put_contents(__DIR__.'/log.json',json_encode($data),FILE_APPEND);
        $paymentResponse = $curl->processDataToRkfl($data);
        file_put_contents(__DIR__.'/response.json',json_encode($paymentResponse),FILE_APPEND);

        unset($curl);

        if (!$paymentResponse) {
            return false;
        }



        $result = $paymentResponse;

        if (!isset($result->result) && !isset($result->result->url)) {
            // wc_add_notice(__('Failed to place order', 'rocketfuel-payment-gateway'), 'error');
            return array('succcess' => 'false');
        }
        $urlArr = explode('/', $result->result->url);

        return $urlArr[count($urlArr) - 1];
    }

    public function getEndpoint($environment)
    {
        $environmentData = [
            'prod' => 'https://app.rocketfuelblockchain.com/api',
            'dev' => 'https://dev-app.rocketdemo.net/api',
            'stage2' => 'https://qa-app.rocketdemo.net/api',
            'preprod' => 'https://preprod-app.rocketdemo.net/api',
        ];

        return isset($environmentData[$environment]) ? $environmentData[$environment] : 'https://app.rocketfuelblockchain.com/api';
    }

    public function merchantCred()
    {
        return [
            'email' => Configuration::get('ROCKETFUEL_MERCHANT_EMAIL'),
            'password' => Configuration::get('ROCKETFUEL_MERCHANT_PASSWORD')
        ];
    }
}
