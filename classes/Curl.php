<?php
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
            CURLOPT_TIMEOUT => 0,      CURLOPT_RETURNTRANSFER => true);
        $newOption =  $header + $default;
  
        curl_setopt_array($this->curl, $newOption);
    }
    /**
     * Process data to get uuid
     *
     * @param array $data - Data from plugin.
     */
    public function processDataToRkfl($data)
    {

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

        $charge_response = $this->createCharge($result->result->access, $data);

        $charge_result = json_decode($charge_response);

        if (!$charge_result || $charge_result->ok === false) {
            return array('success' => false, 'message' => 'Could not establish an order: ' . $charge_result->message);
        }

        return json_decode($charge_response);
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
     * @param string $accessToken Access token for request
     * @param array  $data  Request body
     *
     * @return array
     */
    public function createCharge($accessToken, $data)
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
}
