<?php

use GuzzleHttp\Psr7\Response;
require_once(dirname(__FILE__) . '/../.public.php');

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
    protected $merchant_id;

    public function __construct($request = null)
    {
        $this->merchant_id = Configuration::get('ROCKETFUEL_MERCHANT_ID');
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

        if ( ((int)$order->getCurrentState() <> (int)Configuration::get('PS_OS_BANKWIRE')) ) {
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
     * "price":0.05,
     * "name":"Beanie with Logo"
     * },{
     * "id":"22",
     * "price":0.05,
     * "name":"Belt"
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
                'price' => $product['total_price'],
                'name' => $product['product_name']
            ];
        };

        $out['amount'] = $order->total_paid;
        $out['merchant_id'] = $this->merchant_id;
        $out['order'] = $order->id;
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

        foreach ($keys as $key)
            $sorted[$key] = is_array($payload[$key]) ? $this->sortPayload($payload[$key]) : (string)$payload[$key];
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
        //dump($order);


        $hash = base64_encode(
            hash_hmac(
                'sha256',
                json_encode($this->getOrderPayload($order)),
                PUB_KEY
            ));

        if($hash === $signature){
            $this->makeOrderPayed($order);
            //todo response
            echo json_encode(['status' => 'ok']);
        }
    }
}
