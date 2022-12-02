<?php
require 'vendor/autoload.php';

class gc_rider_ctrl extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('GC_Rider_Model');
	}

	public function gc_get_customer_orders_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$bunit_code = $this->security->xss_clean($this->input->post('bunit_code'));
		//$r_id_number = '000001-2020'; 		
		//$r_id_number = '000055-2021';
		$this->GC_Rider_Model->gc_download_customer_orders_mod($r_id_number);
		// $this->GC_Rider_Model->gc_download_customer_orders_mod('000018-2020');
	}

	public function gc_get_items_breakdown_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '000001-2020';
		// $ticket_id = '210325-3-004';
		//$ticket_id = '210315-1-001';
		echo $this->GC_Rider_Model->gc_download_transaction_view_items_mod($r_id_number, $ticket_id);
	}

	public function gc_update_delivery_status_controller()
	{
		if (isset($_POST['update_delivered_status'])) {
			$id = $this->security->xss_clean($this->input->post('update_delivered_status'));
			$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
			$data['update_delivery_status'] = $this->GC_Rider_Model->gc_update_delivery_status_mod($id, $r_id_num);
		}
	}

	public function gc_update_cancelled_status_controller()
	{
		if (isset($_POST['update_cancelled_status'])) {
			$id = $this->security->xss_clean($this->input->post('update_cancelled_status'));
			$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
			$this->GC_Rider_Model->gc_update_cancelled_status_mod($id, $r_id_num);
			$this->GC_Rider_Model->gc_update_delivery_status_mod($id, $r_id_num);
		}
	}

	public function gc_get_customer_details_controller()
	{
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		//$ticket_id = '210327-3-001';
		echo $this->GC_Rider_Model->gc_get_customer_details_mod($ticket_id);
	}

	public function gc_update_viewed_status_controller()
	{
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		//$ticket_id = '210325-3-004';
		$this->GC_Rider_Model->gc_update_viewed_status_mod($ticket_id);
	}

	public function gc_get_tenant_timeframe_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '00001-2020';
		// $ticket_id = '210325-3-004';
		$this->GC_Rider_Model->gc_get_tenant_timeframe_mod($r_id_number, $ticket_id);
	}

	public function gc_get_history_items_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$r_id_number = '000001-2020'; 
		$this->GC_Rider_Model->gc_download_history_items_mod($r_id_number);
		//$this->load->view('json/pages/json_index8',$data);
	}
	public function gc_get_reports_delivered_items_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$delevered_status = $this->security->xss_clean($this->input->post('delevered_status'));
		$selected_date = $this->security->xss_clean($this->input->post('selected_date'));
		// $r_id_number = '000001-2020';
		// $delevered_status = '0';
		// $selected_date = '2021-03-29';
		echo $this->GC_Rider_Model->gc_download_reports_items_mod($r_id_number, $delevered_status, $selected_date);
		//$this->load->view('json/pages/json_index8',$data);
	}
}
