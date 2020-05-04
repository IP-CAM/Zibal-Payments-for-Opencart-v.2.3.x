<?php
class ControllerExtensionPaymentZibal extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/zibal');

		$this->document->setTitle($this->language->get('doc_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('zibal', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_authorization'] = $this->language->get('text_authorization');
		$data['text_sale'] = $this->language->get('text_sale');
		$data['text_edit'] = $this->language->get('text_edit');

		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$data['entry_zibal_direct'] = $this->language->get('entry_zibal_direct');

		//new???
		// $data['text_host_iran'] = $this->language->get('text_host_iran');
		// $data['text_host_foreign'] = $this->language->get('text_host_foreign');
		// $data['text_support'] = $this->language->get('text_support');
		// $data['text_support_title'] = $this->language->get('text_support_title');
		
		// $data['entry_pin'] = $this->language->get('entry_pin');
		// $data['entry_host_type'] = $this->language->get('entry_host_type');
		// $data['entry_order_status'] = $this->language->get('entry_order_status');
		// $data['entry_status'] = $this->language->get('entry_status');
		// $data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');
      	$data['tab_additional'] = $this->language->get('tab_additional');

		// if (isset($this->error['warning'])) {
		// 	$data['error_warning'] = $this->error['warning'];
		// } else {
		// 	$data['error_warning'] = '';
		// }

		// if (isset($this->error['pin'])) {
		// 	$data['error_pin'] = $this->error['pin'];
		// } else {
		// 	$data['error_pin'] = '';
		// }

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(

			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
			// new
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/zibal', 'token=' . $this->session->data['token'], true),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/payment/zibal', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : false;
		$data['error_merchant'] = isset($this->error['merchant']) ? $this->error['merchant'] : false;
		

		if (isset($this->request->post['zibal_merchant'])) {
			$data['zibal_merchant'] = $this->request->post['zibal_merchant'];
		} else {
			$data['zibal_merchant'] = $this->config->get('zibal_merchant');
		}

		if (isset($this->request->post['zibal_direct'])) {
			$data['zibal_direct'] = $this->request->post['zibal_direct'];
		} else {
			$data['zibal_direct'] = $this->config->get('zibal_direct');
		}

		if (isset($this->request->post['zibal_order_status_id'])) {
			$data['zibal_order_status_id'] = $this->request->post['zibal_order_status_id'];
		} else {
			$data['zibal_order_status_id'] = $this->config->get('zibal_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['zibal_status'])) {
			$data['zibal_status'] = $this->request->post['zibal_status'];
		} else {
			$data['zibal_status'] = $this->config->get('zibal_status');
		}

		if (isset($this->request->post['zibal_sort_order'])) {

			$data['zibal_sort_order'] = $this->request->post['zibal_sort_order'];

		} else {

			$data['zibal_sort_order'] = $this->config->get('zibal_sort_order');

		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/zibal', $data));
	}

	protected function validate() 
	{
	
		if (!$this->user->hasPermission('modify', 'extension/payment/zibal')) {

			$this->error['warning'] = $this->language->get('error_permission');

		}
		
		if (!$this->request->post['zibal_merchant']) {

			// $this->error['pin'] = $this->language->get('error_pin');
			$this->error['warning'] = $this->language->get('error_validate');
			$this->error['merchant'] = $this->language->get('error_merchant');

		}

		if (!$this->error) {

			return true;

		} else {

			return false;
		}
		// return !$this->error;
	}
}
?>