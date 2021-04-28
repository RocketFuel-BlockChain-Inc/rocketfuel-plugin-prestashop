<?php

use GuzzleHttp\Psr7\Response;

//require_once(dirname(__FILE__) . '/../.public.php');

class RocketfuelService
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
    protected $merchant_id;

    public function __construct($request = null)
    {
        $this->merchant_id = Configuration::get('ROCKETFUEL_MERCHANT_ID');
        $this->rf_pkey = Configuration::get('ROCKETFUEL_MERCHANT_PUBLIC_KEY');
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
        $shipping_total=$order->total_shipping;
        if($shipping_total!=0){
            $out['cart'][] = [
                'id' => '',
                'name' => 'Shipping: '.$order->getShipping()[0]['state_name'],
                'price' => $shipping_total,
                'quantity' => 1
            ];
        }

        $out['amount'] = $order->total_paid;
        $out['merchant_id'] = $this->merchant_id;
        $out['order'] = $order->id;
        $out['encrypted'] = $this->getEncrypted($order->total_paid,  $order->id);

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
        if (is_object($payload))
            $payload = (array)$payload;
        $keys = array_keys($payload);
        sort($keys);

        foreach ($keys as $key){
            if(is_bool($payload[$key])){
                $sorted[$key] =$payload[$key];
            }else{
                $sorted[$key] = is_array($payload[$key]) ? $this->sortPayload($payload[$key]) : (string)$payload[$key];
            }
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

        $signature = $this->request['signature'];

        $public_key = openssl_pkey_get_public(
            file_get_contents(dirname(__FILE__) . '/../key/.rf_public.key')
        );

        $verify = openssl_verify(
            json_encode($this->getOrderPayload($order)),
            base64_decode($signature),
            $public_key,
            'SHA256'
        );

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

    /**
     * @param $amount
     * @param $order_id
     * @return string
     */
    public function getEncrypted($amount, $order_id)
    {
        $to_crypt = json_encode([
            'amount' => $amount,
            'merchant_id' => $this->merchant_id,
            'order' => $order_id
        ]);

        $out = '';

        $cert = $this->rf_pkey;

        $public_key = openssl_pkey_get_public($cert);
        $key_lenght = openssl_pkey_get_details($public_key);

        $part_len = $key_lenght['bits'] / 8 - 11;
        $parts = str_split($to_crypt, $part_len);
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_public_encrypt($part, $encrypted_temp, $public_key,OPENSSL_PKCS1_OAEP_PADDING);
            $out .=  $encrypted_temp;
        }

        return base64_encode($out);
    }

}
