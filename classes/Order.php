







<?php


class Order{

	/**
	 * Update order when payment has been confirmed
	 * @param WP_REST_REQUEST $request_data
	 * @return void
	 */
	public function updateOrder($request_data)
	{
		$data = $request_data->get_params();
		if (!$data['order_id'] || !$data['status']) {
			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Invalid parameter. You must pass in order_id and status'));
			exit;
		}

		$order_id = $data['order_id'];

		$status = $data['status'];

		$order = new Order($order_id);

		if (!$order) {
			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Could not retrieve order from the order_id that was sent'));
			exit;
		}

		if ($status === 'admin_default')
			$status = $this->payment_complete_order_status;

		$data = $order->update_status($status);
		echo json_encode(array('status' => 'success', 'message' => 'Order was updated'));
		exit;
	}