<?php

use GuzzleHttp\Psr7\Response;

class Callback
{
    const PUB_KEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCVyByXLNic5gXpZ6SL6xqLrdQYmPdba6Xbb1q2TiXY4URig1AfgLoPs8qK937oYSmfAJUGudCYsZy00e8VbNcOS1O973/u+t7qykGBLxLZQDfSr6wbfGRhThDdBVmENYxEhrBxJZJmmRGShy66x5JArnFWOdfwRbQCaZMZU1tl7wIDAQAB';
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

        dump($this->getOrderPayload($order));

        if (!$order->reference) {
            throw new Exception('order not found');
        }

        if (!($order->getCurrentState() == Configuration::get('PS_OS_BANKWIRE'))) {
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
        //todo for test
        //$history->save();
        dump($history);
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
        dump($order);
        dump($signature);

        $hash = base64_encode(
            hash_hmac(
                'sha256',
                json_encode($this->getOrderPayload($order)),
                self::PUB_KEY
            ));

        //todo compare hash and signature
        dump($hash);
        $this->makeOrderPayed($order);

        error_log('test');


        //echo json_encode([], true);
    }
}


/**
 * Controller for process cloudpayments callbacks
 */
class CloudpaymentsCallback
{
    private $response;

    public function __construct($callbackType)
    {
        $this->response = new stdClass;
        $this->response->code = 13;//error code
        if ($this->checkSignature()) {
            $this->init($callbackType);
        }
    }

    /**
     *  Init callbacks
     * @param string $callbackType
     * @return void
     */
    private function init($callbackType)
    {
        switch ($callbackType) {
            case 'check':
                $this->check();
                break;

            case 'pay':
                $this->pay();
                break;

            case 'fail':
                $this->fail();
                break;

            case 'confirm':
                $this->confirm();
                break;

            case 'refund':
                $this->refund();
                break;

            case 'cancel':
                $this->cancel();
                break;
        }
    }

    /**
     *  Check callback
     * @return void
     */
    function check()
    {
        $invoiceId = Tools::getValue('InvoiceId');
        $amount = Tools::getValue('Amount');
        $currency = Tools::getValue('Currency');
        if ($invoiceId && $amount && $currency) {
            $cart = new Cart($invoiceId);
            if ($cart) {
                if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
                    if ($cart->getOrderTotal(true) == $amount && Currency::getIdByIsoCode($currency) == $cart->id_currency) {
                        $this->response->code = 0;
                        $validate = true;
                    }
                } else {
                    if ($cart->getOrderTotal(true) == $amount && Configuration::get('CLOUDPAYMENTS_CURRENCY') == $currency) {
                        $this->response->code = 0;
                        $validate = true;
                    }
                }
                $order = new Order(Order::getOrderByCartId($invoiceId));
                if ($validate && $order->id == NULL) {
                    $customer = new Customer((int)$cart->id_customer);
                    $Cloudpayments = new Cloudpayments();
                    $Cloudpayments->validateOrder(
                        (int)$cart->id,
                        Configuration::get('PS_OS_PREPARATION'), $cart->getOrderTotal(),
                        'Cloudpayments',
                        null,
                        null,
                        $cart->id_currency, false,
                        $customer->secure_key
                    );
                }
            }
        }
    }

    /**
     *  Pay callback
     * @return void
     */
    private function pay()
    {
        $invoiceId = Tools::getValue('InvoiceId');
        $amount = Tools::getValue('Amount');
        $currency = Tools::getValue('Currency');
        if ($invoiceId && $amount && $currency) {
            $cart = new Cart($invoiceId);
            if ($cart) {
                if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
                    if ($cart->getOrderTotal(true) == $amount && Currency::getIdByIsoCode($currency) == $cart->id_currency) {
                        $this->response->code = 0;
                        $validate = true;
                    }
                } else {
                    if ($cart->getOrderTotal(true) == $amount && Configuration::get('CLOUDPAYMENTS_CURRENCY') == $currency) {
                        $this->response->code = 0;
                        $validate = true;
                    }
                }
                $paystage = Configuration::get('CLOUDPAYMENTS_PAYSTAGE');

                if ($validate && $paystage == 0) {
                    $order = new Order(Order::getOrderByCartId($invoiceId));
                    if ($order->id != NULL) {
                        $history = new OrderHistory();
                        $history->id_order = $order->id;
                        $history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $history->id_order);
                        $history->addWithemail();
                        $history->save();
                        //save payment info
                        $payments = $order->getOrderPaymentCollection();
                        $payments[0]->transaction_id = Tools::getValue('TransactionId');
                        $payments[0]->card_number = Tools::getValue('CardFirstSix') . Tools::getValue('CardLastFour');
                        $payments[0]->card_brand = Tools::getValue('CardType');
                        $payments[0]->card_expiration = Tools::getValue('CardExpDate');
                        $payments[0]->card_holder = Tools::getValue('Name');
                        $payments[0]->update();
                    }
                }
            }
        }
    }

    /**
     *  Fail callback
     * @return void
     */
    private function fail()
    {
        $this->response->code = 0;
    }

    /**
     *  Confirm callback
     * @return void
     */
    private function confirm()
    {
        $invoiceId = Tools::getValue('InvoiceId');
        if ($invoiceId) {
            $order = new Order(
                Order::getOrderByCartId($invoiceId)
            );
            if ($order->id != NULL) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $history->id_order);
                $history->addWithemail();
                $history->save();

                $this->response->code = 0;
            }
        }
    }

    private function refund()
    {
        $invoiceId = Tools::getValue('InvoiceId');
        if ($invoiceId) {
            $order = new Order(
                Order::getOrderByCartId($invoiceId)
            );
            if ($order->id != NULL) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int)Configuration::get('PS_OS_REFUND'), $history->id_order);
                $history->addWithemail();
                $history->save();

                $this->response->code = 0;
            }
        }
    }

    /**
     *  Cancel callback
     * @return void
     */
    private function cancel()
    {
        $invoiceId = Tools::getValue('InvoiceId');
        if ($invoiceId) {
            $order = new Order(
                Order::getOrderByCartId($invoiceId)
            );
            if ($order->id != NULL) {
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->changeIdOrderState((int)Configuration::get('PS_OS_REFUND'), $history->id_order);
                $history->addWithemail();
                $history->save();

                $this->response->code = 0;
            }
        }
    }

    /**
     *  Check signature
     * @return bool
     */
    private function checkSignature()
    {
        $headers = getallheaders();
        if (!isset($headers['Content-HMAC']) && !isset($headers['Content-Hmac'])) {
            return false;
        }
        $signature = base64_encode(
            hash_hmac(
                'SHA256',
                file_get_contents('php://input'),
                Configuration::get('CLOUDPAYMENTS_APIKEY'),
                true)
        );

        if ($headers['Content-HMAC'] == $signature) {
            return true;
        } else if ($headers['Content-Hmac'] == $signature) return true;
    }

    public function getResponse()
    {
        return json_encode($this->response);
    }

}
