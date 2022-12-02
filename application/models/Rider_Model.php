<?php
class Rider_Model extends CI_Model
{
	private static $OPENSSL_CIPHER_NAME = "aes-128-cbc"; //Name of OpenSSL Cipher 
	private static $CIPHER_KEY_LEN = 16; //128 bits


	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Manila');
	}

	public function validate_login_with_security_mod($user, $password)
	{
		$arr = array();
		$arr_data = array();

		$this->db->select('*');
		$this->db->from('toms_riders_data');
		$this->db->where('username', $user);
		$this->db->where('password', md5($password));
		$query1 = $this->db->get();
		$username_and_password_status = $query1->num_rows();

		$this->db->select('*');
		$this->db->from('toms_riders_data');
		$this->db->where('username', $user);
		$query2 = $this->db->get();
		$username_status = $query2->num_rows();

		$this->db->select('*');
		$this->db->from('toms_riders_data');
		$this->db->where('username', $user);
		$this->db->where('rider_block_status', '1');
		$query3 = $this->db->get();
		$blocked_status = $query3->num_rows();

		if ($blocked_status > 0) {
			$arr_data[] = "Account blocked";
		} else {
			if ($username_and_password_status > 0) {
				$this->update_online_status($user);
				$arr_data[] = "Success";
			} else if ($username_status > 0) {
				$arr_data[] = "Incorrect password";
			} else {
				$arr_data[] = "Account doesnt exist";
			}
		}

		echo json_encode($arr_data);
	}

	public function update_online_status($username)
	{
		$this->db->set('online_status', '1');
		$this->db->where('username', $username);
		$this->db->update('toms_riders_data');
	}

	public function validate_login_mod($user, $password)
	{
		$result = array();
		$this->db->select('*');
		$this->db->from('toms_riders_data');
		$this->db->where('username', $user);
		$this->db->where('password', md5($password));
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['id']);
			$arr_data[] = trim($value['username']);
			$arr_data[] = trim($value['password']);
			$arr_data[] = trim($value['r_firstname']);
			$arr_data[] = trim($value['r_lastname']);
			$arr_data[] = trim($value['r_picture']);
			$arr_data[] = trim($value['r_id_num']);
			//$arr_data[] = trim($value['bunit_code']);
			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function check_connection_mod()
	{
		echo "success!";
	}

	public function download_customer_orders_mod($r_id_number)
	{
		//$r_id_number = '000045-2020';
		$result = array();
		$this->db->select("tco.id as tco_id, tkts.ticket as tkts_ticket, tkts.id as tkts_id, cdi.id as cdi_id,cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, 
			cdi.mobile_number as cdi_mobile_number, b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, 
			group_concat(DISTINCT(fp.product_name)) as fp_product_name, ttr.delevered_status as ttr_delevered_status, 
			ttr.cancelled_status as ttr_cancelled_status, ttr.complete_status as ttr_complete_status, tkts.type as tkts_type, ttr.created_at as ttr_created_at,
			cb.amount as cb_amount, cb.change as cb_change, lbu.acroname as lbu_acroname, lt.tenant as lt_tenant, lt.tenant_id as lt_tenant_id, ptr.main_rider_stat as ptr_main_rider_stat, cb.delivery_charge as cb_delivery_charge, 
			ttr.view_status as ttr_view_status, epd.payment_gateway as epd_payment_gateway,
			(SELECT sum(total_price) FROM toms_customer_orders tco1 INNER JOIN tickets tkts1 ON tco1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as tco_total_price,
		    (SELECT SUM(ttr1.trans_status) FROM toms_tag_riders ttr1 INNER JOIN tickets tkts2 ON ttr1.ticket_id = tkts2.id WHERE tkts2.ticket = tkts_ticket) as trans_status_sum,
	        (SELECT COUNT(ttr2.trans_status) FROM toms_tag_riders ttr2 INNER JOIN tickets tkts3 ON ttr2.ticket_id = tkts3.id WHERE tkts3.ticket = tkts_ticket) as trans_status_count,
			(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders ttr4 INNER JOIN tickets tkts4 ON ttr4.ticket_id = tkts4.id WHERE tkts4.ticket = tkts_ticket) as complete_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 INNER JOIN tickets tkts5 ON ttr3.ticket_id = tkts5.id WHERE tkts5.ticket = tkts_ticket) as complete_status_count,
			(SELECT group_concat(' ', ct.container_type,' - ',ctd.quantity) FROM fd_customer_order_details customer_details 
				INNER JOIN fd_container_type_details as ctd ON customer_details.ticket_id = ctd.ticket_id
				INNER JOIN fd_container_types as ct ON ctd.container_id = ct.id 
				WHERE customer_details.ticket_id = tkts_id AND customer_details.tenant_id = lt_tenant_id) as num_pack,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider,
			(SELECT SUM(c_d_a.rider_discount) FROM customer_discounted_amounts as c_d_a WHERE c_d_a.ticket_id = tkts_id) as cda_discounted_amount2,
			(SELECT csi.instructions FROM customer_special_instructions csi WHERE csi.tenant_id = '25' AND csi.ticket_id = tkts_id) as csi_instructions,
			(SELECT cds.submit_status FROM customer_discount_statuses cds WHERE cds.ticket_id = tkts_id limit 1) as cds_submit_status,
			(SELECT lt1.tenant FROM fd_customer_order_details fcod1
					INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
					INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
					INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
					WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
					GROUP BY lt1.tenant limit 1) as lt_last_tenant,
			(SELECT lbu1.acroname FROM fd_customer_order_details fcod1
					INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
					INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
					INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
					WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
					GROUP BY lt1.tenant limit 1) as lbu_acroname_final
			");
		$this->db->from('toms_riders_data as trd');
		$this->db->join('toms_tag_riders as ttr', 'ttr.rider_id = trd.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('tickets as tkts', 'tkts.id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_orders as tco', 'tco.ticket_id = tkts.id', 'inner');
		$this->db->join('fd_products as fp', 'fp.product_id = tco.product_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = tkts.id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('fd_customer_order_details as cod', 'cod.ticket_id = tkts.id', 'inner');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = cod.tenant_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code', 'inner');
		$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
		$this->db->join('ticket_payment_methods as tpm', 'tpm.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('payment_methods as epd', 'epd.id = tpm.payment_method_id', 'left');
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by("ttr.ticket_id");

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {

			if ($value['trans_status_sum'] == $value['trans_status_count']) {
				$ttr_trans_status = 1;
			} else {
				$ttr_trans_status = 0;
			}

			if ($value['complete_status_sum'] != $value['complete_status_count']) {

				$arr_data = array();
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['cdi_id']);
				$arr_data[] = trim($value['cdi_firstname']);
				$arr_data[] = trim($value['cdi_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
				$arr_data[] = trim($value['cb_delivery_charge']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = $ttr_trans_status;
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tkts_ticket']);
				$arr_data[] = trim($value['cdi_mobile_number']);
				$arr_data[] = ($value['num_pack']) ? trim($value['num_pack']) : 'None'; //trim($value['num_pack']);
				$arr_data[] = trim($value['cdi_land_mark']);
				$arr_data[] = trim($value['tkts_type']);
				$arr_data[] = trim($value['ttr_created_at']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = "(" . trim($value['lbu_acroname_final']) . "-" . trim($value['lt_last_tenant']) . ")";
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = trim($value['count_rider']);
				$arr_data[] = ($value['csi_instructions']) ? trim($value['csi_instructions']) : 'None';
				$arr_data[] = $value['cds_submit_status'];
				$arr_data[] = $value['epd_payment_gateway'];

				array_push($arr, $arr_data);
			}
		}

		echo json_encode($arr);
	}

	// public function download_customer_orders_mod($r_id_number)
	// {
	// 	//$r_id_number = '000045-2020';
	// 	$result = array();


	// 	$this->db->select("tco.id as tco_id, tkts.ticket as tkts_ticket, tkts.id as tkts_id, cdi.id as cdi_id,cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, 
	// 		cdi.mobile_number as cdi_mobile_number, b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, 
	// 		group_concat(DISTINCT(fp.product_name)) as fp_product_name, ttr.delevered_status as ttr_delevered_status, 
	// 		ttr.cancelled_status as ttr_cancelled_status, ttr.complete_status as ttr_complete_status, tkts.type as tkts_type, ttr.created_at as ttr_created_at,
	// 		cb.amount as cb_amount, cb.change as cb_change, lbu.acroname as lbu_acroname, lt.tenant as lt_tenant, lt.tenant_id as lt_tenant_id, ptr.main_rider_stat as ptr_main_rider_stat, cb.delivery_charge as cb_delivery_charge, 
	// 		ttr.view_status as ttr_view_status, epd.payment_platform as epd_payment_platform,

	// 		(SELECT sum(total_price) FROM toms_customer_orders tco1 INNER JOIN tickets tkts1 ON tco1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as tco_total_price,
	// 	    (SELECT SUM(ttr1.trans_status) FROM toms_tag_riders ttr1 INNER JOIN tickets tkts2 ON ttr1.ticket_id = tkts2.id WHERE tkts2.ticket = tkts_ticket) as trans_status_sum,
	//         (SELECT COUNT(ttr2.trans_status) FROM toms_tag_riders ttr2 INNER JOIN tickets tkts3 ON ttr2.ticket_id = tkts3.id WHERE tkts3.ticket = tkts_ticket) as trans_status_count,
	// 		(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders ttr4 INNER JOIN tickets tkts4 ON ttr4.ticket_id = tkts4.id WHERE tkts4.ticket = tkts_ticket) as complete_status_sum,
	// 		(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 INNER JOIN tickets tkts5 ON ttr3.ticket_id = tkts5.id WHERE tkts5.ticket = tkts_ticket) as complete_status_count,

	// 		(SELECT group_concat(' ', ct.container_type,' - ',ctd.quantity) FROM fd_customer_order_details customer_details 
	// 			INNER JOIN fd_container_type_details as ctd ON customer_details.ticket_id = ctd.ticket_id
	// 			INNER JOIN fd_container_types as ct ON ctd.container_id = ct.id 
	// 			WHERE customer_details.ticket_id = tkts_id AND customer_details.tenant_id = lt_tenant_id) as num_pack,


	// 		(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider,
	// 		(SELECT SUM(c_d_a.rider_discount) FROM customer_discounted_amounts as c_d_a WHERE c_d_a.ticket_id = tkts_id) as cda_discounted_amount2,
	// 		(SELECT csi.instructions FROM customer_special_instructions csi WHERE csi.tenant_id = '25' AND csi.ticket_id = tkts_id) as csi_instructions,
	// 		(SELECT cds.submit_status FROM customer_discount_statuses cds WHERE cds.ticket_id = tkts_id limit 1) as cds_submit_status,
	// 		(SELECT lt1.tenant FROM fd_customer_order_details fcod1
	// 				INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
	// 				INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
	// 				INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
	// 				WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
	// 				GROUP BY lt1.tenant limit 1) as lt_last_tenant,
	// 		(SELECT lbu1.acroname FROM fd_customer_order_details fcod1
	// 				INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
	// 				INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
	// 				INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
	// 				WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
	// 				GROUP BY lt1.tenant limit 1) as lbu_acroname_final
	// 		");

	// 	$this->db->from('toms_riders_data as trd');
	// 	$this->db->join('toms_tag_riders as ttr', 'ttr.rider_id = trd.id', 'inner');
	// 	$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
	// 	$this->db->join('tickets as tkts', 'tkts.id = ttr.ticket_id', 'inner');
	// 	$this->db->join('toms_customer_orders as tco', 'tco.ticket_id = tkts.id', 'inner');
	// 	$this->db->join('fd_products as fp', 'fp.product_id = tco.product_id', 'inner');
	// 	$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = tkts.id', 'inner');
	// 	$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
	// 	$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
	// 	$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
	// 	$this->db->join('fd_customer_order_details as cod', 'cod.ticket_id = tkts.id', 'inner');
	// 	$this->db->join('locate_tenants as lt', 'lt.tenant_id = cod.tenant_id', 'inner');
	// 	$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code', 'inner');
	// 	$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
	// 	$this->db->join('e_payment_details as epd', 'epd.ticket_id = ttr.ticket_id', 'left');

	// 	$this->db->where('trd.r_id_num', $r_id_number);
	// 	$this->db->where('tco.canceled_status', '0');
	// 	$this->db->where('tco.status', '1');
	// 	$this->db->group_by("ttr.ticket_id");

	// 	$query = $this->db->get();
	// 	$data = $query->result_array();

	// 	$arr = array();
	// 	foreach ($data as $value) {

	// 		if ($value['trans_status_sum'] == $value['trans_status_count']) {
	// 			$ttr_trans_status = 1;
	// 		} else {
	// 			$ttr_trans_status = 0;
	// 		}

	// 		if ($value['complete_status_sum'] != $value['complete_status_count']) {

	// 			$arr_data = array();
	// 			$arr_data[] = trim($value['tco_id']);
	// 			$arr_data[] = trim($value['cdi_id']);
	// 			$arr_data[] = trim($value['cdi_firstname']);
	// 			$arr_data[] = trim($value['cdi_lastname']);
	// 			$arr_data[] = trim($value['b_brgy_name']);
	// 			$arr_data[] = trim($value['t_town_name']);
	// 			$arr_data[] = trim($value['fp_product_name']);
	// 			$arr_data[] = trim($value['tco_total_price']);
	// 			$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
	// 			$arr_data[] = trim($value['cb_delivery_charge']);
	// 			$arr_data[] = trim($value['ttr_view_status']);
	// 			$arr_data[] = $ttr_trans_status;
	// 			$arr_data[] = trim($value['ttr_delevered_status']);
	// 			$arr_data[] = trim($value['ttr_cancelled_status']);
	// 			$arr_data[] = trim($value['ttr_complete_status']);
	// 			$arr_data[] = trim($value['tkts_ticket']);
	// 			$arr_data[] = trim($value['cdi_mobile_number']);
	// 			$arr_data[] = ($value['num_pack']) ? trim($value['num_pack']) : 'None'; //trim($value['num_pack']);
	// 			$arr_data[] = trim($value['cdi_land_mark']);
	// 			$arr_data[] = trim($value['tkts_type']);
	// 			$arr_data[] = trim($value['ttr_created_at']);
	// 			$arr_data[] = trim($value['cb_amount']);
	// 			$arr_data[] = trim($value['cb_change']);
	// 			$arr_data[] = "(" . trim($value['lbu_acroname_final']) . "-" . trim($value['lt_last_tenant']) . ")";
	// 			$arr_data[] = trim($value['ptr_main_rider_stat']);
	// 			$arr_data[] = trim($value['count_rider']);
	// 			$arr_data[] = ($value['csi_instructions']) ? trim($value['csi_instructions']) : 'None';
	// 			$arr_data[] = $value['cds_submit_status'];
	// 			$arr_data[] = $value['epd_payment_platform'];

	// 			array_push($arr, $arr_data);
	// 		}
	// 	}

	// 	echo json_encode($arr);
	// }

	public function download_customer_orders_from_mobile_mod($r_id_number)
	{
		$result = array();

		$this->db->select("tco.id as tco_id, tco.ticket_id as tco_ticket_id, tcd.id as tcd_id, tcd.firstname as tcd_firstname, tcd.lastname as tcd_lastname, b.brgy_name as b_brgy_name, 
			t.town_name t_town_name, tco.land_mark as ca_land_mark, group_concat(DISTINCT(fp.product_name)) as fp_product_name, 
			(SELECT sum(total_price) FROM toms_customer_orders WHERE ticket_id = tco_ticket_id AND canceled_status = '0' AND status = '1' GROUP BY ticket_id) as tco_total_price, 
			ttr.view_status as ttr_view_status,ttr.trans_status as ttr_trans_status,
			(SELECT SUM(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_sum,
			(SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count,
			 ttr.delevered_status as ttr_delevered_status, ttr.cancelled_status as ttr_cancelled_status,ttr.complete_status as ttr_complete_status, 
			 tco.mobile_no as cn_mobile_number, (select SUM(num_pack) FROM fd_customer_order_details as customer_details 
			WHERE customer_details.ticket_id = tco_ticket_id GROUP BY customer_details.ticket_id) as num_pack, dc.charge_amt as dc_charge_amt, 
			tco.order_from as tco_order_from, ttr.created_at as ttr_created_at, cb.amount as cb_amount, cb.change as cb_change,
			(SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(Select COUNT(r_id_num) FROM partial_tag_riders where ticket_id = tco_ticket_id) as count_rider,
			ptr.main_rider_stat as ptr_main_rider_stat");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id');
		$this->db->join('towns as t', 't.town_id = tco.town_id');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'mobile_app');
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {


			if ($value['trans_status_sum'] == $value['trans_status_count']) {
				$ttr_trans_status = 1;
			} else {
				$ttr_trans_status = 0;
			}

			if ($value['complete_status_sum'] != $value['complete_status_count']) {
				$arr_data = array();
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['tcd_id']);
				$arr_data[] = trim($value['tcd_firstname']);
				$arr_data[] = trim($value['tcd_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = trim($value['dc_charge_amt']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = $ttr_trans_status;
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tco_ticket_id']);
				$arr_data[] = trim($value['cn_mobile_number']);
				$arr_data[] = trim($value['num_pack']);
				$arr_data[] = trim($value['ca_land_mark']);
				$arr_data[] = trim($value['tco_order_from']);
				$arr_data[] = trim($value['ttr_created_at']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = trim($value['count_rider']);

				array_push($arr, $arr_data);
			}
		}

		echo json_encode($arr);
	}

	// 	public function download_transaction_view_items_mod($r_id_number, $ticket_id)
	// 	{

	// 		// $r_id_number = '000001-2020';
	// 		// $ticket_id = '201030-1-003';
	// 		//$ticket_id = '201026-1-001';

	// 		$result = array();

	// 		$this->db->select('tco.ticket_id as tco_ticket_id, tco.id as tco_id, fp.image as fp_image, fp.product_name as fp_product_name, fp.description as fp_description,tco.product_price as fp_price, tco.product_id as tco_product_id,
	// 						   tco.quantity as tco_quantity, lt.bunit_code as lt_bunit_code, tco.id as tco_id,

	// 						   (SELECT SUM(total_price) FROM toms_customer_orders WHERE id = tco_id and ticket_id = tco_ticket_id and product_id = tco_product_id and canceled_status = "0" AND status = "1") as tco_total_price, 

	// 						   cb.delivery_charge as db_delivery_charge,
	// 						   lt.tenant as lt_tenant, lt.tenant_id as lt_tenant_id, lbu.acroname as lbu_acroname, tckts.ticket as ticket,

	// 						   (SELECT SUM(tco1.total_price) 
	// 						   	FROM toms_customer_orders tco1
	// 						   	INNER JOIN fd_products as fp1 ON tco1.product_id = fp1.product_id
	// 						   	INNER JOIN locate_tenants as lt1 ON fp1.tenant_id = lt1.tenant_id
	// 						   	WHERE lt1.tenant_id = lt_tenant_id 
	// 						   	AND tco1.ticket_id = tco_ticket_id 
	// 						   	AND lt1.bunit_code = lt_bunit_code
	// 						   	AND tco1.canceled_status = "0"
	// 						   	AND tco1.status = "1") as total_by_tenant
	// 							,(cda.rider_discount) as cda_discounted_amount2
	// ');
	// 		$this->db->from('toms_customer_orders as tco');
	// 		$this->db->join('tickets as tckts','tckts.id = tco.ticket_id');
	// 		$this->db->join('customer_bills as cb','cb.ticket_id = tckts.id');
	// 		$this->db->join('partial_tag_riders ptr','ptr.ticket_id = tco.ticket_id');
	// 		$this->db->join('toms_riders_data trd','trd.id = ptr.rider_id');
	// 		$this->db->join('toms_tag_riders as ttr','ttr.ticket_id = tco.ticket_id');
	// 		$this->db->join('fd_products as fp','tco.product_id = fp.product_id');
	// 		$this->db->join('locate_tenants as lt','fp.tenant_id = lt.tenant_id');
	// 		$this->db->join('locate_business_units as lbu','lt.bunit_code = lbu.bunit_code');
	// 		$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
	// 		$this->db->where('trd.r_id_num', $r_id_number);
	// 		$this->db->where('tckts.ticket',$ticket_id);
	// 		$this->db->where('tco.canceled_status','0');
	// 		$this->db->where('tco.status','1');
	// 		$this->db->group_by('tco_id');
	// 		$query = $this->db->get();
	// 	    $data = $query->result_array();

	// 	    $arr = Array();
	// 	 	foreach($data as $value)
	// 	 	{
	// 		 	 $arr_data= Array();
	// 			 $arr_data[] = trim($value['tco_id']);
	// 			 $arr_data[] = trim($value['fp_image']);
	// 			 $arr_data[] = trim($value['fp_product_name']);
	// 			 $arr_data[] = trim($value['fp_description']);
	// 			 $arr_data[] = trim($value['fp_price']);
	// 			 $arr_data[] = trim($value['tco_quantity']);
	// 			 $arr_data[] = trim($value['tco_total_price']);
	// 			 $arr_data[] = trim($value['db_delivery_charge']);
	// 			 $arr_data[] = trim($value['lt_tenant']);
	// 			 $arr_data[] = trim($value['lbu_acroname']);
	// 			 $arr_data[] = trim($value['total_by_tenant']);
	// 			 //$arr_data[] = trim($value['cda_discounted_amount2']);
	// 			 $arr_data[] = $arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';

	// 			array_push($arr,$arr_data);
	// 		}
	// 		echo json_encode($arr);
	// 	}

	public function download_transaction_view_items_mod($r_id_number, $ticket_id)
	{

		// $r_id_number = '000001-2020';
		// $ticket_id = '201030-1-003';
		//$ticket_id = '201026-1-001';

		$result = array();

		$this->db->select('tco.ticket_id as tco_ticket_id, tco.id as tco_id, fp.image as fp_image, fp.product_name as fp_product_name, fp.description as fp_description,tco.product_price as fp_price, tco.product_id as tco_product_id,
						   tco.quantity as tco_quantity, lt.bunit_code as lt_bunit_code, tco.id as tco_id,

						   (SELECT SUM(total_price) FROM toms_customer_orders WHERE id = tco_id and ticket_id = tco_ticket_id and product_id = tco_product_id and canceled_status = "0" AND status = "1") as tco_total_price, 
						   
						   cb.delivery_charge as db_delivery_charge,
						   lt.tenant as lt_tenant, lt.tenant_id as lt_tenant_id, lbu.acroname as lbu_acroname, tckts.ticket as ticket,

						   (SELECT SUM(tco1.total_price) 
						   	FROM toms_customer_orders tco1
						   	INNER JOIN fd_products as fp1 ON tco1.product_id = fp1.product_id
						   	INNER JOIN locate_tenants as lt1 ON fp1.tenant_id = lt1.tenant_id
						   	WHERE lt1.tenant_id = lt_tenant_id 
						   	AND tco1.ticket_id = tco_ticket_id 
						   	AND lt1.bunit_code = lt_bunit_code
						   	AND tco1.canceled_status = "0"
						   	AND tco1.status = "1") as total_by_tenant,
							
							(SELECT SUM(cda1.rider_discount) FROM customer_discounted_amounts cda1
								WHERE cda1.ticket_id = tco_ticket_id AND cda1.tenant_id = lt_tenant_id) as cda_discounted_amount2
');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('tickets as tckts', 'tckts.id = tco.ticket_id');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tckts.id');
		$this->db->join('partial_tag_riders ptr', 'ptr.ticket_id = tco.ticket_id');
		$this->db->join('toms_riders_data trd', 'trd.id = ptr.rider_id');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id');
		$this->db->join('locate_business_units as lbu', 'lt.bunit_code = lbu.bunit_code');
		$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('tckts.ticket', $ticket_id);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by('tco_id');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['fp_image']);
			$arr_data[] = trim($value['fp_product_name']);
			$arr_data[] = trim($value['fp_description']);
			$arr_data[] = trim($value['fp_price']);
			$arr_data[] = trim($value['tco_quantity']);
			$arr_data[] = trim($value['tco_total_price']);
			$arr_data[] = trim($value['db_delivery_charge']);
			$arr_data[] = trim($value['lt_tenant']);
			$arr_data[] = trim($value['lbu_acroname']);
			$arr_data[] = trim($value['total_by_tenant']);
			//$arr_data[] = trim($value['cda_discounted_amount2']);
			$arr_data[] = $arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function download_transaction_view_items_from_mobile_mod($r_id_number, $ticket_id)
	{
		$result = array();

		$this->db->select('tco.ticket_id as tco_ticket_id, tco.id as tco_id, fp.image as fp_image, fp.product_name as fp_product_name, 
						   fp.description as fp_description,fp.price as fp_price, tco.product_id as tco_product_id,
						   tco.quantity as tco_quantity, lt.bunit_code, (SELECT SUM(total_price) FROM toms_customer_orders 
						   WHERE ticket_id = tco_ticket_id and product_id = tco_product_id AND canceled_status = "0" AND status = "1") as tco_total_price, dc.charge_amt as dc_charge_amt,
						   lt.tenant as lt_tenant, lbu.acroname as lbu_acroname, 

						   (SELECT SUM(tco1.total_price) 
						   	FROM toms_customer_orders tco1 
						   	INNER JOIN fd_products as fp1 ON tco1.product_id = fp1.product_id
						   	INNER JOIN locate_tenants as lt1 ON fp1.tenant_id = lt1.tenant_id 
						   	WHERE lt1.tenant = lt.tenant AND tco1.ticket_id = tco_ticket_id AND lt1.bunit_code = lt.bunit_code) as total_by_tenant
		');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id');
		$this->db->join('partial_tag_riders ptr', 'ptr.ticket_id = tco.ticket_id');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id');
		$this->db->join('locate_business_units as lbu', 'lt.bunit_code = lbu.bunit_code');
		$this->db->join('app_users as au', 'tcd.id = au.customerId');
		$this->db->join('towns as t', 't.town_id = au.townId');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.ticket_id', $ticket_id);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by('tco_id');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['fp_image']);
			$arr_data[] = trim($value['fp_product_name']);
			$arr_data[] = trim($value['fp_description']);
			$arr_data[] = trim($value['fp_price']);
			$arr_data[] = trim($value['tco_quantity']);
			$arr_data[] = trim($value['tco_total_price']);
			$arr_data[] = trim($value['dc_charge_amt']);
			$arr_data[] = trim($value['lt_tenant']);
			$arr_data[] = trim($value['lbu_acroname']);
			$arr_data[] = trim($value['total_by_tenant']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function update_viewed_status_mod($ticket_id)
	{

		$query = $this->db->query("UPDATE toms_tag_riders as ttr
								   		INNER JOIN tickets as tckts
								   		ON ttr.ticket_id = tckts.id
								   		SET ttr.view_status = '1'
								   		AND tckts.ticket = '$ticket_id'");
	}

	public function update_intransit_status_mod($id)
	{
		$result = array();
		$this->db->set('trans_status', '1');
		$this->db->where('ticket_id', $id);
		$this->db->update('toms_tag_riders');
	}

	public function update_online_status_to_offline_model($id)
	{
		$result = array();
		$this->db->set('online_status', '0');
		$this->db->where('id', $id);
		$this->db->update('toms_riders_data');
	}

	public function remove_message_model($id)
	{
		$this->db->set('remove_status', '0');
		$this->db->where('id', $id);
		$this->db->update('messages');
	}

	public function update_delivery_status_mod($id, $r_id_num, $payment_platform)
	{

		$date_time = date('Y-m-d H:i:s');

		if ($payment_platform == "Cash on Delivery") {
			$query = $this->db->query("UPDATE toms_tag_riders as ttr
									   		INNER JOIN toms_riders_data as trd
									   		ON ttr.rider_id = trd.id
									   		INNER JOIN tickets as tckts
									   		ON tckts.id = ttr.ticket_id
									   		SET ttr.delevered_status = '1',
									   		ttr.delevered_at = '$date_time'
									   		WHERE trd.r_id_num = '$r_id_num'
									   		AND tckts.ticket = '$id'");
		} else if ($payment_platform == "Online Payment") {
			$query = $this->db->query("UPDATE toms_tag_riders as ttr
								   		INNER JOIN toms_riders_data as trd
								   		ON ttr.rider_id = trd.id
								   		INNER JOIN tickets as tckts
								   		ON tckts.id = ttr.ticket_id
								   		SET ttr.delevered_status = '1',
								   		ttr.delevered_at = '$date_time',
								   		ttr.complete_status = '1',
								   		ttr.completed_at = '$date_time',
								   		ttr.remitted_status = '1',
								   		ttr.remitted_at = '$date_time'
								   		WHERE trd.r_id_num = '$r_id_num'
								   		AND tckts.ticket = '$id'");
		} else {
			$query = $this->db->query("UPDATE toms_tag_riders as ttr
									   		INNER JOIN toms_riders_data as trd
									   		ON ttr.rider_id = trd.id
									   		INNER JOIN tickets as tckts
									   		ON tckts.id = ttr.ticket_id
									   		SET ttr.delevered_status = '1',
									   		ttr.delevered_at = '$date_time'
									   		WHERE trd.r_id_num = '$r_id_num'
									   		AND tckts.ticket = '$id'");
		}
	}

	public function update_cancelled_status_mod($id, $r_id_num)
	{

		$date_time = date('Y-m-d H:i:s');
		$query = $this->db->query("UPDATE toms_tag_riders as ttr
								   		INNER JOIN toms_riders_data as trd
								   		ON ttr.rider_id = trd.id
								   		INNER JOIN tickets as tckts
								   		ON tckts.id = ttr.ticket_id
								   		SET ttr.cancelled_status = '1',
								   		ttr.cancelled_at = '$date_time'
								   		WHERE trd.r_id_num = '$r_id_num'
								   		AND tckts.ticket = '$id'");
	}

	public function download_history_items_mod($r_id_number)
	{

		//$r_id_number = '000021-2020';

		$result = array();


		$this->db->select("tco.id as tco_id, tkts.ticket as tkts_ticket, tkts.id as tkts_id, cdi.id as cdi_id,cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, cdi.mobile_number as cdi_mobile_number,
			b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, group_concat(DISTINCT(fp.product_name)) as fp_product_name,
			(SELECT sum(total_price) FROM toms_customer_orders tco1 INNER JOIN tickets tkts1 ON tco1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as tco_total_price,
			cb.delivery_charge as cb_delivery_charge, ttr.view_status as ttr_view_status, ttr.trans_status as ttr_trans_status,
		    (SELECT SUM(ttr1.trans_status) FROM toms_tag_riders ttr1 INNER JOIN tickets tkts2 ON ttr1.ticket_id = tkts2.id WHERE tkts2.ticket = tkts_ticket) as trans_status_sum,
	        (SELECT COUNT(ttr2.trans_status) FROM toms_tag_riders ttr2 INNER JOIN tickets tkts3 ON ttr2.ticket_id = tkts3.id WHERE tkts3.ticket = tkts_ticket) as trans_status_count,
			(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders ttr4 INNER JOIN tickets tkts4 ON ttr4.ticket_id = tkts4.id WHERE tkts4.ticket = tkts_ticket) as complete_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 INNER JOIN tickets tkts5 ON ttr3.ticket_id = tkts5.id WHERE tkts5.ticket = tkts_ticket) as complete_status_count,
			ttr.delevered_status as ttr_delevered_status, ttr.cancelled_status as ttr_cancelled_status, ttr.complete_status as ttr_complete_status, epd.payment_platform as epd_payment_platform,
			
			lbu.acroname as lbu_acroname, lt.tenant as lt_tenant, lt.tenant_id as lt_tenant_id,

			(SELECT group_concat(' ', ct.container_type,' - ',ctd.quantity) FROM fd_customer_order_details customer_details 
				INNER JOIN fd_container_type_details as ctd ON customer_details.ticket_id = ctd.ticket_id
				INNER JOIN fd_container_types as ct ON ctd.container_id = ct.id 
				WHERE customer_details.ticket_id = tkts_id AND customer_details.tenant_id = lt_tenant_id) as num_pack,

			tkts.type as tkts_type, ttr.created_at as ttr_created_at, cb.amount as cb_amount, cb.change as cb_change, ptr.main_rider_stat as ptr_main_rider_stat,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider,
			(SELECT SUM(c_d_a.rider_discount) FROM customer_discounted_amounts as c_d_a WHERE c_d_a.ticket_id = tkts_id) as cda_discounted_amount2,
			(SELECT lt1.tenant FROM fd_customer_order_details fcod1
					INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
					INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
					INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
					WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
					GROUP BY lt1.tenant limit 1) as lt_last_tenant,
			(SELECT lbu1.acroname FROM fd_customer_order_details fcod1
					INNER JOIN tickets tkts8 ON tkts8.id = fcod1.ticket_id
					INNER JOIN locate_tenants lt1 ON lt1.tenant_id = fcod1.tenant_id
					INNER JOIN locate_business_units lbu1 ON lbu1.bunit_code = lt1.bunit_code
					WHERE tkts8.ticket = tkts_ticket AND fcod1.last_tenant = '1'
					GROUP BY lt1.tenant limit 1) as lbu_acroname_final
			");

		$this->db->from('toms_riders_data as trd');
		$this->db->join('toms_tag_riders as ttr', 'ttr.rider_id = trd.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('tickets as tkts', 'tkts.id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_orders as tco', 'tco.ticket_id = tkts.id', 'inner');
		$this->db->join('fd_products as fp', 'fp.product_id = tco.product_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = tkts.id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('fd_customer_order_details as cod', 'cod.ticket_id = tkts.id', 'inner');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = cod.tenant_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code', 'inner');
		$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
		$this->db->join('e_payment_details as epd', 'epd.ticket_id = ttr.ticket_id', 'left');

		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by('ttr.ticket_id');

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			if ($value['complete_status_sum'] == $value['complete_status_count']) {
				$arr_data = array();
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['cdi_id']);
				$arr_data[] = trim($value['cdi_firstname']);
				$arr_data[] = trim($value['cdi_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
				$arr_data[] = trim($value['cb_delivery_charge']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = trim($value['ttr_trans_status']);
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tkts_ticket']);
				$arr_data[] = trim($value['cdi_mobile_number']);
				$arr_data[] = trim($value['num_pack']);
				$arr_data[] = trim($value['cdi_land_mark']);
				$arr_data[] = trim($value['ttr_created_at']);
				$arr_data[] = trim($value['tkts_type']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = "(" . trim($value['lbu_acroname_final']) . "-" . trim($value['lt_last_tenant']) . ")";
				$arr_data[] = trim($value['count_rider']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = $value['epd_payment_platform'];


				array_push($arr, $arr_data);
			}
		}

		echo json_encode($arr);
	}

	public function download_history_items_from_mobile_mod($r_id_number)
	{
		$result = array();

		$this->db->select("tco.id as tco_id, tcd.id as tcd_id, tco.ticket_id as tco_ticket_id, tcd.firstname as tcd_firstname, tcd.lastname as tcd_lastname, 
			b.brgy_name as b_brgy_name, t.town_name t_town_name, tco.land_mark as ca_land_mark, 
			group_concat(DISTINCT(fp.product_name)) as fp_product_name, 
			(SELECT sum(total_price) FROM toms_customer_orders WHERE ticket_id = tco_ticket_id AND canceled_status = '0' AND status = '1' GROUP BY ticket_id)as tco_total_price, 
			ttr.view_status as ttr_view_status,ttr.trans_status as ttr_trans_status, 
			ttr.delevered_status as ttr_delevered_status, ttr.cancelled_status as ttr_cancelled_status,ttr.complete_status as ttr_complete_status,  
			tco.mobile_no as cn_mobile_number, 
			(SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count,
			(select SUM(num_pack) FROM fd_customer_order_details as customer_details WHERE customer_details.ticket_id = tco_ticket_id GROUP BY customer_details.ticket_id) as num_pack,
			dc.charge_amt as dc_charge_amt, ttr.created_at as ttr_create_at, tco.order_from as tco_order_from, cb.amount as cb_amount, 
			(SELECT COUNT(ptr1.r_id_num) FROM partial_tag_riders ptr1 WHERE ptr1.ticket_id = tco_ticket_id) as count_rider,
			cb.change as cb_change, ptr.main_rider_stat as ptr_main_rider_stat");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = tco.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'mobile_app');
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			if ($value['complete_status_sum'] == $value['complete_status_count']) {
				$arr_data = array();
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['tcd_id']);
				$arr_data[] = trim($value['tcd_firstname']);
				$arr_data[] = trim($value['tcd_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = trim($value['dc_charge_amt']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = trim($value['ttr_trans_status']);
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tco_ticket_id']);
				$arr_data[] = trim($value['cn_mobile_number']);
				$arr_data[] = trim($value['num_pack']);
				$arr_data[] = trim($value['ca_land_mark']);
				$arr_data[] = trim($value['ttr_create_at']);
				$arr_data[] = trim($value['tco_order_from']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = trim($value['count_rider']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);


				array_push($arr, $arr_data);
			}
		}

		echo json_encode($arr);
	}

	public function download_reports_items_mod($r_id_number, $delevered_status, $selected_date)
	{
		// $r_id_number = '000001-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-10-30';

		$result = array();

		$this->db->select("tco.id as tco_id, tkts.ticket as tkts_ticket, tkts.id as tkts_id, cdi.id as cdi_id,cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, cdi.mobile_number as cdi_mobile_number,
			b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, group_concat(DISTINCT(fp.product_name)) as fp_product_name,
			(SELECT sum(total_price) FROM toms_customer_orders tco1 INNER JOIN tickets tkts1 ON tco1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as tco_total_price,
			cb.delivery_charge as cb_delivery_charge, ttr.view_status as ttr_view_status, ttr.trans_status as ttr_trans_status,
		    (SELECT SUM(ttr1.trans_status) FROM toms_tag_riders ttr1 INNER JOIN tickets tkts2 ON ttr1.ticket_id = tkts2.id WHERE tkts2.ticket = tkts_ticket) as trans_status_sum,
	        (SELECT COUNT(ttr2.trans_status) FROM toms_tag_riders ttr2 INNER JOIN tickets tkts3 ON ttr2.ticket_id = tkts3.id WHERE tkts3.ticket = tkts_ticket) as trans_status_count,
			(SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 INNER JOIN tickets as tkts4 ON ttr4.ticket_id = tkts4.id WHERE tkts4.ticket = tkts_ticket) as remitted_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 INNER JOIN tickets as tkts4 ON ttr3.ticket_id = tkts4.id WHERE tkts4.ticket = tkts_ticket) as remitted_status_count,
			ttr.delevered_status as ttr_delevered_status, ttr.cancelled_status as ttr_cancelled_status, ttr.complete_status as ttr_complete_status, epd.payment_platform as epd_payment_platform,

			(SELECT group_concat(' ',ct.container_type,' - ',ctd.quantity) FROM fd_customer_order_details customer_details 
				INNER JOIN tickets tkts6 ON customer_details.ticket_id = tkts6.id 
				INNER JOIN fd_container_type_details ctd ON ctd.ticket_id = tkts6.id
				INNER JOIN fd_container_types ct ON ct.id = ctd.container_id
				WHERE tkts6.ticket = tkts_ticket GROUP BY tkts6.ticket) as num_pack,

			tkts.type as tkts_type, ttr.created_at as ttr_created_at, cb.amount as cb_amount, cb.change as cb_change, ptr.main_rider_stat as ptr_main_rider_stat,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider
			,
			(SELECT SUM(c_d_a.rider_discount) FROM customer_discounted_amounts as c_d_a WHERE c_d_a.ticket_id = tkts_id) as cda_discounted_amount2
			");


		$this->db->from('toms_riders_data as trd');
		$this->db->join('toms_tag_riders as ttr', 'ttr.rider_id = trd.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('tickets as tkts', 'tkts.id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_orders as tco', 'tco.ticket_id = tkts.id', 'inner');
		$this->db->join('fd_products as fp', 'fp.product_id = tco.product_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = tkts.id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('customer_discounted_amounts as cda', 'cda.ticket_id = ttr.ticket_id AND cda.tenant_id = ttr.tenant_id', 'left');
		$this->db->join('e_payment_details as epd', 'epd.ticket_id = ttr.ticket_id', 'left');

		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('ttr.delevered_status', $delevered_status);
		$this->db->where('date(ttr.created_at)', $selected_date);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by("tkts.ticket");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			if ($delevered_status == 1) {
				if ($value['remitted_status_sum'] != $value['remitted_status_count']) {
					$arr_data[] = trim($value['tco_id']);
					$arr_data[] = trim($value['cdi_firstname']);
					$arr_data[] = trim($value['cdi_lastname']);
					$arr_data[] = trim($value['b_brgy_name']);
					$arr_data[] = trim($value['t_town_name']);
					$arr_data[] = trim($value['fp_product_name']);
					$arr_data[] = trim($value['tco_total_price']);
					$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
					$arr_data[] = trim($value['cb_delivery_charge']);
					$arr_data[] = trim($value['ttr_view_status']);
					$arr_data[] = trim($value['ttr_trans_status']);
					$arr_data[] = trim($value['ttr_delevered_status']);
					$arr_data[] = trim($value['ttr_cancelled_status']);
					$arr_data[] = trim($value['ttr_complete_status']);
					$arr_data[] = trim($value['tkts_ticket']);
					$arr_data[] = trim($value['cdi_mobile_number']);
					$arr_data[] = trim($value['tkts_type']);
					$arr_data[] = trim($value['cb_amount']);
					$arr_data[] = trim($value['cb_change']);
					$arr_data[] = trim($value['ptr_main_rider_stat']);
					$arr_data[] = trim($value['count_rider']);
					$arr_data[] = trim($value['cb_change']);
					$arr_data[] = $value['epd_payment_platform'];
					array_push($arr, $arr_data);
				}
			} else {
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['cdi_firstname']);
				$arr_data[] = trim($value['cdi_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
				$arr_data[] = trim($value['cb_delivery_charge']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = trim($value['ttr_trans_status']);
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tkts_ticket']);
				$arr_data[] = trim($value['cdi_mobile_number']);
				$arr_data[] = trim($value['tkts_type']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = trim($value['count_rider']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = $value['epd_payment_platform'];
				array_push($arr, $arr_data);
			}
		}
		echo json_encode($arr);
	}


	public function download_cancelled_reports_items_mod($r_id_number, $delevered_status, $selected_date)
	{
		// $r_id_number = '1595207965-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-07-31';

		$result = array();

		$this->db->select("tco.id as tco_id, tco.ticket_id as tco_ticket_id, tcd.firstname as tcd_firstname, tcd.lastname as tcd_lastname, 
			b.brgy_name as b_brgy_name, t.town_name t_town_name, group_concat(fp.product_name) as fp_product_name, 
			(SELECT sum(tco2.total_price) FROM toms_customer_orders as tco2 WHERE tco2.ticket_id = tco_ticket_id GROUP BY tco2.ticket_id) as tco_total_price, 
			ttr.view_status as ttr_view_status, ttr.trans_status as ttr_trans_status, 
			ttr.delevered_status as ttr_delevered_status, ttr.complete_status as ttr_complete_status, ttr.tenant_id as ttr_tenant_id,
			cn.mobile_number as cn_mobile_number,dc.charge_amt as dc_charge_amt, tco.order_from as tco_order_from,
			(SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(SELECT SUM(ttr2.complete_status) FROM toms_tag_riders as ttr2 WHERE ttr2.ticket_id = tco_ticket_id) as remitted_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as remitted_status_count,
			cb.amount as cb_amount, cb.change as cb_change, ptr.main_rider_stat as ptr_main_rider_stat, 
			(SELECT GROUP_CONCAT(DISTINCT(tco1.tag_status_at) SEPARATOR '|') FROM fd_products as fp1
			INNER JOIN toms_customer_orders as tco1 ON tco1.product_id = fp1.product_id
			WHERE tco1.ticket_id = tco_ticket_id) as return_change_to_tenant,
			(SELECT COUNT(ptr1.r_id_num) FROM partial_tag_riders ptr1 WHERE ptr1.ticket_id = tco_ticket_id) as count_rider");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('customer_addresses as ca', 'tcd.id = ca.customer_id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('customer_numbers as cn', 'tcd.id = cn.customer_id', 'inner');
		$this->db->join('towns as t', 't.town_id = ca.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = ca.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('ttr.delevered_status', $delevered_status);
		$this->db->where('ttr.cancelled_status', '1');
		$this->db->where('date(ttr.created_at)', $selected_date);
		$this->db->where('ca.shipping', '1');
		$this->db->where('cn.in_use', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			if ($value['remitted_status_sum'] != $value['remitted_status_count']) {
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['tcd_firstname']);
				$arr_data[] = trim($value['tcd_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = trim($value['dc_charge_amt']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = trim($value['ttr_trans_status']);
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tco_ticket_id']);
				$arr_data[] = trim($value['cn_mobile_number']);
				$arr_data[] = trim($value['tco_order_from']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = trim($value['count_rider']);
				$arr_data[] = trim($value['return_change_to_tenant']);
				array_push($arr, $arr_data);
			}
		}
		echo json_encode($arr);
	}

	public function get_reports_cancelled_items_from_mobile_controller_mod($r_id_number, $delevered_status, $selected_date)
	{
		// $r_id_number = '1587970511-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-05-04';

		$result = array();

		$this->db->select("tco.id as tco_id, tco.ticket_id as tco_ticket_id, tcd.firstname as tcd_firstname, tcd.lastname as tcd_lastname, 
			b.brgy_name as b_brgy_name, t.town_name t_town_name, group_concat(fp.product_name) as fp_product_name, 
			(SELECT sum(tco2.total_price) FROM toms_customer_orders as tco2 WHERE tco2.ticket_id = tco_ticket_id GROUP BY tco2.ticket_id) as tco_total_price, 
			ttr.view_status as ttr_view_status, ttr.trans_status as ttr_trans_status, 
			ttr.delevered_status as ttr_delevered_status, ttr.complete_status as ttr_complete_status, ttr.tenant_id as ttr_tenant_id,
			cn.mobile_number as cn_mobile_number,dc.charge_amt as dc_charge_amt, tco.order_from as tco_order_from,
			(SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(SELECT SUM(ttr2.complete_status) FROM toms_tag_riders as ttr2 WHERE ttr2.ticket_id = tco_ticket_id) as remitted_status_sum,
			(SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as remitted_status_count,
			cb.amount as cb_amount, cb.change as cb_change, ptr.main_rider_stat as ptr_main_rider_stat, 
			(SELECT GROUP_CONCAT(DISTINCT(tco1.tag_status_at) SEPARATOR '|') FROM fd_products as fp1
			INNER JOIN toms_customer_orders as tco1 ON tco1.product_id = fp1.product_id
			WHERE tco1.ticket_id = tco_ticket_id) as return_change_to_tenant,
			(SELECT COUNT(ptr1.r_id_num) FROM partial_tag_riders ptr1 WHERE ptr1.ticket_id = tco_ticket_id) as count_rider");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('customer_addresses as ca', 'tcd.id = ca.customer_id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('customer_numbers as cn', 'tcd.id = cn.customer_id', 'inner');
		$this->db->join('towns as t', 't.town_id = ca.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = ca.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('ttr.delevered_status', $delevered_status);
		$this->db->where('ttr.cancelled_status', '1');
		$this->db->where('date(ttr.created_at)', $selected_date);
		$this->db->where('ca.shipping', '1');
		$this->db->where('cn.in_use', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();

			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['tcd_firstname']);
			$arr_data[] = trim($value['tcd_lastname']);
			$arr_data[] = trim($value['b_brgy_name']);
			$arr_data[] = trim($value['t_town_name']);
			$arr_data[] = trim($value['fp_product_name']);
			$arr_data[] = trim($value['tco_total_price']);
			$arr_data[] = trim($value['dc_charge_amt']);
			$arr_data[] = trim($value['ttr_view_status']);
			$arr_data[] = trim($value['ttr_trans_status']);
			$arr_data[] = trim($value['ttr_delevered_status']);
			$arr_data[] = trim($value['ttr_complete_status']);
			$arr_data[] = trim($value['tco_ticket_id']);
			$arr_data[] = trim($value['cn_mobile_number']);
			$arr_data[] = trim($value['tco_order_from']);
			$arr_data[] = trim($value['cb_amount']);
			$arr_data[] = trim($value['cb_change']);
			$arr_data[] = trim($value['ptr_main_rider_stat']);
			$arr_data[] = trim($value['count_rider']);
			$arr_data[] = trim($value['return_change_to_tenant']);
			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function download_reports_items_from_mobile_mod($r_id_number, $delevered_status, $selected_date)
	{
		// $r_id_number = '1587970511-2020';
		// $delevered_status = '1';
		// $selected_date = '2020-05-04';

		$result = array();

		$this->db->select("tco.id as tco_id, tco.ticket_id as tco_ticket_id, tcd.firstname as tcd_firstname, tcd.lastname as tcd_lastname, b.brgy_name as b_brgy_name, 
			t.town_name t_town_name, group_concat(fp.product_name) as fp_product_name, 
			(SELECT sum(tco2.total_price) FROM toms_customer_orders as tco2 WHERE tco2.ticket_id = tco_ticket_id AND canceled_status = '0' AND status = '1' GROUP BY tco2.ticket_id) as tco_total_price, 
			ttr.view_status as ttr_view_status, ttr.trans_status as ttr_trans_status, 
			ttr.delevered_status as ttr_delevered_status, ttr.cancelled_status as ttr_cancelled_status, ttr.complete_status as ttr_complete_status,  
			tco.mobile_no as cn_mobile_number,dc.charge_amt as dc_charge_amt, tco.order_from as tco_order_from, cb.amount as cb_amount, 
			cb.change as cb_change, (SELECT COUNT(ttr1.trans_status) FROM toms_tag_riders as ttr1 WHERE ttr1.ticket_id = tco_ticket_id) as trans_status_count,
			(SELECT SUM(ttr2.delevered_status) FROM toms_tag_riders as ttr2 WHERE ttr2.ticket_id = tco_ticket_id) as remitted_status_sum,
			(SELECT COUNT(ttr3.delevered_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as remitted_status_count,
			(SELECT COUNT(ptr1.r_id_num) FROM partial_tag_riders ptr1 WHERE ptr1.ticket_id = tco_ticket_id) as count_rider,
			ptr.main_rider_stat as ptr_main_rider_stat");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = tco.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = fp.tenant_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('ttr.delevered_status', $delevered_status);
		$this->db->where('date(ttr.created_at)', $selected_date);
		$this->db->where('tco.canceled_status', '0');
		$this->db->where('tco.status', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			if ($delevered_status == 1) {
				if ($value['remitted_status_sum'] == $value['remitted_status_count']) {
					$arr_data = array();
					$arr_data[] = trim($value['tco_id']);
					$arr_data[] = trim($value['tcd_firstname']);
					$arr_data[] = trim($value['tcd_lastname']);
					$arr_data[] = trim($value['b_brgy_name']);
					$arr_data[] = trim($value['t_town_name']);
					$arr_data[] = trim($value['fp_product_name']);
					$arr_data[] = trim($value['tco_total_price']);
					$arr_data[] = trim($value['dc_charge_amt']);
					$arr_data[] = trim($value['ttr_view_status']);
					$arr_data[] = trim($value['ttr_trans_status']);
					$arr_data[] = trim($value['ttr_delevered_status']);
					$arr_data[] = trim($value['ttr_cancelled_status']);
					$arr_data[] = trim($value['ttr_complete_status']);
					$arr_data[] = trim($value['tco_ticket_id']);
					$arr_data[] = trim($value['cn_mobile_number']);
					$arr_data[] = trim($value['tco_order_from']);
					$arr_data[] = trim($value['cb_amount']);
					$arr_data[] = trim($value['cb_change']);
					$arr_data[] = trim($value['ptr_main_rider_stat']);
					$arr_data[] = trim($value['count_rider']);

					array_push($arr, $arr_data);
				}
			} else {
				$arr_data = array();
				$arr_data[] = trim($value['tco_id']);
				$arr_data[] = trim($value['tcd_firstname']);
				$arr_data[] = trim($value['tcd_lastname']);
				$arr_data[] = trim($value['b_brgy_name']);
				$arr_data[] = trim($value['t_town_name']);
				$arr_data[] = trim($value['fp_product_name']);
				$arr_data[] = trim($value['tco_total_price']);
				$arr_data[] = trim($value['dc_charge_amt']);
				$arr_data[] = trim($value['ttr_view_status']);
				$arr_data[] = trim($value['ttr_trans_status']);
				$arr_data[] = trim($value['ttr_delevered_status']);
				$arr_data[] = trim($value['ttr_cancelled_status']);
				$arr_data[] = trim($value['ttr_complete_status']);
				$arr_data[] = trim($value['tco_ticket_id']);
				$arr_data[] = trim($value['cn_mobile_number']);
				$arr_data[] = trim($value['tco_order_from']);
				$arr_data[] = trim($value['cb_amount']);
				$arr_data[] = trim($value['cb_change']);
				$arr_data[] = trim($value['ptr_main_rider_stat']);
				$arr_data[] = trim($value['count_rider']);
				array_push($arr, $arr_data);
			}
		}
		echo json_encode($arr);
	}

	public function count_transactions_and_history_mod($r_id_number)
	{

		$result = array();
		$this->db->select("count(*) as trans_count_trans_web, tco.ticket_id as tco_ticket_id,
						   (SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum1,
						   (SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count1");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('customer_addresses as ca', 'tcd.id = ca.customer_id', 'inner');
		$this->db->join('customer_numbers as cn', 'tcd.id = cn.customer_id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = ca.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = ca.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'web_app');
		$this->db->where('ca.shipping', '1');
		$this->db->where('cn.in_use', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr1 = array();
		//var_dump($data);

		// if($data != null)
		// {
		foreach ($data as $value) {
			if ($value['complete_status_sum1'] != $value['complete_status_count1']) {
				$arr_data = array();
				$arr_data[] = trim($value['trans_count_trans_web']);
				array_push($arr1, $arr_data);
			} else {
				$arr_data = array();
				$arr_data[] = 0;
				array_push($arr1, $arr_data);
			}
		}
		// }
		// else
		// {
		// 	$arr_data= Array();
		// 	$arr_data[] = 0;
		// 	array_push($arr1, $arr_data);
		// }

		$result = array();

		$this->db->select("count(*) as trans_count_trans_mobile, tco.ticket_id as tco_ticket_id,
						   (SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum2,
						   (SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count2");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = tco.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'mobile_app');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		//var_dump($data);
		$arr2 = array();
		// if($data != null)
		// {
		foreach ($data as $value) {

			if ($value['complete_status_sum2'] != $value['complete_status_count2']) {
				$arr_data = array();
				$arr_data[] = trim($value['trans_count_trans_mobile']);
				array_push($arr2, $arr_data);
			} else {
				$arr_data = array();
				$arr_data[] = 0;
				array_push($arr2, $arr_data);
			}
		}
		// }
		// else
		// {
		// 	$arr_data= Array();
		// 	$arr_data[] = 0;
		// 	array_push($arr2, $arr_data);
		// }


		$result = array();

		$this->db->select("count(*) as trans_count_history_web, tco.ticket_id as tco_ticket_id,
						   (SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum3,
						   (SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count3");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('customer_addresses as ca', 'tcd.id = ca.customer_id', 'inner');
		$this->db->join('customer_numbers as cn', 'tcd.id = cn.customer_id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = ca.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = ca.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		//$this->db->join('e_commerce.locate_tenants as lt','fp.tenant_id = lt.tenant_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'web_app');
		$this->db->where('ca.shipping', '1');
		$this->db->where('cn.in_use', '1');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();

		$arr3 = array();
		// if($data != null)
		// {
		foreach ($data as $value) {
			var_dump($value['complete_status_sum3']);
			var_dump($value['complete_status_count3']);
			if ($value['complete_status_sum3'] == $value['complete_status_count3']) {
				$arr_data = array();
				$arr_data[] = trim($value['trans_count_history_web']);
				array_push($arr3, $arr_data);
			} else {
				$arr_data = array();
				$arr_data[] = 0;
				array_push($arr3, $arr_data);
			}
		}
		// }
		// else
		// {
		// 	$arr_data= Array();
		// 	$arr_data[] = 0;
		// 	array_push($arr3, $arr_data);
		// }

		$result = array();

		$this->db->select("count(*) as trans_count_history_mobile, tco.ticket_id as tco_ticket_id,
						   (SELECT SUM(ttr4.complete_status) FROM toms_tag_riders as ttr4 WHERE ttr4.ticket_id = tco_ticket_id) as complete_status_sum4,
						   (SELECT COUNT(ttr3.complete_status) FROM toms_tag_riders as ttr3 WHERE ttr3.ticket_id = tco_ticket_id) as complete_status_count4");
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id', 'inner');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id', 'inner');
		$this->db->join('towns as t', 't.town_id = tco.town_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id', 'inner');
		$this->db->join('tbl_delivery_charges as dc', 't.town_id = dc.town_id', 'inner');
		//$this->db->join('e_commerce.locate_tenants as lt','fp.tenant_id = lt.tenant_id', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.order_from', 'mobile_app');
		$this->db->group_by("ttr.ticket_id");
		$query = $this->db->get();
		$data = $query->result_array();
		//var_dump($data);
		// var_dump($value['complete_status_sum3']);
		// var_dump($value['complete_status_count3']);

		$arr4 = array();
		// if($data != null)
		// {
		foreach ($data as $value) {

			if ($value['complete_status_sum4'] == $value['complete_status_count4']) {
				$arr_data = array();
				$arr_data[] = trim($value['trans_count_history_mobile']);
				array_push($arr4, $arr_data);
			} else {
				$arr_data = array();
				$arr_data[] = 0;
				array_push($arr4, $arr_data);
			}
		}
		// }
		// else
		// {
		// 	$arr_data= Array();
		// 	$arr_data[] = 0;
		// 	array_push($arr4, $arr_data);
		// }

		// var_dump($arr1);
		// var_dump($arr2);
		// var_dump($arr3);
		// var_dump($arr4);

		$arr5 = array();
		$count1[] = count($arr1) + count($arr2);
		$count[] = count($arr3) + count($arr4);
		array_push($arr5, $count1);
		array_push($arr5, $count);
		echo json_encode($arr5);
	}

	public function get_customer_details_from_mobile_mod($cus_id)
	{
		$result = array();

		$this->db->select("cd.id as cd_id, cd.firstname as cd_firstname, au.contactNum as au_contactNum,cd.lastname as cd_lastname, au.contactNum as contactNum, 
			b.brgy_name as barangay_name, t.town_name as town_name");
		$this->db->from('toms_customer_details as cd');
		$this->db->join('app_users as au', 'au.customerId = cd.id');
		$this->db->join('barangays as b', 'b.brgy_id = au.brgId');
		$this->db->join('towns as t', 't.town_id = au.townId');
		$this->db->where('cd.id', $cus_id);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['cd_id']);
			$arr_data[] = trim($value['cd_firstname']) . ' ' . $value['cd_lastname'];
			$arr_data[] = trim($value['au_contactNum']);
			$arr_data[] = "";
			$arr_data[] = "";
			$arr_data[] = trim($value['barangay_name']);
			$arr_data[] = trim($value['town_name']);
			$arr_data[] = "";

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function get_customer_details_from_mobile_mod2($cus_id)
	{
		$result = array();

		$this->db->select("cd.id as cd_id, cd.firstname as cd_firstname, tco.mobile_no as tco_mobile_no, cd.lastname as cd_lastname, 
			b.brgy_name as barangay_name, t.town_name as town_name, tco.land_mark as tco_land_mark");
		$this->db->from('toms_customer_details as cd');
		$this->db->join('toms_customer_orders as tco', 'tco.customer_id = cd.id');
		$this->db->join('app_users as au', 'au.customerId = cd.id');
		$this->db->join('barangays as b', 'b.brgy_id = tco.barangay_id');
		$this->db->join('towns as t', 't.town_id = tco.town_id');
		$this->db->where('cd.id', $cus_id);
		$this->db->group_by('tco.customer_id');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['cd_id']);
			$arr_data[] = trim($value['cd_firstname']) . ' ' . $value['cd_lastname'];
			$arr_data[] = trim($value['tco_mobile_no']);
			$arr_data[] = "";
			$arr_data[] = "";
			$arr_data[] = trim($value['barangay_name']);
			$arr_data[] = trim($value['town_name']);
			$arr_data[] = trim($value['tco_land_mark']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function get_customer_details_mod($ticket_id)
	{
		$result = array();

		$this->db->select("cdi.id as cdi_id, cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, 	cdi.mobile_number as cdi_mobile_number,
			cdi.complete_address as cdi_complete_address, cdi.street_purok as cdi_street_purok, b.brgy_name as b_brgy_name, t.town_name as t_town_name,
			cdi.land_mark as cdi_land_mark
			");

		$this->db->from('customer_delivery_infos as cdi');
		$this->db->join('tickets as tckts', 'tckts.id = cdi.ticket_id');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id');
		$this->db->join('towns as t', 't.town_id = b.town_id');
		$this->db->where('tckts.ticket', $ticket_id);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['cdi_id']);
			$arr_data[] = trim($value['cdi_firstname']) . ' ' . $value['cdi_lastname'];
			$arr_data[] = trim($value['cdi_mobile_number']);
			$arr_data[] = trim($value['cdi_complete_address']);
			$arr_data[] = trim($value['cdi_street_purok']);
			$arr_data[] = trim($value['b_brgy_name']);
			$arr_data[] = trim($value['t_town_name']);
			$arr_data[] = trim($value['cdi_land_mark']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function download_transaction_view_items_total_by_tenant_mod($r_id_number, $ticket_id, $tenant)
	{
		$result = array();

		$this->db->select('SUM(tco.total_price) as tco_total_price');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id');
		$this->db->join('toms_customer_details as tcd', 'tco.customer_id = tcd.id');
		$this->db->join('fd_products as fp', 'tco.product_id = fp.product_id');
		$this->db->join('locate_tenants as lt', 'fp.tenant_id = lt.tenant_id');
		$this->db->join('locate_business_units as lbu', 'lt.bunit_code = lbu.bunit_code');
		$this->db->join('customer_addresses as ca', 'tcd.id = ca.customer_id');
		$this->db->join('towns as t', 't.town_id = ca.town_id');
		$this->db->join('barangays as b', 'b.brgy_id = ca.barangay_id');
		$this->db->where('ttr.r_id_num', $r_id_number);
		$this->db->where('tco.ticket_id', $ticket_id);
		$this->db->where('lt.tenant', $tenant);
		$this->db->where('ca.shipping', '1');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_total_price']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function get_timeframe_mod($r_id_number, $ticket_id)
	{
		$result = array();

		$this->db->select('tco.id as tco_id, tco.prepared_at as tco_prepared_at, tco.tag_status_at as tco_tag_status_at, 
			tco.r_setup_stat_at  as tco_r_setup_stat, ttr.trans_at as ttr_trans_at, ttr.delevered_at as ttr_delevered_at');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'ttr.ticket_id = tco.ticket_id');
		$this->db->join('partial_tag_riders ptr', 'ptr.ticket_id = tco.ticket_id');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('tco.ticket_id', $ticket_id);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['tco_prepared_at']);
			$arr_data[] = trim($value['tco_tag_status_at']);
			$arr_data[] = trim($value['tco_r_setup_stat']);
			$arr_data[] = trim($value['ttr_trans_at']);
			$arr_data[] = trim($value['ttr_delevered_at']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function get_tenant_timeframe_mod($r_id_number, $ticket_id)
	{
		$result = array();

		$this->db->select('tco.id as tco_id, tco.created_at as tco_created_at, 
			ttr.`ticket_id` as tc,ttr.tenant_id as tid,
			(SELECT tco1.tag_status_at FROM toms_customer_orders tco1
 			INNER JOIN fd_products fp ON tco1.product_id = fp.product_id
 			INNER JOIN locate_tenants lt1 ON fp.tenant_id = lt1.tenant_id
 			WHERE tco1.ticket_id = tc AND lt1.tenant_id = tid  GROUP BY tco1.tag_status_at) as tStatus,
			tco.r_setup_stat_at  as tco_r_setup_stat, ttr.trans_at as ttr_trans_at, ttr.delevered_at as ttr_delevered_at, 
			lt.tenant_id as lt_tenant_id, lbu.acroname as lbu_acroname, lt.tenant as lt_tenant');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'tco.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = ttr.tenant_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code', 'inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('ptr.ticket_id', $ticket_id);
		$this->db->group_by('ttr.tenant_id');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['tco_created_at']);
			$arr_data[] = trim($value['tStatus']);
			$arr_data[] = trim($value['tco_r_setup_stat']);
			$arr_data[] = trim($value['ttr_trans_at']);
			$arr_data[] = trim($value['ttr_delevered_at']);
			$arr_data[] = trim($value['lt_tenant_id']);
			$arr_data[] = trim($value['lbu_acroname']);
			$arr_data[] = trim($value['lt_tenant']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}


	public function get_tenant_timeframe2_mod($r_id_number, $ticket_id)
	{

		// $r_id_number = '000001-2020';
		// $ticket_id = '201006-3-001';

		$result = array();

		$this->db->select('tco.id as tco_id, tco.prepared_at as tco_prepared_at, 
			ttr.`ticket_id` as tc,ttr.tenant_id as tid,

			(SELECT tco1.tag_status_at FROM toms_customer_orders tco1
 			INNER JOIN fd_products fp ON tco1.product_id = fp.product_id
 			INNER JOIN locate_tenants lt1 ON fp.tenant_id = lt1.tenant_id
 			WHERE tco1.ticket_id = tc AND lt1.tenant_id = tid  GROUP BY tco1.tag_status_at) as tStatus,

			tco.r_setup_stat_at  as tco_r_setup_stat, ttr.trans_at as ttr_trans_at, ttr.delevered_at as ttr_delevered_at, 
			lt.tenant_id as lt_tenant_id, lbu.acroname as lbu_acroname, lt.tenant as lt_tenant, lt.logo as lt_logo');
		$this->db->from('toms_customer_orders as tco');
		$this->db->join('toms_tag_riders as ttr', 'tco.ticket_id = ttr.ticket_id', 'left');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('tickets as tckts', 'tckts.id = ptr.ticket_id', 'inner');
		$this->db->join('toms_riders_data as trd', 'trd.id = ptr.rider_id', 'inner');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = ttr.tenant_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code', 'inner');
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('tckts.ticket', $ticket_id);
		$this->db->where('tco.canceled_status',	'0');
		$this->db->where('tco.status', '1');
		$this->db->group_by('ttr.tenant_id');
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['tco_id']);
			$arr_data[] = trim($value['tco_prepared_at']);
			$arr_data[] = trim($value['tStatus']);
			$arr_data[] = trim($value['tco_r_setup_stat']);
			$arr_data[] = trim($value['ttr_trans_at']);
			$arr_data[] = trim($value['ttr_delevered_at']);
			$arr_data[] = trim($value['lt_tenant_id']);
			$arr_data[] = trim($value['lbu_acroname']);
			$arr_data[] = trim($value['lt_tenant']);
			$arr_data[] = trim($value['lt_logo']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function verify_old_password_mod($r_id_number, $old_pass)
	{
		$result = array();

		$this->db->select('*');
		$this->db->from('toms_riders_data as trd');
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('trd.password', md5($old_pass));
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['id']);
			$arr_data[] = trim($value['r_id_num']);
			$arr_data[] = trim($value['r_firstname']);
			$arr_data[] = trim($value['r_lastname']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function change_password_mod($r_id_number, $old_pass, $new_pass)
	{
		$this->db->set('trd.password', md5($new_pass));
		$this->db->where('trd.password', md5($old_pass));
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->update('toms_riders_data as trd');
	}

	public function view_rider_details_mod($r_id_num)
	{

		// $r_id_num = '1599548536-2020'; 	

		$result = array();

		$this->db->select('trd.r_id_num r_id_num, trd.r_firstname as r_firstname, trd.r_lastname as r_lastname, trd.r_birth_date as r_birth_date,
			trd.r_gender as r_gender, trd.r_address as r_address, trd.r_mobile as r_mobile, trd.r_license as r_license, trd.r_other_details as r_other_details,
			trd.rm_brand as rm_brand, trd.rm_model as rm_model, trd.rm_color as rm_color, trd.rm_plate_num as rm_plate_num, trd.rm_other_d as rm_other_d,
			trd.username as username, trd.created_at as created_at');
		$this->db->from('toms_riders_data as trd');
		$this->db->where('trd.r_id_num', $r_id_num);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['r_id_num']);
			//$arr_data[] = trim($value['r_id_num']);1
			//$arr_data[] = trim($value['r_picture']);2
			$arr_data[] = trim($value['r_firstname']);
			$arr_data[] = trim($value['r_lastname']);
			$arr_data[] = trim($value['r_birth_date']);
			$arr_data[] = trim($value['r_gender']);
			$arr_data[] = trim($value['r_address']);
			$arr_data[] = trim($value['r_mobile']);
			$arr_data[] = trim($value['r_license']);
			$arr_data[] = trim($value['r_other_details']);
			//$arr_data[] = trim($value['r_zone']);
			//$arr_data[] = trim($value['rm_picture']);12
			$arr_data[] = trim($value['rm_brand']);
			$arr_data[] = trim($value['rm_model']);
			$arr_data[] = trim($value['rm_color']);
			$arr_data[] = trim($value['rm_plate_num']);
			$arr_data[] = trim($value['rm_other_d']);
			//$arr_data[] = trim($value['business_unit']);
			$arr_data[] = trim($value['username']);
			//$arr_data[] = trim($value['password']);
			//$arr_data[] = trim($value['status']);17
			//$arr_data[] = trim($value['rider_stat']);18
			$arr_data[] = trim($value['created_at']);
			//$arr_data[] = trim($value['updated_at']);

			array_push($arr, $arr_data);
		}
		echo json_encode($arr);
	}

	public function save_image_mod($ticket_id, $discount_id, $discount_type, $image)
	{

		$encodedData = str_replace(' ', '+', $image);
		$decodedData = base64_decode($encodedData);
		file_put_contents(APPPATH . '../uploads/' . $discount_id . '.PNG', $decodedData);
		//file_put_contents('../storetenant.alturush.com/storage/uploads/discount_ids/' . $discount_id . '.PNG',$decodedData);
		// return;

	}

	public function save_image_name_mod($ticket_id, $discount_id, $discount_type)
	{

		// $result = array();
		// $this->db->join('customer_discounts cd','cds.id=cd.customer_discount_storage_id');
		// $this->db->set('cds.image_path', $discount_id . '.PNG');
		// $this->db->where('cd.id', $discount_id);
		// $this->db->update('customer_discount_storages cds');

		$this->db->query("UPDATE customer_discount_storages cds JOIN customer_discounts cd on cds.id=cd.customer_discount_storage_id
			SET cds.image_path='uploads/" . $discount_id . ".PNG'
			WHERE cd.id='$discount_id'");

		// $this->db->query("UPDATE customer_discount_storages cds JOIN customer_discounts cd on cds.id=cd.customer_discount_storage_id
		// 	SET cds.image_path='storage/uploads/discount_ids".$discount_id.".PNG'
		// 	WHERE cd.id='$discount_id'");
	}

	public function get_addons_ticket_mod($ticket)
	{

		$result = array();

		$this->db->select('tckts.id as tckts_id');
		$this->db->from('tickets as tckts');
		$this->db->where('tckts.ticket', $ticket);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr;
		foreach ($data as $value) {
			$arr = trim($value['tckts_id']);
		}
		return $arr;
	}

	public function update_confirmed_status_mod($discount_id, $ticket_id, $customer_discount_id)
	{
		$result = array();
		$this->db->set('cds.rider_status', '1');
		$this->db->set('cds.cancelled_status', '0');
		$this->db->where('cds.ticket_id', $ticket_id);
		$this->db->where('cds.discount_id', $discount_id);
		$this->db->where('cds.customer_discount_id', $customer_discount_id);
		$this->db->update('customer_discount_statuses as cds');
	}

	public function update_discount_cancelled_status_mod($discount_id, $ticket_id, $customer_discount_id)
	{
		$result = array();
		$this->db->set('cds.rider_status', '1');
		$this->db->set('cds.cancelled_status', '1');
		$this->db->where('cds.ticket_id', $ticket_id);
		$this->db->where('cds.discount_id', $discount_id);
		$this->db->where('cds.customer_discount_id', $customer_discount_id);
		$this->db->update('customer_discount_statuses as cds');
	}

	public function get_discount_id_mod($discount_name)
	{

		$result = array();

		$this->db->select('dl.id as dl_id');
		$this->db->from('discount_lists as dl');
		$this->db->where('dl.discount_name', $discount_name);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr;
		foreach ($data as $value) {
			$arr = trim($value['dl_id']);
		}
		return $arr;
	}

	public function get_addons_product_mod($product_name)
	{

		$result = array();

		$this->db->select('fp.product_id as fp_product_id');
		$this->db->from('fd_products as fp');
		$this->db->where('fp.product_name', $product_name);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr;
		foreach ($data as $value) {
			$arr = trim($value['fp_product_id']);
		}
		return $arr;
	}

	// public function get_addons_order_mod($ticket_id, $product_id, $tco_id)
	// {

	// 	$result = array();

	// 	$this->db->select('tco.id as tco_id');
	// 	$this->db->from('toms_customer_orders as tco');
	// 	$this->db->where('tco.ticket_id', $ticket_id);
	// 	$this->db->where('tco.product_id', $product_id);
	// 	$query = $this->db->get();
	//     $data = $query->result_array();

	// 	$arr;
	//  	foreach($data as $value)
	//  	{
	// 		 $arr = trim($value['tco_id']); 
	// 	}
	// 	return $arr;
	// }

	public function get_addons_breakdown_mod($order_id)
	{

		$this->db->select('fp.product_name as fp_product_name, tcoa.addon_price as tcoa_addon_price');
		$this->db->from('toms_customer_order_addons as tcoa');
		$this->db->join('fd_products as fp', 'fp.product_id = tcoa.addon_id', 'inner');
		$this->db->where('tcoa.order_id', $order_id);
		$query = $this->db->get();
		$data1 = $query->result_array();

		$arr = array();
		foreach ($data1 as $value) {
			$arr_data1 = array();
			$arr_data1[] = trim($value['tcoa_addon_price']);
			$arr_data1[] = trim($value['fp_product_name']);
			array_push($arr, $arr_data1);
		}

		$this->db->select('fp.product_name as fp_product_name, tcoc.addon_price as tcoc_addon_price');
		$this->db->from('toms_customer_order_choices as tcoc');
		$this->db->join('fd_products as fp', 'fp.product_id = tcoc.choice_id', 'inner');
		$this->db->where('tcoc.order_id', $order_id);
		$query = $this->db->get();
		$data = $query->result_array();

		foreach ($data as $value) {
			$arr_data2 = array();
			$arr_data2[] = trim($value['tcoc_addon_price']);
			$arr_data2[] = trim($value['fp_product_name']);
			array_push($arr, $arr_data2);
		}

		$this->db->select('fs.suggestion as fs_suggestion, tcos.addon_price as tcos_addon_price');
		$this->db->from('toms_customer_order_suggestions as tcos');
		$this->db->join('fd_suggestions as fs', 'fs.id = tcos.suggestion_id', 'inner');
		$this->db->where('tcos.order_id', $order_id);
		$query = $this->db->get();
		$data = $query->result_array();

		foreach ($data as $value) {
			$arr_data3 = array();
			$arr_data3[] = trim($value['tcos_addon_price']);
			$arr_data3[] = trim($value['fs_suggestion']);
			array_push($arr, $arr_data3);
		}

		echo json_encode($arr);
	}

	public function get_discount_type_mod($ticket_id)
	{
		//$ticket_id = '210111-1-006';

		$this->db->select('cd.id as cd_id, dl.discount_name as dl_discount_name, cds.name as cds_name, 
						   cds.id_number as cds_id_number, cdst.rider_status as cdst_rider_status, 
						   cdst.cancelled_status as cdst_cancelled_status, cdst.submit_status as cdst_submit_status,
						   cds.image_path as cds_image_path');
		$this->db->from('tickets as tckts');
		$this->db->join('customer_discounts as cd', 'tckts.id = cd.ticket_id', 'inner');
		$this->db->join('customer_discount_statuses as cdst', 'cd.id = cdst.customer_discount_id', 'inner');
		$this->db->join('customer_discount_storages as cds', 'cds.id = cd.customer_discount_storage_id', 'inner');
		$this->db->join('discount_lists as dl', 'dl.id = cds.discount_id', 'inner');
		$this->db->where('tckts.ticket', $ticket_id);
		$this->db->where('cdst.status', '1');
		$this->db->group_by("cds.id");

		$query = $this->db->get();
		$data1 = $query->result_array();

		$arr = array();
		foreach ($data1 as $value) {
			$arr_data1 = array();
			$arr_data1[] = trim($value['cd_id']);
			$arr_data1[] = trim($value['dl_discount_name']);
			$arr_data1[] = trim($value['cds_name']);
			$arr_data1[] = trim($value['cds_id_number']);
			$arr_data1[] = trim($value['cdst_rider_status']);
			$arr_data1[] = trim($value['cdst_cancelled_status']);
			$arr_data1[] = trim($value['cdst_submit_status']);
			$arr_data1[] = trim($value['cds_image_path']);
			array_push($arr, $arr_data1);
		}

		echo json_encode($arr);
	}



	public function update_riders_discount_mod($t_id)
	{
		$acc_change = 0;
		$acc_change2 = 0;
		$final_acc_change = 0;
		$result = $this->db->query("SELECT tenant_id as cds_tenant_id, ticket_id as cds_ticket_id, discount_id as cds_discount_id, COUNT(rider_status) AS RIDER_STATUS_COUNT, SUM(rider_status) AS RIDER_STATUS_SUM, SUM(cancelled_status) as CANCELLED_STATUS_SUM FROM customer_discount_statuses WHERE ticket_id = $t_id AND status = '1' GROUP BY tenant_id, discount_id")->result();
		// $result = $this->db->select()->from('customer_discount_statuses')->where(array('ticket_id' => $t_id))->get()->result();
		$data = [];
		$discount_amt = 0;
		$discount_amt_per_discount = 0;

		foreach ($result as $key => $value) {
			$RIDER_STATUS_SUM_FINAL = $value->RIDER_STATUS_SUM - $value->CANCELLED_STATUS_SUM;
			//$a = 1;

			$result2 = $this->db->query("SELECT discount, no_approved FROM customer_discounted_amounts WHERE ticket_id = $value->cds_ticket_id AND discount_id = $value->cds_discount_id AND tenant_id = $value->cds_tenant_id")->result();
			foreach ($result2 as $key2 => $value2) {

				$discount_amt_per_discount = $value2->discount / $value2->no_approved;
				$discount_amt = $discount_amt + ($discount_amt_per_discount * $value->CANCELLED_STATUS_SUM);

				$updated_rider_discount = 1;
				$diff_no_approved = 0;

				if ($RIDER_STATUS_SUM_FINAL > 0) {
					$diff_no_approved =  $value2->no_approved / $RIDER_STATUS_SUM_FINAL;
				}


				if ($diff_no_approved >= 1) {
					// if($value->RIDER_STATUS_SUM == 0)
					// {
					// 	$updated_rider_discount = 0.00;
					// }
					// else
					// {
					$updated_rider_discount = $value2->discount / $diff_no_approved;
					$acc_change = $value2->discount - $updated_rider_discount;
					$acc_change2 = $value2->discount - $acc_change;
					$final_acc_change = $final_acc_change + $acc_change2;
					// echo $value2->discount;
					// echo $updated_rider_discount;
					// }
					//echo $a.'a';
				} else {
					//echo $a.'c';
					$updated_rider_discount = 0;
				}
				//echo $diff_no_approved;
				//$a++;
			}

			$data = array(
				'rider_no_approved' => $RIDER_STATUS_SUM_FINAL,
				'rider_discount' => $updated_rider_discount
			);

			$this->update_customer_discounted_amounts($value->cds_tenant_id, $value->cds_ticket_id, $value->cds_discount_id, $data);
		}
		$discount_amount = $this->get_discount_amount($t_id);
		$total_price = $this->get_total_price($t_id);
		$riders_fee = $this->get_riders_fee($t_id);
		$grand_total = ($total_price + $riders_fee) - $discount_amount;

		$tender_amount = $this->get_tender_amount($t_id);

		$change = $tender_amount - $grand_total;

		$this->update_change($change, $t_id);
	}

	public function get_tender_amount($ticket_id)
	{
		$result = $this->db->query("SELECT amount FROM customer_bills WHERE ticket_id = $ticket_id")->result();
		$amount = 0;
		foreach ($result as $key => $value) {
			$amount = $value->amount;
		}
		return $amount;
	}

	public function get_riders_fee($ticket_id)
	{
		$result = $this->db->query("SELECT delivery_charge FROM customer_bills WHERE ticket_id = $ticket_id")->result();
		$delivery_charge = 0;
		foreach ($result as $key => $value) {
			$delivery_charge = $value->delivery_charge;
		}
		return $delivery_charge;
	}

	public function get_total_price($ticket_id)
	{
		$result = $this->db->query("SELECT SUM(total_price) as sum_of_total_price FROM toms_customer_orders WHERE ticket_id = $ticket_id AND canceled_status = '0'")->result();
		$sum_of_total_price = 0;
		foreach ($result as $key => $value) {
			$sum_of_total_price = $value->sum_of_total_price;
		}
		return $sum_of_total_price;
	}

	public function get_discount_amount($ticket_id)
	{
		$result = $this->db->query("SELECT SUM(rider_discount) as total_rider_discount FROM customer_discounted_amounts WHERE ticket_id = $ticket_id")->result();
		$total_rider_discount = 0;
		foreach ($result as $key => $value) {
			$total_rider_discount = $value->total_rider_discount;
		}
		return $total_rider_discount;
	}

	public function update_change($change, $ticket_id)
	{
		$this->db->set('change', $change);
		$this->db->where('ticket_id', $ticket_id);
		$this->db->where('last_tenant', '1');
		$this->db->update('fd_customer_order_details');
	}

	public function update_customer_discounted_amounts($tenant_id, $ticket_id, $discount_id, $data)
	{
		//var_dump($data);
		$this->db->where('ticket_id', $ticket_id);
		$this->db->where('tenant_id', $tenant_id);
		$this->db->where('discount_id', $discount_id);
		$this->db->update('customer_discounted_amounts', $data);
	}

	public function update_customer_discount_statuses($ticket_id)
	{
		$this->db->set('submit_status', '1');
		$this->db->where('ticket_id', $ticket_id);
		$this->db->update('customer_discount_statuses');
	}

	public function save_message($from_id, $message, $to_id, $receiver_user_type, $ticket_id)
	{
		$date_time = date('Y-m-d H:i:s');

		$data = array(
			'contact_type_from' => 'RIDER',
			'from_id' => $from_id,
			'contact_type_to' => $receiver_user_type,
			'to_id' => $to_id,
			'body' => $message,
			'attachment' => '',
			'seen' => '0',
			'seen_at' => $date_time,
			'created_at' => $date_time,
			'ticket_id' => $ticket_id,
			'updated_at' => $date_time
		);
		$this->db->insert('messages', $data);
	}

	public function get_user_id($user_name, $final_user_type)
	{
		if ($final_user_type == "CSR") {
			$this->db->select('u.id as u_id');
			$this->db->from('users as u');
			$this->db->where('u.name', $user_name);
			$this->db->where('u.user_type', 'CS_1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['u_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "RC") {
			$this->db->select('u.id as u_id');
			$this->db->from('users as u');
			$this->db->where('u.name', $user_name);
			$this->db->where('u.user_type', 'RC');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['u_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "TENANT") {
			$this->db->select('tu.id as tu_id');
			$this->db->from('tenant_users as tu');
			$this->db->where('tu.name', $user_name);
			$this->db->where('tu.active', '1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['tu_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "CUSTOMER") {
			$this->db->select('au.customer_id as au_id');
			$this->db->from('app_users as au');
			$this->db->where('CONCAT(au.firstname, " " ,au.lastname) LIKE "%' . $user_name . '%"');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['au_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "RIDER") {

			$this->db->select('trd.id as trd_id');
			$this->db->from('toms_riders_data as trd');
			$this->db->where('CONCAT(trd.r_firstname, " " ,trd.r_lastname) LIKE "%' . $user_name . '%"');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['trd_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "OTHERS") {
		}
	}

	public function get_user_id_using_ticket($user_name, $final_user_type)
	{
		if ($final_user_type == "CSR") {
			$this->db->select('u.id as u_id');
			$this->db->from('users as u');
			$this->db->where('u.name', $user_name);
			$this->db->where('u.user_type', 'CS_1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['u_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "RC") {
			$this->db->select('u.id as u_id');
			$this->db->from('users as u');
			$this->db->where('u.name', $user_name);
			$this->db->where('u.user_type', 'RC');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['u_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "TENANT") {
			$this->db->select('tu.id as tu_id');
			$this->db->from('tenant_users as tu');
			$this->db->where('tu.name', $user_name);
			$this->db->where('tu.active', '1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['tu_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "CUSTOMER") {
			$this->db->select('tckts.customer_id as au_id');
			$this->db->from('tickets as tckts');
			$this->db->where('tckts.ticket', $user_name);

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['au_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "RIDER") {

			$this->db->select('trd.id as trd_id');
			$this->db->from('toms_riders_data as trd');
			$this->db->where('CONCAT(trd.r_firstname, " " ,trd.r_lastname) LIKE "%' . $user_name . '%"');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			$arr_data = "";

			foreach ($data as $value) {
				$arr_data = trim($value['trd_id']);
			}

			return $arr_data;
		} else if ($final_user_type == "OTHERS") {
		}
	}

	public function update_seen_status($receiver_id, $rider_id, $user_type)
	{
		$date_time = date('Y-m-d H:i:s');

		if ($user_type == "CSR") {
			$this->db->set('m.seen', '1');
			$this->db->set('m.seen_at', $date_time);
			$this->db->where("(m.from_id=$receiver_id AND m.to_id=$rider_id)", NULL, FALSE);
			$this->db->where("(m.contact_type_to = 'RIDER' AND m.contact_type_from = 'CSR')");
			$this->db->where('m.seen', "0");
			$this->db->update('messages as m');
		} else if ($user_type == "RC") {
			$this->db->set('m.seen', '1');
			$this->db->set('m.seen_at', $date_time);
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'RC') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'RC'))");
			$this->db->where('m.seen', "0");
			$this->db->update('messages as m');
		} else if ($user_type == "TENANT") {
			$this->db->set('m.seen', '1');
			$this->db->set('m.seen_at', $date_time);
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'TENANT') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'TENANT'))");
			$this->db->where('m.seen', "0");
			$this->db->update('messages as m');
		} else if ($user_type == "CUSTOMER") {
			$this->db->set('m.seen', '1');
			$this->db->set('m.seen_at', $date_time);
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'CUSTOMER') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'CUSTOMER'))");
			$this->db->where('m.seen', "0");
			$this->db->update('messages as m');
		} else if ($user_type == "RIDER") {
			$this->db->set('m.seen', '1');
			$this->db->set('m.seen_at', $date_time);
			$this->db->where("((m.to_id=$receiver_id OR m.to_id=$rider_id) AND (m.from_id=$receiver_id OR m.from_id=$rider_id))", NULL, FALSE);
			$this->db->where('m.contact_type_from', 'RIDER');
			$this->db->where('m.contact_type_to', 'RIDER');
			$this->db->where('m.seen', "0");
			$this->db->update('messages as m');
		} else {
			echo "Others";
		}
	}

	public function retrieve_message($receiver_id, $rider_id, $user_type)
	{

		if ($user_type == "CSR") {
			//echo"Customer Service";

			$this->db->select('m.id as m_id, m.contact_type_from as m_contact_type_from, m.from_id as m_from_id, 
				m.contact_type_to as m_contact_type_to, m.to_id as m_to_id, body as m_body,
				m.attachment as m_attachment, m.remove_status as m_remove_status, m.seen as m_seen, m.seen_at as m_seen_at, m.created_at as m_created_at, m.updated_at as m_updated_at');
			$this->db->from('messages as m');
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'CSR') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'CSR'))");
			$this->db->order_by('m.id', 'asc');

			$query = $this->db->get();
			$data1 = $query->result_array();

			$arr = array();
			foreach ($data1 as $value) {
				$arr_data1 = array();
				$arr_data1[] = trim($value['m_id']);
				$arr_data1[] = trim($value['m_contact_type_from']);
				$arr_data1[] = trim($value['m_from_id']);
				$arr_data1[] = trim($value['m_contact_type_to']);
				$arr_data1[] = trim($value['m_to_id']);
				$arr_data1[] = trim($value['m_body']);
				$arr_data1[] = trim($value['m_attachment']);
				$arr_data1[] = trim($value['m_remove_status']);
				$arr_data1[] = trim($value['m_seen']);
				$arr_data1[] = trim($value['m_seen_at']);
				$arr_data1[] = trim($value['m_created_at']);
				$arr_data1[] = trim($value['m_updated_at']);
				array_push($arr, $arr_data1);
			}

			echo json_encode($arr);
		} else if ($user_type == "RC") {
			$this->db->select('m.id as m_id, m.contact_type_from as m_contact_type_from, m.from_id as m_from_id, 
				m.contact_type_to as m_contact_type_to, m.to_id as m_to_id, body as m_body,
				m.attachment as m_attachment, m.remove_status as m_remove_status, m.seen as m_seen, m.seen_at as m_seen_at, m.created_at as m_created_at, m.updated_at as m_updated_at');
			$this->db->from('messages as m');
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'RC') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'RC'))");
			$this->db->order_by('m.id', 'asc');

			$query = $this->db->get();
			$data1 = $query->result_array();

			$arr = array();
			foreach ($data1 as $value) {
				$arr_data1 = array();
				$arr_data1[] = trim($value['m_id']);
				$arr_data1[] = trim($value['m_contact_type_from']);
				$arr_data1[] = trim($value['m_from_id']);
				$arr_data1[] = trim($value['m_contact_type_to']);
				$arr_data1[] = trim($value['m_to_id']);
				$arr_data1[] = trim($value['m_body']);
				$arr_data1[] = trim($value['m_attachment']);
				$arr_data1[] = trim($value['m_remove_status']);
				$arr_data1[] = trim($value['m_seen']);
				$arr_data1[] = trim($value['m_seen_at']);
				$arr_data1[] = trim($value['m_created_at']);
				$arr_data1[] = trim($value['m_updated_at']);
				array_push($arr, $arr_data1);
			}

			echo json_encode($arr);
		} else if ($user_type == "TENANT") {
			$this->db->select('m.id as m_id, m.contact_type_from as m_contact_type_from, m.from_id as m_from_id, 
				m.contact_type_to as m_contact_type_to, m.to_id as m_to_id, body as m_body,
				m.attachment as m_attachment, m.remove_status as m_remove_status, m.seen as m_seen, m.seen_at as m_seen_at, m.created_at as m_created_at, m.updated_at as m_updated_at');
			$this->db->from('messages as m');
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'TENANT') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'TENANT'))");
			$this->db->order_by('m.id', 'asc');

			$query = $this->db->get();
			$data1 = $query->result_array();

			$arr = array();
			foreach ($data1 as $value) {
				$arr_data1 = array();
				$arr_data1[] = trim($value['m_id']);
				$arr_data1[] = trim($value['m_contact_type_from']);
				$arr_data1[] = trim($value['m_from_id']);
				$arr_data1[] = trim($value['m_contact_type_to']);
				$arr_data1[] = trim($value['m_to_id']);
				$arr_data1[] = trim($value['m_body']);
				$arr_data1[] = trim($value['m_attachment']);
				$arr_data1[] = trim($value['m_remove_status']);
				$arr_data1[] = trim($value['m_seen']);
				$arr_data1[] = trim($value['m_seen_at']);
				$arr_data1[] = trim($value['m_created_at']);
				$arr_data1[] = trim($value['m_updated_at']);
				array_push($arr, $arr_data1);
			}

			echo json_encode($arr);
		} else if ($user_type == "CUSTOMER") {
			$this->db->select('m.id as m_id, m.contact_type_from as m_contact_type_from, m.from_id as m_from_id, 
				m.contact_type_to as m_contact_type_to, m.to_id as m_to_id, body as m_body,
				m.attachment as m_attachment, m.remove_status as m_remove_status, m.seen as m_seen, m.seen_at as m_seen_at, m.created_at as m_created_at, m.updated_at as m_updated_at');
			$this->db->from('messages as m');
			$this->db->where("((m.from_id=$receiver_id AND m.to_id=$rider_id) OR (m.from_id=$rider_id AND m.to_id=$receiver_id))", NULL, FALSE);
			$this->db->where("((m.contact_type_from = 'RIDER' AND m.contact_type_to = 'CUSTOMER') OR (m.contact_type_to = 'RIDER' AND m.contact_type_from = 'CUSTOMER'))");
			$this->db->order_by('m.id', 'asc');

			$query = $this->db->get();
			$data1 = $query->result_array();

			$arr = array();
			foreach ($data1 as $value) {
				$arr_data1 = array();
				$arr_data1[] = trim($value['m_id']);
				$arr_data1[] = trim($value['m_contact_type_from']);
				$arr_data1[] = trim($value['m_from_id']);
				$arr_data1[] = trim($value['m_contact_type_to']);
				$arr_data1[] = trim($value['m_to_id']);
				$arr_data1[] = trim($value['m_body']);
				$arr_data1[] = trim($value['m_attachment']);
				$arr_data1[] = trim($value['m_remove_status']);
				$arr_data1[] = trim($value['m_seen']);
				$arr_data1[] = trim($value['m_seen_at']);
				$arr_data1[] = trim($value['m_created_at']);
				$arr_data1[] = trim($value['m_updated_at']);
				array_push($arr, $arr_data1);
			}

			echo json_encode($arr);
		} else if ($user_type == "RIDER") {

			//echo"Co-rider";

			$this->db->select('m.id as m_id, m.contact_type_from as m_contact_type_from, m.from_id as m_from_id, 
				m.contact_type_to as m_contact_type_to, m.to_id as m_to_id, body as m_body,
				m.attachment as m_attachment, m.remove_status as m_remove_status, m.seen as m_seen, m.seen_at as m_seen_at, m.created_at as m_created_at, m.updated_at as m_updated_at');
			$this->db->from('messages as m');
			$this->db->where("((m.to_id=$receiver_id OR m.to_id=$rider_id) AND (m.from_id=$receiver_id OR m.from_id=$rider_id))", NULL, FALSE);
			$this->db->where('m.contact_type_from', 'RIDER');
			$this->db->where('m.contact_type_to', 'RIDER');
			$this->db->order_by('m.id', 'asc');

			$query = $this->db->get();
			$data1 = $query->result_array();

			$arr = array();
			foreach ($data1 as $value) {
				$arr_data1 = array();
				$arr_data1[] = trim($value['m_id']);
				$arr_data1[] = trim($value['m_contact_type_from']);
				$arr_data1[] = trim($value['m_from_id']);
				$arr_data1[] = trim($value['m_contact_type_to']);
				$arr_data1[] = trim($value['m_to_id']);
				$arr_data1[] = trim($value['m_body']);
				$arr_data1[] = trim($value['m_attachment']);
				$arr_data1[] = trim($value['m_remove_status']);
				$arr_data1[] = trim($value['m_seen']);
				$arr_data1[] = trim($value['m_seen_at']);
				$arr_data1[] = trim($value['m_created_at']);
				$arr_data1[] = trim($value['m_updated_at']);
				array_push($arr, $arr_data1);
			}

			echo json_encode($arr);
		} else {
			echo "Others";
		}
	}

	public function save_new_user($r_id_num, $et_firstname, $et_lastname, $et_birthdate, $rb_sex, $et_permanentaddress, $et_mobileno, $et_username, $et_password, $spin_license_type, $et_otherdetails, $et_brand, $et_model, $et_color, $et_plateno, $et_otherdetails2)
	{
		$date_time = date('Y-m-d H:i:s');

		$data = array(
			'r_id_num' => $r_id_num,
			'r_picture' => '',
			'r_firstname' => $et_firstname,
			'r_lastname' => $et_lastname,
			'r_birth_date' => $et_birthdate,
			'r_gender' => $rb_sex,
			'r_address' => $et_permanentaddress,
			'r_mobile' => $et_mobileno,
			'r_license' => $spin_license_type,
			'r_other_details' => $et_otherdetails,
			'rm_picture' => '0',
			'rm_brand' => $et_brand,
			'rm_model' => $et_model,
			'rm_color' => $et_color,
			'rm_plate_num' => $et_plateno,
			'rm_other_d' => $et_otherdetails2,
			'vehicle_type' => '0',
			'username' => $et_username,
			'password' => md5($et_password),
			'status' => '0',
			'rider_stat' => '0',
			'created_at' => $date_time,
			'updated_at' => $date_time
		);
		$this->db->insert('toms_riders_data', $data);
	}

	public function Count_no_of_riders()
	{
		$year_today = date('Y');

		$this->db->select('*');
		$this->db->from('toms_riders_data as trd');
		$this->db->like('created_at', $year_today);

		$query = $this->db->get();
		$data = $query->num_rows();
		$data = $data + 1;
		$result = '00000' . $data . '-' . $year_today;

		$length = strlen($result) - 11;
		$final_result = substr($result, $length);

		return $final_result;
	}

	public function ValidateUsername($username)
	{
		$this->db->select('trd.id as trd_id, trd.r_id_num as trd_r_id_num');
		$this->db->from('toms_riders_data as trd');
		$this->db->where('trd.username', $username);

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['trd_id']);
			$arr_data[] = trim($value['trd_r_id_num']);

			array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}

	public function get_rider_mobile_no($username)
	{
		$this->db->select('trd.r_mobile as trd_r_mobile');
		$this->db->from('toms_riders_data as trd');
		$this->db->where('trd.username', $username);

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		$arr_data = "";
		foreach ($data as $value) {
			$arr_data = trim($value['trd_r_mobile']);
			array_push($arr, $arr_data);
		}

		echo json_encode($arr);

		return $arr_data;
	}

	public function get_rider_id($username)
	{
		$this->db->select('trd.id as trd_id');
		$this->db->from('toms_riders_data as trd');
		$this->db->where('trd.username', $username);

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		$arr_data = "";
		foreach ($data as $value) {
			$arr_data = trim($value['trd_id']);
		}

		return $arr_data;
	}

	public function save_user_verification_codes($rider_id, $my_number, $otp_num)
	{
		$date_time = date('Y-m-d H:i:s');

		$data = array(
			'user_id' => $rider_id,
			'contact_num' => $my_number,
			'otp_code' => $otp_num,
			'status' => '0',
			'created_at' => $date_time,
			'updated_at' => $date_time
		);
		$this->db->insert('user_verification_codes', $data);
	}

	public function match_otp_code($et_otp, $et_r_id_num)
	{

		$this->db->select('uvc.otp_code as uvc_otp_code');
		$this->db->from('toms_riders_data as trd');
		$this->db->join('user_verification_codes as uvc', 'trd.id = uvc.user_id', 'inner');
		$this->db->where('uvc.otp_code', $et_otp);
		$this->db->where('trd.username', $et_r_id_num);
		$this->db->where('uvc.status', '0');

		$query = $this->db->get();
		$data = $query->result_array();

		if ($query->num_rows() > 0) {
			$arr = array();
			$arr_data = array();
			//$arr_data[] = $this->encrypt_mod("naa"); 
			$arr_data[] = "naa";
			array_push($arr, $arr_data);

			echo json_encode($arr_data);
		} else {
			$arr = array();
			$arr_data = array();
			//$arr_data[] = $this->encrypt_mod("wala"); 
			$arr_data[] = "wala";
			array_push($arr, $arr_data);

			echo json_encode($arr_data);
		}

		return $query->num_rows();
	}

	public function encrypt_mod($string)
	{
		return openssl_encrypt($string, ENCRYPT_METHOD, SECRET_KEY, 0, SECRET_IV);
	}

	public function decrypt_mod($string)
	{
		return openssl_decrypt($string, ENCRYPT_METHOD, SECRET_KEY, 0, SECRET_IV);
	}

	public function update_otp_status($otp_code)
	{
		$result = array();
		$this->db->set('status', '1');
		$this->db->where('otp_code', $otp_code);
		$this->db->update('user_verification_codes');
	}

	public function update_rider_status($username)
	{
		$result = array();
		$this->db->set('rider_block_status', '0');
		$this->db->where('username', $username);
		$this->db->update('toms_riders_data');
	}

	public function update_password_mod($username, $password)
	{
		$result = array();
		$this->db->set('password', md5($password));
		$this->db->where('username', $username);
		$this->db->update('toms_riders_data');
	}

	public function update_rider_blocked_status_mod($username)
	{
		$result = array();
		$this->db->set('rider_block_status', '1');
		$this->db->where('username', $username);
		$this->db->update('toms_riders_data');
	}

	public function get_chatbox_usertype_mod()
	{

		$result = array();
		$this->db->select('u.user_type as u_user_type');
		$this->db->from('users as u');
		$this->db->group_by('u.user_type',);
		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			if (trim($value['u_user_type']) != "ADMIN" && trim($value['u_user_type']) != "AUDIT" && trim($value['u_user_type']) != "ACCOUNTING") {
				if (trim($value['u_user_type']) == "CS_1") {
					$arr_data[] = "Customer Service";
					array_push($arr, $arr_data);
				} else if (trim($value['u_user_type']) == "RC") {
					$arr_data[] = "Rider Coordinator";
					array_push($arr, $arr_data);
				} else {
					$arr_data[] = trim($value['u_user_type']);
					array_push($arr, $arr_data);
				}
			}
		}

		$arr_data1[] = "Tenant";
		array_push($arr, $arr_data1);

		$arr_data2[] = "Customer";
		array_push($arr, $arr_data2);

		$arr_data3[] = "Co-rider";
		array_push($arr, $arr_data3);

		echo json_encode($arr);
	}

	public function get_chatbox_users_mod($user_type, $rider_id)
	{
		if ($user_type == "Customer Service") {
			$this->db->select('u.name as u_name, u.logged_in as u_logged_in');
			$this->db->from('users as u');
			$this->db->where('u.active', '1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			foreach ($data as $value) {
				$arr_data = array();
				$arr_data[] = trim($value['u_name']);
				$arr_data[] = trim($value['u_logged_in']);
				array_push($arr, $arr_data);
			}
		} else if ($user_type == "Rider Coordinator") {
			$this->db->select('u.name as u_name, u.logged_in as u_logged_in');
			$this->db->from('users as u');
			$this->db->where('u.active', '1');
			$this->db->where('u.user_type', 'RC');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			foreach ($data as $value) {
				$arr_data = array();
				$arr_data[] = trim($value['u_name']);
				$arr_data[] = trim($value['u_logged_in']);

				array_push($arr, $arr_data);
			}
		} else if ($user_type == "Tenant") {
			$this->db->select('u.name as u_name, lt.tenant as lt_tenant, lbu.acroname as lbu_acroname');
			$this->db->from('tenant_users as u');
			$this->db->join('locate_tenants as lt', 'lt.tenant_id = u.tenant_id');
			$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code');
			$this->db->where('u.active', '1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			foreach ($data as $value) {
				$arr_data = array();
				$arr_data[] = trim($value['u_name']) . "(" . trim($value['lbu_acroname']) . " - " . trim($value['lt_tenant']) . ")";
				$arr_data[] = '1';

				array_push($arr, $arr_data);
			}
		} else if ($user_type == "Customer") {
			$this->db->select('au.customer_id as au_customer_id,au.firstname as au_fistname, au.lastname as au_lastname, au.status as au_status');
			$this->db->from('app_users as au');
			$this->db->where('au.status', '1');

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			foreach ($data as $value) {
				$arr_data = array();
				$arr_data[] = trim($value['au_customer_id']);
				$arr_data[] = trim($value['au_fistname']) . " " . trim($value['au_lastname']);
				$arr_data[] = trim($value['au_status']);

				array_push($arr, $arr_data);
			}
		} else if ($user_type == "Co-rider") {
			$this->db->select('trd.r_firstname as trd_r_firstname, trd.r_lastname as trd_r_lastname, trd.online_status as trd_online_status');
			$this->db->from('toms_riders_data as trd');
			$this->db->where('trd.status', '0');
			$this->db->where('trd.id !=', $rider_id);

			$query = $this->db->get();
			$data = $query->result_array();

			$arr = array();
			foreach ($data as $value) {
				$arr_data = array();
				$arr_data[] = trim($value['trd_r_firstname']) . " " . trim($value['trd_r_lastname']);
				$arr_data[] = trim($value['trd_online_status']);

				array_push($arr, $arr_data);
			}
		}

		echo json_encode($arr);
	}

	public function get_tenants_mod()
	{

		$this->db->select('lt.tenant_id as lt_tenant_id, lbu.acroname as lbu_acroname, lt.tenant as lt_tenant');
		$this->db->from('locate_business_units as lbu');
		$this->db->join('locate_tenants as lt', 'lt.bunit_code = lbu.bunit_code', 'inner');
		$this->db->where('lt.active', '1');

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['lt_tenant_id']);
			$arr_data[] = trim($value['lbu_acroname']) . " - " . trim($value['lt_tenant']);

			array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}

	public function get_tenants_users_mod($tenant_id)
	{

		$this->db->select('u.name as u_name, lt.tenant as lt_tenant, lbu.acroname as lbu_acroname');
		$this->db->from('tenant_users as u');
		$this->db->join('locate_tenants as lt', 'lt.tenant_id = u.tenant_id');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = lt.bunit_code');
		$this->db->where('u.active', '1');
		$this->db->where('lt.tenant_id', $tenant_id);

		$query = $this->db->get();
		$data = $query->result_array();

		$arr = array();
		foreach ($data as $value) {
			$arr_data = array();
			$arr_data[] = trim($value['u_name']);
			$arr_data[] = '1';

			array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}

	public function get_tickets_mod()
	{
	}
}
