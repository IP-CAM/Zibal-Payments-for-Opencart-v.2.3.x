<?php

class ControllerExtensionPaymentZibal extends Controller {
	public function index() {
		$this->load->language('extension/payment/zibal');
		
		$data['text_connect'] = $this->language->get('text_connect');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_wait'] = $this->language->get('text_wait');
		
    	$data['button_confirm'] = $this->language->get('button_confirm');

		return $this->load->view('extension/payment/zibal', $data);
	}

	private function post_to_zibal($url, $data = false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://gateway.zibal.ir/v1/".$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=utf-8'));
		curl_setopt($ch, CURLOPT_POST, 1);
		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return !empty($result) ? json_decode($result) : false;
	}

	public function confirm() {
		$this->load->language('extension/payment/zibal');
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$amount = $this->correctAmount($order_info);

		$data['return'] = $this->url->link('checkout/success', '', true);
		$data['cancel_return'] = $this->url->link('checkout/payment', '', true);
		$data['back'] = $this->url->link('checkout/payment', '', true);
		
		$merchant = $this->config->get('zibal_merchant');
		$description = $this->language->get('text_order_no') . $order_info['order_id'];
		$mobile = isset($order_info['fax']) ? $order_info['fax'] : $order_info['telephone'];
		$data['order_id'] = $this->encryption->encrypt($this->session->data['order_id']);
		$callbackUrl = $this->url->link('extension/payment/zibal/callback', 'order_id=' . $data['order_id'], true);

		$parameters = array(
			'merchant' 		=> $merchant,
			'amount' 		=> $amount,
			'description' 	=> $description,
			'mobile' 		=> $mobile,
			'orderId' 		=> $order_info['order_id'],
			'callbackUrl' 	=> $callbackUrl
		);

		$requestResult = $this->post_to_zibal('request',$parameters);
		
		if(!$requestResult) {
			$json = array();
			$json['error']= $this->language->get('error_cant_connect');				
		} elseif($requestResult->result == 100) {
			$data['action'] = "https://gateway.zibal.ir/start/" . $requestResult->trackId;
			$json['success']= $data['action'];
		} else {
			$json = $this->checkState($requestResult->Status);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback() {

		if ($this->session->data['payment_method']['code'] == 'zibal') {
			$this->load->language('extension/payment/zibal');

			$this->document->setTitle($this->language->get('text_title'));
			
			$data['heading_title'] = $this->language->get('text_title');
			$data['text_results'] = $this->language->get('text_results');
			$data['results'] = "";
			
			$data['breadcrumbs'] = array();
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'), 
				'href' => $this->url->link('common/home', '', true)
			);
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_title'), 
				'href' => $this->url->link('extension/payment/zibal/callback', '', true)
			);

			try {
				if($this->request->get['status'] != '2') {
					throw new Exception($this->language->get('error_verify'));
				}

				/*$order_id = isset($this->request->get['order_id']) ? $this->encryption->decrypt($this->request->get['order_id']) : 0;*/
				$order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0;

				$this->load->model('checkout/order');
				$order_info = $this->model_checkout_order->getOrder($order_id);

				if (!$order_info)
					throw new Exception($this->language->get('error_order_id'));

				$trackId = $this->request->get['trackId'];
				$amount = $this->correctAmount($order_info);

				$verifyResult = $this->verifyPayment($trackId, $amount);

				if (!$verifyResult)
					throw new Exception($this->language->get('error_connect_verify'));

				if ($verifyResult['result'] == 100) {
					$comment = $this->language->get('text_results') . $verifyResult['result'];
					
					$ress = $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('zibal_order_status_id'), $comment, true);

					$data['error_warning'] = NULL;
					$data['result'] = $verifyResult['result'];
					$data['button_continue'] = $this->language->get('button_complete');
					$data['continue'] = $this->url->link('checkout/success');
					$this->response->redirect('index.php?route=checkout/success');
				} else {
					throw new Exception($this->checkState($verifyResult['result'])['error']);
				}

			} catch (Exception $e) {
				$data['error_warning'] = $e->getMessage();
				$data['button_continue'] = $this->language->get('button_view_cart');
				$data['continue'] = $this->url->link('checkout/cart');
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('extension/payment/zibal_confirm', $data));
		}
	}

	private function correctAmount($order_info) {
		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$amount = round($amount);
		$amount = $this->currency->convert($amount, $order_info['currency_code'], "RLS");
		return (int)$amount;
	}

	private function checkState($status) {
		$json = array();
		$json['error'] = $this->language->get('error_status_undefined');

		if ($this->language->get('error_status_' . $status) != 'error_status_' . $status ) {
			$json['error'] = $this->language->get('error_status_' . $status);
		}

		return $json;
	}

	private function verifyPayment($trackId, $amount) {
		$merchant = $this->config->get('zibal_merchant');

		$context = array(
			'merchant'		=> $merchant,
			'trackId' 		=> $trackId
		);
		
		$verifyResult = $this->post_to_zibal('verify', $context);

		if(!$verifyResult) {
			// echo  $this->language->get('error_cant_connect');
			return ['result' => 'error'];
		} elseif($verifyResult->result) {
			if ($amount == $verifyResult->amount) {
				return ['result' => $verifyResult->result];
			} else {
				return ['result' => 'error'];
			}
		} else {
			return ['result' => 'error'];
		}
	}
}

?>