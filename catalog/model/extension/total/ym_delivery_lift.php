<?php
class ModelExtensionTotalYmDeliveryLift extends Model {
	public function getTotal($total) {
        // $this->db->query("SELECT ");
        
        $log = new Log ('session.log');
        $log->write(print_r($this->session->data,1));
	}
}