<?php

use phpDocumentor\Reflection\Types\Void_;

require 'vendor/autoload.php';
// defined('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
// defined('SECRET_IV', 'T1g994xo2UAqG81M');
// defined('ENCRYPT_METHOD', 'AES-256-CBC');
class rider_ctrl extends CI_Controller
{
	private $iv  = 'fdsfds85435nfdfs';
	private $key = '89432hjfsd891787';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Rider_Model');
	}

	public function get_tenants_controller()
	{
		$this->Rider_Model->get_tenants_mod();
	}

	public function get_tenants_users_controller()
	{
		$tenant_id = $this->security->xss_clean($this->input->post('tenant_id'));
		//$tenant_id = '1';
		$this->Rider_Model->get_tenants_users_mod($tenant_id);
	}

	public function get_chatbox_users_controller()
	{
		$user_type = $this->security->xss_clean($this->input->post('user_type'));
		$rider_id = $this->security->xss_clean($this->input->post('rider_id'));
		// $user_type = 'Co-rider';
		// $rider_id = '1';
		$this->Rider_Model->get_chatbox_users_mod($user_type, $rider_id);
	}

	public function get_chatbox_usertype_controller()
	{
		$this->Rider_Model->get_chatbox_usertype_mod();
	}

	public function get_user_type($user_type)
	{
		$final_user_type = '';

		if ($user_type == "Customer Service") {
			$final_user_type = "CSR";
		} else if ($user_type == "Rider Coordinator") {
			$final_user_type = "RIDER COORDINATOR";
		} else if ($user_type == "Tenant") {
			$final_user_type = "TENANT";
		} else if ($user_type == "Co-rider") {
			$final_user_type = "RIDER";
		} else {
			$final_user_type = "OTHERS";
		}

		return $final_user_type;
	}

	public function load_messages_controller()
	{
		$final_user_type = "";

		$user_name = $this->security->xss_clean($this->input->post('user_name'));
		$rider_id = $this->security->xss_clean($this->input->post('rider_id'));
		$user_type = $this->security->xss_clean($this->input->post('user_type'));

		// $rider_id = '21';
		// $user_type = 'Co-rider';
		// $user_name = 'Raymund Calipes';

		if ($user_type == "Customer Service") {
			$final_user_type = "CSR";
		} else if ($user_type == "Rider Coordinator") {
			$final_user_type = "RC";
		} else if ($user_type == "Tenant") {
			$final_user_type = "TENANT";
		} else if ($user_type == "Co-rider") {
			$final_user_type = "RIDER";
		} else if ($user_type == "Customer") {
			$final_user_type = "CUSTOMER";
		} else {
			$final_user_type = "OTHERS";
		}


		$receiver_id = $this->Rider_Model->get_user_id($user_name, $final_user_type);

		$this->Rider_Model->update_seen_status($receiver_id, $rider_id, $final_user_type);
		$this->Rider_Model->retrieve_message($receiver_id, $rider_id, $final_user_type);
	}


	public function load_messages_from_transaction_controller()
	{
		$final_user_type = "";

		$user_name = $this->security->xss_clean($this->input->post('user_name'));
		$rider_id = $this->security->xss_clean($this->input->post('rider_id'));
		$user_type = $this->security->xss_clean($this->input->post('user_type'));

		// $rider_id = '21';
		// $user_type = 'Tenant';
		// $user_name = 'Zoren Ormido';

		if ($user_type == "Customer Service") {
			$final_user_type = "CSR";
		} else if ($user_type == "Rider Coordinator") {
			$final_user_type = "RC";
		} else if ($user_type == "Tenant") {
			$final_user_type = "TENANT";
		} else if ($user_type == "Co-rider") {
			$final_user_type = "RIDER";
		} else if ($user_type == "Customer") {
			$final_user_type = "CUSTOMER";
		} else {
			$final_user_type = "OTHERS";
		}

		$receiver_id = $this->Rider_Model->get_user_id_using_ticket($user_name, $final_user_type);

		$this->Rider_Model->retrieve_message($receiver_id, $rider_id, $final_user_type);
	}


	public function sample_encryption_controller()
	{

		$str = 'vigor45';

		$encrypted = $this->encrypt_txt($str);
		//$decrypted = $this->decrypt_txt($str);


	}

	public function encrypt_txt($str)
	{
		$this->load->library('encryption');
		$msg = 'My secret message';

		$encrypted_string = $this->encrypt->encode($msg);

		return $encrypted_string;
	}

	public function decrypt_txt($code)
	{
	}

	protected function hex2bin($hexdata)
	{
		$bindata = '';
		for ($i = 0; $i < strlen($hexdata); $i += 2) {
			$bindata .= chr(hexdec(substr($hexdata, $i, 2)));
		}
		return $bindata;
	}

	protected function pkcs5_pad($text)
	{
		$blocksize = 16;
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	protected function pkcs5_unpad($text)
	{
		$pad = ord($text[strlen($text) - 1]);
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}

	public function check_connection_controller()
	{
		$this->rider_mod->check_connection_mod();
		$this->load->view('json_view1');
	}

	public function validateUsername_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');
		$et_username = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_username'))));
		$this->Rider_Model->ValidateUsername($et_username);
	}

	public function savenewuser_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$et_firstname = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_firstname'))));
		$et_lastname = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_lastname'))));
		$et_birthdate = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_birthdate'))));
		$rb_sex = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('rb_sex'))));
		$et_permanentaddress = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_permanentaddress'))));
		$et_mobileno = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_mobileno'))));
		$et_username = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_username'))));
		$et_password = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_password'))));
		$spin_license_type = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('spin_license_type'))));
		$et_otherdetails = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_otherdetails'))));
		$et_brand = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_brand'))));
		$et_model = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_model'))));
		$et_color = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_color'))));
		$et_plateno = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_plateno'))));
		$et_otherdetails2 = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_otherdetails2'))));

		$r_id_num = $this->Rider_Model->Count_no_of_riders();
		$this->Rider_Model->save_new_user($r_id_num, $et_firstname, $et_lastname, $et_birthdate, $rb_sex, $et_permanentaddress, $et_mobileno, $et_username, $et_password, $spin_license_type, $et_otherdetails, $et_brand, $et_model, $et_color, $et_plateno, $et_otherdetails2);
	}

	public function sendmessage_controller()
	{

		$final_user_type = "";

		$id = $this->security->xss_clean($this->input->post('rider_id'));
		$message = $this->security->xss_clean($this->input->post('message'));
		$receiver_name = $this->security->xss_clean($this->input->post('receiver_name'));
		$user_type = $this->security->xss_clean($this->input->post('user_type'));

		// $id = "21";
		// $message = "Hi, Leeward from rider21 11:33am";
		// $receiver_name = "Leeward Jane Labunog";
		// $user_type = "Rider Coordinator";

		if ($user_type == "Customer Service") {
			$final_user_type = "CSR";
		} else if ($user_type == "Rider Coordinator") {
			$final_user_type = "RC";
		} else if ($user_type == "Tenant") {
			$final_user_type = "TENANT";
		} else if ($user_type == "Customer") {
			$final_user_type = "CUSTOMER";
		} else if ($user_type == "Co-rider") {
			$final_user_type = "RIDER";
		} else {
			$final_user_type = "OTHERS";
		}


		$receiver_id = $this->Rider_Model->get_user_id($receiver_name, $final_user_type);

		$pusher = new Pusher\Pusher("b78f6990d12c34f243d2", "af19b1a058eb858c3d33", "1106019", array('cluster' => 'ap1'));
		$pusher->trigger($final_user_type . '-' . $receiver_id, 'send-message', array('message' => $message));

		//echo "receiver_id: " . $receiver_id;

		$this->Rider_Model->save_message($id, $message, $receiver_id, $final_user_type);

		$data['getjson_rider_data'] = $this->Rider_Model->retrieve_message($receiver_id, $id, $final_user_type);
	}


	public function remove_message_controller()
	{
		$id = $this->security->xss_clean($this->input->post('message_id'));
		$this->Rider_Model->remove_message_model($id);
	}

	public function update_online_status_to_offline_controller()
	{
		$id = $this->security->xss_clean($this->input->post('rider_id'));
		$this->Rider_Model->update_online_status_to_offline_model($id);
	}

	public function sendmessage_from_transaction_controller()
	{

		$final_user_type = "";

		$id = $this->security->xss_clean($this->input->post('rider_id'));
		$message = $this->security->xss_clean($this->input->post('message'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		$user_type = $this->security->xss_clean($this->input->post('user_type'));

		// $id = "21";
		// $message = "Hi, Leeward from rider21 11:33am";
		// $receiver_name = "Leeward Jane Labunog";
		// $user_type = "Rider Coordinator";

		if ($user_type == "Customer Service") {
			$final_user_type = "CSR";
		} else if ($user_type == "Rider Coordinator") {
			$final_user_type = "RC";
		} else if ($user_type == "Tenant") {
			$final_user_type = "TENANT";
		} else if ($user_type == "Customer") {
			$final_user_type = "CUSTOMER";
		} else if ($user_type == "Co-rider") {
			$final_user_type = "RIDER";
		} else {
			$final_user_type = "OTHERS";
		}

		$pusher = new Pusher\Pusher("b78f6990d12c34f243d2", "af19b1a058eb858c3d33", "1106019", array('cluster' => 'ap1'));
		$pusher->trigger('csr-chat-5', 'send-message', array('message' => $message));

		$receiver_id = $this->Rider_Model->get_user_id_using_ticket($ticket_id, $final_user_type);

		//echo "receiver_id: " . $receiver_id;

		$this->Rider_Model->save_message($id, $message, $receiver_id, $final_user_type, $ticket_id);

		$data['getjson_rider_data'] = $this->Rider_Model->retrieve_message($receiver_id, $id, $final_user_type);
	}

	public function validateloginwithencryption_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$user = $this->security->xss_clean($this->input->post('username'));
		$password = $this->security->xss_clean($this->input->post('password'));

		// $user = 'zruaCFExZmspONEsW8p/Ng==';
		// $password = 'VDFnOTk0eG8yVUFxRzgxTbjLc4/Aq3xDnEUdPPKFflw=';
		// $char = 'vigor45';

		// $decrypted_user = $this->remove_char($this->decrypt($user));
		// $decrypted_pass = $this->remove_char($this->decrypt($password));			
		$decrypted_user = $this->decrypt($user);
		$decrypted_pass = $this->decrypt($password);

		// $encrypted_user = $this->encrypt($char);
		// $decrypted_pass2 = $this->decrypt($encrypted_user);
		// $encrypted_user2 = $this->encrypt($decrypted_pass2);

		// echo $encrypted_user . '<br/>';
		//echo $decrypted_user . '<br/>';
		//echo $decrypted_pass2 . '<br/>';
		// echo $encrypted_user2 . '<br/>';


		$data['getjson_rider_data'] = $this->Rider_Model->validate_login_mod($decrypted_user, $decrypted_pass);
		$this->load->view('json_view2', $data);
	}

	public function remove_char($text)
	{
		return substr($text, 16);
	}

	public function encrypt($string)
	{
		return openssl_encrypt($string, ENCRYPT_METHOD, SECRET_KEY, 0, SECRET_IV);
	}

	public function decrypt($string)
	{
		return openssl_decrypt($string, ENCRYPT_METHOD, SECRET_KEY, 0, SECRET_IV);
	}

	public function validatelogin_controller()
	{

		$user = $this->security->xss_clean($this->input->post('username'));
		$password = $this->security->xss_clean($this->input->post('password'));

		$data['getjson_rider_data'] = $this->Rider_Model->validate_login_mod($user, $password);
		$this->load->view('json_view2', $data);
	}

	public function update_rider_blocked_status_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$user = $this->security->xss_clean($this->input->post('username'));

		// $user = 'VDFnOTk0eG8yVUFxRzgxTYsOZ3Pm0CKpoEp6EvAIFjg=';

		$decrypted_user = $this->remove_char($this->decrypt($user));

		// $decrypted_user = '00005-2020';

		$this->Rider_Model->update_rider_blocked_status_mod($decrypted_user);
	}

	public function validate_login_with_security_controller()
	{
		//var_dump($_SERVER['SERVER_ADDR']);
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$user = $this->security->xss_clean($this->input->post('username'));
		$password = $this->security->xss_clean($this->input->post('password'));

		// $user = 'VDFnOTk0eG8yVUFxRzgxTYsOZ3Pm0CKpoEp6EvAIFjg=';
		// $password = 'VDFnOTk0eG8yVUFxRzgxTaBb86ZuJDpNDID7xsrmloM=';

		// $decrypted_user = $this->remove_char($this->decrypt($user));
		// $decrypted_pass = $this->remove_char($this->decrypt($password));

		$decrypted_user = $this->decrypt($user);
		$decrypted_pass = $this->decrypt($password);

		// $decrypted_user = '00005-2020';
		// $decrypted_pass = 'rider-20201';


		$data['getjson_rider_data'] = $this->Rider_Model->validate_login_with_security_mod($decrypted_user, $decrypted_pass);
		$this->load->view('json_view2', $data);
	}

	public function get_customer_orders_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$bunit_code = $this->security->xss_clean($this->input->post('bunit_code'));
		//$r_id_number = '1599548536-2020'; 		
		//$r_id_number = '000055-2021';
		$data['get_customer_orders'] = $this->Rider_Model->download_customer_orders_mod($r_id_number);
		//$this->load->view('json_view3',$data);
	}

	public function get_customer_orders_from_mobile_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$r_id_number = '1589165513-2020';
		$data['get_customer_orders_from_mobile'] = $this->Rider_Model->download_customer_orders_from_mobile_mod($r_id_number);
		//$this->load->view('json_view3',$data);
	}

	public function update_ontransit_status_controller()
	{
		$id = $this->security->xss_clean($this->input->post('update_intransit_status'));
		$data['update_intransit_status'] = $this->Rider_Model->update_intransit_status_mod($id);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function update_delivery_status_controller()
	{
		if (isset($_POST['update_delivered_status'])) {
			$id = $this->security->xss_clean($this->input->post('update_delivered_status'));
			$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
			$payment_platform = $this->security->xss_clean($this->input->post('payment_platform'));
			$data['update_delivery_status'] = $this->Rider_Model->update_delivery_status_mod($id, $r_id_num, $payment_platform);
		}
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function update_cancelled_status_controller()
	{
		if (isset($_POST['update_cancelled_status'])) {
			$id = $this->security->xss_clean($this->input->post('update_cancelled_status'));
			$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
			$this->Rider_Model->update_cancelled_status_mod($id, $r_id_num);
			$this->Rider_Model->update_delivery_status_mod($id, $r_id_num);
		}
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_history_items_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$r_id_number = '1589165513-2020';
		//$r_id_number = '1589156321-2020';
		echo $this->Rider_Model->download_history_items_mod($r_id_number);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_history_items_from_mobile_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		//$bunit_code = $this->security->xss_clean($this->input->post('bunit_code'));
		// $r_id_number = '1591425990-2020';
		// $bunit_code = '1';
		echo $this->Rider_Model->download_history_items_from_mobile_mod($r_id_number);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_reports_delivered_items_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$delevered_status = $this->security->xss_clean($this->input->post('delevered_status'));
		$selected_date = $this->security->xss_clean($this->input->post('selected_date'));
		// $r_id_number = '1589156321-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-6-29';
		echo $this->Rider_Model->download_reports_items_mod($r_id_number, $delevered_status, $selected_date);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_reports_cancelled_items_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$delevered_status = $this->security->xss_clean($this->input->post('delevered_status'));
		$selected_date = $this->security->xss_clean($this->input->post('selected_date'));
		// $r_id_number = '1589156321-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-6-29';
		echo $this->Rider_Model->download_cancelled_reports_items_mod($r_id_number, $delevered_status, $selected_date);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_reports_cancelled_items_from_mobile_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$delevered_status = $this->security->xss_clean($this->input->post('delevered_status'));
		$selected_date = $this->security->xss_clean($this->input->post('selected_date'));
		// $r_id_number = '1589156321-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-6-29';
		echo $this->Rider_Model->get_reports_cancelled_items_from_mobile_controller_mod($r_id_number, $delevered_status, $selected_date);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_reports_delivered_items_from_mobile_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$delevered_status = $this->security->xss_clean($this->input->post('delevered_status'));
		$selected_date = $this->security->xss_clean($this->input->post('selected_date'));
		// $r_id_number = '1589156321-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-6-23';
		echo $this->Rider_Model->download_reports_items_from_mobile_mod($r_id_number, $delevered_status, $selected_date);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function get_items_breakdown_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '000001-2020';
		// $ticket_id = '201027-3-002';
		echo $this->Rider_Model->download_transaction_view_items_mod($r_id_number, $ticket_id);
	}

	public function get_items_breakdown_from_mobile_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		//$bunit_code = $this->security->xss_clean($this->input->post('bunit_code'));
		// $r_id_number = '1589156321-2020';
		// $ticket_id = '1591431227-85';
		// $bunit_code = '1';
		echo $this->Rider_Model->download_transaction_view_items_from_mobile_mod($r_id_number, $ticket_id);
	}

	public function get_customer_details_controller()
	{
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $cus_id = '91';
		//$cus_id = '3';
		echo $this->Rider_Model->get_customer_details_mod($ticket_id);
	}

	public function get_customer_details_from_mobile_controller()
	{
		$cus_id = $this->security->xss_clean($this->input->post('customer_id'));
		$details_type = $this->security->xss_clean($this->input->post('details_type'));

		// $cus_id = '85';
		// $details_type = 'signup1';

		if ($details_type == "signup") {
			echo $this->Rider_Model->get_customer_details_from_mobile_mod($cus_id);
		} else {
			echo $this->Rider_Model->get_customer_details_from_mobile_mod2($cus_id);
		}
	}

	public function get_items_breakdown_total_by_tenant_controller()
	{
		$tenant = $this->security->xss_clean($this->input->post('tenant'));
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '1589156321-2020';
		// $ticket_id = '1-1589356163-3';
		// $tenant = 'CHOWKING - ICM';
		echo $this->Rider_Model->download_transaction_view_items_total_by_tenant_mod($r_id_number, $ticket_id, $tenant);
	}

	public function get_timeframe_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '1589156321-2020';
		// $ticket_id = '1-1589356163-3';
		echo $this->Rider_Model->get_timeframe_mod($r_id_number, $ticket_id);
	}

	public function get_tenant_timeframe_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '1589156321-2020';
		// $ticket_id = '190620-421100';
		$this->Rider_Model->get_tenant_timeframe_mod($r_id_number, $ticket_id);
	}

	public function get_tenant_timeframe2_controller()
	{
		$r_id_number = $this->security->xss_clean($this->input->post('r_id_num'));
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		// $r_id_number = '1589156321-2020';
		// $ticket_id = '190620-421100';
		$this->Rider_Model->get_tenant_timeframe2_mod($r_id_number, $ticket_id);
	}

	public function verify_old_password_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$r_id_number = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('r_id_num'))));
		$old_pass = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('old_pass'))));

		// $r_id_number = '000001-2020';
		// $old_pass = '@Aaaaaa3333331';

		// $r_id_number = '1588727695-2020';
		// $old_pass = 'rider-2020';
		echo $this->Rider_Model->verify_old_password_mod($r_id_number, $old_pass);
	}

	public function change_password_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$r_id_number = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('r_id_num'))));
		$old_pass = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('old_pass'))));
		$new_pass = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('new_pass'))));
		// $r_id_number = '1588727695-2020';
		// $old_pass = 'rider-2021';
		// $new_pass = 'rider-2020';
		echo $this->Rider_Model->change_password_mod($r_id_number, $old_pass, $new_pass);
	}

	public function update_viewed_status_controller()
	{
		$ticket_id = $this->security->xss_clean($this->input->post('ticket_id'));
		$data['update_viewed_status'] = $this->Rider_Model->update_viewed_status_mod($ticket_id);
		//$this->load->view('json/pages/json_index8',$data);
	}

	public function view_rider_details_controller()
	{
		$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
		//$r_id_num = '1589156321-2020';
		echo $this->Rider_Model->view_rider_details_mod($r_id_num);
	}

	public function count_transactions_and_history_controller()
	{
		$r_id_num = $this->security->xss_clean($this->input->post('r_id_num'));
		//$r_id_num = '1589156321-2020';
		$this->Rider_Model->count_transactions_and_history_mod($r_id_num);
	}

	public function save_image_controller()
	{

		$ticket = $this->security->xss_clean($this->input->post('ticket'));
		$ticket_id = $this->Rider_Model->get_addons_ticket_mod($ticket);

		$discount_id = $this->security->xss_clean($this->input->post('discount_id'));
		$discount_type = $this->security->xss_clean($this->input->post('discount_type'));
		$image = $this->security->xss_clean($this->input->post('imageString'));
		$this->Rider_Model->save_image_mod($ticket_id, $discount_id, $discount_type, $image);
		$this->Rider_Model->save_image_name_mod($ticket_id, $discount_id, $discount_type);
	}

	public function get_addons_breakdown_controller()
	{
		// $ticket = '201030-1-003';
		// $product_name = '1pc. Burgersteak with shanghai and drinks';
		// $tco_id = '26';

		$ticket = $this->security->xss_clean($this->input->post('ticket'));
		$product_name = $this->security->xss_clean($this->input->post('product_name'));
		$tco_id = $this->security->xss_clean($this->input->post('tco_id'));

		$ticket_id = $this->Rider_Model->get_addons_ticket_mod($ticket);
		$product_id = $this->Rider_Model->get_addons_product_mod($product_name);
		$this->Rider_Model->get_addons_breakdown_mod($tco_id);
	}

	public function get_discount_type_controller()
	{

		$ticket = $this->security->xss_clean($this->input->post('ticket_id'));
		//$ticket = '210125-1-002';

		$this->Rider_Model->get_discount_type_mod($ticket);
	}

	public function update_confirmed_status_controller()
	{
		$ticket = $this->security->xss_clean($this->input->post('ticket_id'));
		$discount_desc = $this->security->xss_clean($this->input->post('discount_desc'));
		$customer_discount_id = $this->security->xss_clean($this->input->post('customer_discount_id'));
		// $ticket = '201106-1-002';
		// $discount_desc = 'Senior Citizen';
		// $customer_discount_id = '29';

		$discount_id = $this->Rider_Model->get_discount_id_mod($discount_desc);
		$ticket_id = $this->Rider_Model->get_addons_ticket_mod($ticket);

		// echo 'Discount ID: '. $discount_id;
		// echo 'Ticket ID: '. $ticket_id;
		// echo 'Customer Discount ID: '. $customer_discount_id;

		$this->Rider_Model->update_confirmed_status_mod($discount_id, $ticket_id, $customer_discount_id);
	}

	public function update_discount_cancelled_status_controller()
	{
		$ticket = $this->security->xss_clean($this->input->post('ticket_id'));
		$discount_desc = $this->security->xss_clean($this->input->post('discount_desc'));
		$customer_discount_id = $this->security->xss_clean($this->input->post('customer_discount_id'));
		// $ticket = '201106-1-002';
		// $discount_desc = 'Senior Citizen';
		// $customer_discount_id = '29';

		$discount_id = $this->Rider_Model->get_discount_id_mod($discount_desc);
		$ticket_id = $this->Rider_Model->get_addons_ticket_mod($ticket);

		// echo 'Discount ID: '. $discount_id;
		// echo 'Ticket ID: '. $ticket_id;
		// echo 'Customer Discount ID: '. $customer_discount_id;

		$this->Rider_Model->update_discount_cancelled_status_mod($discount_id, $ticket_id, $customer_discount_id);
	}

	public function submit_discount_controller()
	{
		$ticket = $this->security->xss_clean($this->input->post('ticket_id'));
		//$discount = $this->security->xss_clean($this->input->post('discount'));

		// $ticket = '210217-1-001';
		$ticket_id = $this->Rider_Model->get_addons_ticket_mod($ticket);

		$this->Rider_Model->update_riders_discount_mod($ticket_id);
		$this->Rider_Model->update_customer_discount_statuses($ticket_id);
		//$this->Rider_Model->update_change($discount, $ticket_id);
	}

	// public function test_controller()
	// {
	// 	define('SECRET_KEY','SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
	// 	define('SECRET_IV','T1g994xo2UAqG81M');
	// 	define('ENCRYPT_METHOD','AES-256-CBC');

	// 	$a = "VDFnOTk0eG8yVUFxRzgxTc51QLe+mFC/XtiZvl1W9js=";
	// 	echo $this->decrypt($a) . "<br/>";
	// 	$b = "��S�n*y��([Q�?{wala";
	// 	echo $this->encrypt($b);
	// 	//echo $this->remove_char($this->decrypt($a));
	// }

	public function update_password_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$username = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('username'))));
		$password = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('password'))));
		// $username = '00001-2020';
		// $password = '@Aaaaaa11111111';

		$this->Rider_Model->update_password_mod($username, $password);
	}

	public function search_otp_controller()
	{
		define('SECRET_KEY', 'SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		define('SECRET_IV', 'T1g994xo2UAqG81M');
		define('ENCRYPT_METHOD', 'AES-256-CBC');

		$et_otp = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('otp'))));
		$et_username = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('username'))));
		// $et_otp = '443480';
		// $et_username = '00001-2020';

		$otp_count = $this->Rider_Model->match_otp_code($et_otp, $et_username);


		if ($otp_count > 0) {
			$this->Rider_Model->update_rider_status($et_username);
			$this->Rider_Model->update_otp_status($et_otp);
		}
	}

	public function search_credential_controller()
	{
		// define('SECRET_KEY','SoAxVBnw8PYHzHHTFBQdG0MFCLNdmGFf');
		// define('SECRET_IV','T1g994xo2UAqG81M');
		// define('ENCRYPT_METHOD','AES-256-CBC');

		$et_username = $this->remove_char($this->decrypt($this->security->xss_clean($this->input->post('et_username'))));
		//$et_username = '00001-2020';
		$rider_id = $this->Rider_Model->get_rider_id($et_username);
		$my_number = $this->Rider_Model->get_rider_mobile_no($et_username);


		if ($my_number != "" && $rider_id != "") {

			$data = array();
			$data_result = array();
			$otp_num = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
			$apicode = 'PR-ALTUR166130_RHH2A';
			$passwd = '9)h!tc%#y$';

			//Save data to user_verification_codes table...
			$this->Rider_Model->save_user_verification_codes($rider_id, $my_number, $otp_num);

			$msg_result = array();
			$input_param =  strpos(base64_decode(base64_decode($my_number)), '@');

			$message =  "AltuRush - Delivery: TO RECOVER YOUR ACCOUNT, use OTP " . $otp_num . ".";
			$otp_code = $this->itexmo($my_number, $message, $apicode, $passwd);
		}
	}

	public function itexmo($number, $message, $apicode, $passwd)
	{
		$ch = curl_init();
		$itexmo = array('1' => $number, '2' => $message, '3' => $apicode, 'passwd' => $passwd);
		curl_setopt($ch, CURLOPT_URL, "https://www.itexmo.com/php_api/api.php");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			http_build_query($itexmo)
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
		curl_close($ch);
	}

	public function get_tickets_ctrl()
	{

		// $
		// $this->Rider_Model->get_tickets_mod();
	}


	// public function search_credential1(Request $request)
	//    {
	//        $data = array();
	//        $data_result = array();
	//        $otp_num = substr(number_format(time() * rand(), 0, '', ''), 0, 6);
	//        $apicode = 'PR-ALTUR166130_RHH2A';
	//        $passwd = '9)h!tc%#y$';
	//        $otp_api = new sms_verification();
	//        $msg_result = array();
	//        $input_param =  strpos(base64_decode(base64_decode($request->data_input)), '@');
	//        if ($input_param) {
	//            $data = User::where(['email' => base64_decode(base64_decode($request->data_input))])->select('id', 'mobile_number')->get()->first();
	//            if (!$data) {
	//                $data = User::where(['username' => base64_decode(base64_decode($request->data_input))])->select('id', 'mobile_number')->get()->first();
	//                if (!$data) {
	//                    $data = User::where(['mobile_number' => str_replace('+63', '0', base64_decode(base64_decode($request->data_input)))])->select('id', 'mobile_number')->get()->first();
	//                    if (!$data) {
	//                        $data = User::where(['mobile_number' => str_replace('63', '0', base64_decode(base64_decode($request->data_input)))])->select('id', 'mobile_number')->get()->first();
	//                    }
	//                }
	//            }
	//        } else {
	//            $data = User::where(['username' => base64_decode(base64_decode($request->data_input))])->select('id', 'mobile_number')->get()->first();
	//            if (!$data) {
	//                $data = User::where(['mobile_number' => str_replace('+63', '0', base64_decode(base64_decode($request->data_input)))])->select('id', 'mobile_number')->get()->first();
	//                if (!$data) {
	//                    $data = User::where(['mobile_number' => str_replace('63', '0', base64_decode(base64_decode($request->data_input)))])->select('id', 'mobile_number')->get()->first();
	//                }
	//            }
	//        }
	//        if ($data) {
	//            $num = UserRecoveryCode::where(['user_id' => $data->id, 'status' => false])->whereDate('created_at', date('Y-m-d'))->get();
	//            if (count($num) > 3) {
	//                $msg_result = ['msg' => 'Oops, you have reached the maximum amount of request, please try again tommarrow or contact us.', 'status' => 'info'];
	//            } else {
	//                $data_result = UserRecoveryCode::create([
	//                    'user_id' => $data->id,
	//                    'contact_num' => $data->mobile_number,
	//                    'otp_code' => $otp_num
	//                ]);
	//                $message =  "AltuRush - Delivery: TO RECOVER YOUR ACCOUNT, use OTP " . $data_result->otp_code . ".";
	//                $otp_api->itexmo($data_result->contact_num, $message, $apicode, $passwd);
	//                $msg_result = ['msg' => base64_encode(base64_encode(json_encode(['msg_1' => base64_encode(base64_encode(json_encode(['id' => $data_result->id]))), 'msg_2' => 'OTP Recovery code has been sent. Please wait for a moment.']))), 'status' => 'success'];
	//            }
	//        } else {
	//            $msg_result = ['msg' => 'Unfound credential, please check & try again.', 'status' => 'info'];
	//        }
	//        return response()->json($msg_result);
	//    }



}
