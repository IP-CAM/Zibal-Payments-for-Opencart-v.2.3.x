<?php 

class ModelExtensionPaymentZibal extends Model 
{
	  public function getMethod($address) 
	  {
		$this->load->language('extension/payment/zibal');

		if ($this->config->get('zibal_status')) {

			  $status = true;
			  
      	} else {

			$status = false;
			
		}

		$method_data = array();
		
		if ($status) {
      		$method_data = array( 
        		'code'       => 'zibal',
        		'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('zibal_sort_order')
      		);
    	}
		
    	return $method_data;
  	}
}
?>