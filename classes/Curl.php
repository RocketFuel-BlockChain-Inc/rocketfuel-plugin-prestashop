<?php
Namespace RocketFuel\Classes;
/**
 * Order class
 * @author Blessing Udor
 * @copyright 2010-2022 RocketFuel
 * @license   LICENSE.txt
 */

class Curl
{

    public $curl;
    /**
     * The CURL Constructor
     */
    public function __construct()
    {
        $this->curl = curl_init();
    }

    protected function addHeader($header)
    {
        $default = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_RETURNTRANSFER => true
        );
        $newOption =  $header + $default;

        curl_setopt_array($this->curl, $newOption);
    }
    /**
     * Process data to get uuid
     *
     * @param array $data - Data from plugin.
     * - 'cred' => string, 
     * -   'endpoint' =>string,
     * -   'body' => arrray
     * -           'amount' =>string,
     * -            'cart' => array,
     * -            'merchant_id',
     * -            'currency','order',
     * -             redirectUrl
     * @param string $accessToken - Access token for request
     */
    public function processDataToRkfl($data, $accessToken = null)
    {
        if (!$accessToken) {
            $response = $this->auth($data);

            $result = json_decode($response);

            if (!$result) {
                return array(
                    'success' => false,
                    'message' => 'Authorization cannot be completed'
                );
            }
     
            if (($result && $result->ok !== true) || !$result->result->access) {
                return false;
            }
            $accessToken = $result->result->access;
        }
 
        $charge_response = $this->createCharge( $data, $accessToken);

        $charge_result = json_decode($charge_response);

        if (!$charge_result || $charge_result->ok === false) {
            return array('success' => false, 'message' => 'Could not establish an order: ' . $charge_result->message);
        }

        return  $charge_result;
    }

    /**
     * Process authentication
     * @param array $data
     */
    public function auth($data)
    {

        $this->curl = curl_init();

        $body = json_encode($data['cred']);

        $url = $data['endpoint'] . '/auth/login';

        $header =  array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        );

        $this->addHeader($header);

        $response = curl_exec($this->curl);
        return $response;
    }

    /**
     * Get UUID of the customer
     * @param array  $data  Request body
     * @param string $accessToken Access token for request
     *
     * @return array
     */
    public function createCharge($data,$accessToken)
    {

        $this->curl = curl_init();

        $body = json_encode($data['body']);

        $url = $data['endpoint'] . '/hosted-page';

        $header =  array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $accessToken",
                'Content-Type: application/json'
            ),
        );

        $this->addHeader($header);

        $response = curl_exec($this->curl);

        curl_close($this->curl);

        return $response;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function swapOrderId($data)
    {
        $body = $data['body'];

        $url = $data['endpoint'] . '/update/orderId';

        $header =  array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        );

        $this->addHeader($header);

        $response = curl_exec($this->curl);

        curl_close($this->curl);

        return $response;
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}
