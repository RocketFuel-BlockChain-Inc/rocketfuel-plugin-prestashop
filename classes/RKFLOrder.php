







<?php


class RKFLOrder{
    /**
     * Request data
     *
     * @var string
     */
    protected $request;

    public function __construct($request = null)
    {

        $this->request = $request;
    }
	/**
	 * Update order when payment has been confirmed
	 * @param WP_REST_REQUEST $request_data
	 * @return void
	 */
	public function updateOrder(){
     
        switch ($this->request['status']) {
            case '101':
                $status =(int)Configuration::get('PS_OS_PAYMENT');
                break;
                case '1':
                    $status = (int)Configuration::get('PS_OS_PAYMENT'); //Fix partial payment
                    break;
            case '-1':
                $status =(int)Configuration::get('PS_OS_CANCELED');;
            default:
                break;
        }
        // var_dump((int)Configuration::get('PS_OS_PAYMENT'));
      

        $history = new OrderHistory();
        $history->id_order = $this->request['order_id'];
        $history->changeIdOrderState($status , $history->id_order);
        $history->addWithemail();
        $history->save();
        return $status;
    }
}