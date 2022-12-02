<?php
class GC_Rider_Model extends CI_Model {

    public function __construct(){
		parent::__construct();
		date_default_timezone_set('Asia/Manila');
	}

	public function gc_download_customer_orders_mod($r_id_number)
	{
		//$r_id_number = '000001-2020';
		$result = array();


		$this->db->select("gfo.id as gfo_id, tkts.ticket as tkts_ticket, cdi.id as cdi_id, cdi.firstname as cdi_firstname, 
			cdi.lastname as cdi_lastname, cdi.mobile_number as cdi_mobile_number, b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, 
			group_concat(DISTINCT(gpi.product_name)) as gpi_product_name, cb.delivery_charge as cb_delivery_charge,
			cb.picking_charge as cb_picking_charge, gos.view_status as gos_view_status, 
			gos.released_status as gos_released_status, 
			gos.delivered_status as gos_delivered_status, gos.cancelled_status as gos_cancelled_status, 
			lbu.business_unit as lbu_business_unit, gpu.UOM as gpu_UOM, tkts.type as tkts_type, gos.created_at as gos_created_at,
			cb.amount as cb_amount, cb.change cb_change, ptr.main_rider_stat as ptr_main_rider_stat, epd.payment_platform as epd_payment_platform,


			(SELECT group_concat(' ', gbt.type,' - ',gpd.qty) 
			FROM gc_package_details gpd 
			INNER JOIN gc_barcode_type as gbt ON gbt.id = gpd.barcodetype_id
			WHERE gpd.ticket_id = tkts_ticket) as num_pack,
			
			(SELECT sum(total_price) FROM gc_final_order gfo1 INNER JOIN tickets tkts1 ON gfo1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as total_price,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider
			");

		$this->db->from('gc_final_order as  gfo');
		$this->db->join('tickets as tkts', 'tkts.id = gfo.ticket_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = gfo.bu_id', 'inner');
		$this->db->join('gc_product_items as gpi', 'gpi.product_id = gfo.product_id', 'inner');
		$this->db->join('gc_product_uoms as gpu', 'gpu.uom_id = gfo.uom_id', 'inner');
		$this->db->join('gc_order_statuses as gos', 'gos.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('toms_riders_data as trd', 'trd.id = gos.rider_id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = tkts.id', 'inner');
		$this->db->join('e_payment_details as epd', 'epd.ticket_id = gfo.ticket_id', 'left');

		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('gfo.canceled_status','0');
		$this->db->where('gfo.status','1');
		$this->db->where('gos.mode_of_order','0');
		$this->db->where('gos.remitted_status','0');
		$this->db->group_by("gfo.ticket_id");

		$query = $this->db->get();
	    $data = $query->result_array();	
	    
	    $arr = Array();
	 	foreach($data as $value)
	 	{

			 	 $arr_data= Array();
				 $arr_data[] = trim($value['gfo_id']);
				 $arr_data[] = trim($value['cdi_id']);
				 $arr_data[] = trim($value['cdi_firstname']);
				 $arr_data[] = trim($value['cdi_lastname']); 
				 $arr_data[] = trim($value['b_brgy_name']); 
				 $arr_data[] = trim($value['t_town_name']); 
				 $arr_data[] = trim($value['gpi_product_name']);
				 $arr_data[] = trim($value['total_price']);
				 $arr_data[] = '0.00';//($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
				 $arr_data[] = trim($value['cb_delivery_charge']); 
				 $arr_data[] = trim($value['gos_view_status']);
				 $arr_data[] = trim($value['gos_released_status']);
				 $arr_data[] = trim($value['gos_delivered_status']); 
				 $arr_data[] = trim($value['gos_cancelled_status']); 
				 $arr_data[] = '0';//wala gamita...
				 $arr_data[] = trim($value['tkts_ticket']); 
				 $arr_data[] = trim($value['cdi_mobile_number']);
				 $arr_data[] = ($value['num_pack']) ? trim($value['num_pack']) : 'None'; //trim($value['num_pack']);
				 $arr_data[] = trim($value['cdi_land_mark']); 
				 $arr_data[] = trim($value['tkts_type']);
				 $arr_data[] = trim($value['gos_created_at']); 
				 $arr_data[] = trim($value['cb_amount']); 
				 $arr_data[] = trim($value['cb_change']); 
				 $arr_data[] = "(" . trim($value['lbu_business_unit']) . ")";
				 $arr_data[] = trim($value['ptr_main_rider_stat']);
				 $arr_data[] = trim($value['count_rider']);
				 $arr_data[] = 'None';//($value['csi_instructions']) ? trim($value['csi_instructions']) : 'None';
				 $arr_data[] = '1';//$value['cds_submit_status'];
				 $arr_data[] = ($value['cb_picking_charge']) ? trim($value['cb_picking_charge']) : '0.00'; //trim($value['cb_picking_charge']); 
				 $arr_data[] = $value['epd_payment_platform'];

			    array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}

	public function gc_download_transaction_view_items_mod($r_id_number, $ticket_id)
	{

		$result = array();

		$this->db->select('tckts.id as tckts_id ,gfo.id as gfo_id, gpi.image as gpi_image, gpi.product_name as gpi_product_name, gfo.price as gfo_price, 
			gfo.quantity as gfo_quantity, gfo.total_price as gfo_total_price, cb.delivery_charge as cb_delivery_charge, 
			cb.picking_charge as cb_picking_charge, lbu.business_unit as lbu_business_unit,
			(SELECT SUM(gfo1.total_price) FROM gc_final_order as gfo1 WHERE ticket_id = tckts_id) as  gfo_sum_total_price
			');
		$this->db->from('gc_final_order as  gfo');
		$this->db->join('tickets as tckts', 'tckts.id = gfo.ticket_id', 'inner'); 
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = gfo.bu_id', 'inner'); 
		$this->db->join('gc_product_items as gpi', 'gpi.product_id = gfo.product_id', 'inner'); 
		$this->db->join('gc_order_statuses as gos', 'gos.ticket_id = gfo.ticket_id', 'inner'); 
		$this->db->join('toms_riders_data as trd', 'trd.id = gos.rider_id', 'inner'); 
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tckts.id', 'inner'); 

		$this->db->where('tckts.ticket',$ticket_id);
		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('gfo.canceled_status','0');
		$this->db->where('gfo.status','1');
		$this->db->where('gos.mode_of_order','0');
		//$this->db->group_by("gfo.ticket_id");
		$query = $this->db->get();
	    $data = $query->result_array();

	    $arr = Array();
	 	foreach($data as $value)
	 	{
		 	 $arr_data= Array();
			 $arr_data[] = trim($value['gfo_id']);
			 $arr_data[] = trim($value['gpi_image']);
			 $arr_data[] = trim($value['gpi_product_name']);
			 $arr_data[] = trim($value['gfo_price']);
			 $arr_data[] = trim($value['gfo_quantity']);
			 $arr_data[] = trim($value['gfo_total_price']);
			 $arr_data[] = trim($value['cb_delivery_charge']);
			 $arr_data[] = trim($value['cb_picking_charge']);
			 $arr_data[] = trim($value['lbu_business_unit']);
			 $arr_data[] = trim($value['gfo_sum_total_price']); 
			 $arr_data[] = '0.00';//$arr_data[] = ($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';

			array_push($arr,$arr_data);
		}
		echo json_encode($arr);
	}

	public function gc_update_delivery_status_mod($id, $r_id_num)
	{

		$date_time = date('Y-m-d H:i:s');

		$query = $this->db->query("UPDATE gc_order_statuses as gos
								   		INNER JOIN toms_riders_data as trd
								   		ON gos.rider_id = trd.id
								   		INNER JOIN tickets as tckts
								   		ON tckts.id = gos.ticket_id
								   		SET gos.delivered_status = '1',
								   		gos.delivered_at = '$date_time'
								   		WHERE trd.r_id_num = '$r_id_num'
								   		AND tckts.ticket = '$id'");
	}

	public function gc_update_cancelled_status_mod($id, $r_id_num)
	{

		$date_time = date('Y-m-d H:i:s');

		$query = $this->db->query("UPDATE gc_order_statuses as gos
								   		INNER JOIN toms_riders_data as trd
								   		ON gos.rider_id = trd.id
								   		INNER JOIN tickets as tckts
								   		ON tckts.id = gos.ticket_id
								   		SET gos.cancelled_status = '1',
								   		gos.cancelled_at = '$date_time'
								   		WHERE trd.r_id_num = '$r_id_num'
								   		AND tckts.ticket = '$id'");
	}

	public function gc_get_customer_details_mod($ticket_id)
	{
		$result = array();

		$this->db->select("cdi.id as cdi_id, cdi.firstname as cdi_firstname, cdi.lastname as cdi_lastname, 	
			cdi.mobile_number as cdi_mobile_number, cdi.complete_address as cdi_complete_address, 
			cdi.street_purok as cdi_street_purok, b.brgy_name as b_brgy_name, t.town_name as t_town_name,
			cdi.land_mark as cdi_land_mark
			");

		$this->db->from('customer_delivery_infos as cdi');
		$this->db->join('tickets as tckts', 'tckts.id = cdi.ticket_id');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id');
		$this->db->join('towns as t', 't.town_id = b.town_id');
		$this->db->where('tckts.ticket',$ticket_id);
		$query = $this->db->get();
	    $data = $query->result_array();

	    $arr = Array();
	 	foreach($data as $value)
	 	{
		 	 $arr_data= Array();
			 $arr_data[] = trim($value['cdi_id']);
			 $arr_data[] = trim($value['cdi_firstname']) .' '. $value['cdi_lastname'];
			 $arr_data[] = trim($value['cdi_mobile_number']);
			 $arr_data[] = trim($value['cdi_complete_address']);
			 $arr_data[] = trim($value['cdi_street_purok']);
			 $arr_data[] = trim($value['b_brgy_name']);
			 $arr_data[] = trim($value['t_town_name']);
			 $arr_data[] = trim($value['cdi_land_mark']);

			array_push($arr,$arr_data);
		}
		echo json_encode($arr);
	}

	public function gc_update_viewed_status_mod($ticket_id)
	{

		$query = $this->db->query("UPDATE gc_order_statuses as gos
								   INNER JOIN tickets as tckts
								   ON gos.ticket_id = tckts.id
								   SET gos.view_status = '1'
								   WHERE tckts.ticket = '$ticket_id'");
	}

	public function gc_get_tenant_timeframe_mod($r_id_number, $ticket_id)
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
		$this->db->join('toms_tag_riders as ttr','tco.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('partial_tag_riders as ptr','ptr.ticket_id = ttr.ticket_id', 'inner');
		$this->db->join('locate_tenants as lt','lt.tenant_id = ttr.tenant_id', 'inner');
		$this->db->join('locate_business_units as lbu','lbu.bunit_code = lt.bunit_code','inner');
		$this->db->where('ptr.r_id_num', $r_id_number);
		$this->db->where('ptr.ticket_id',$ticket_id);
		$this->db->group_by('ttr.tenant_id');
		$query = $this->db->get();
	    $data = $query->result_array();

	    $arr = Array();
	 	foreach($data as $value)
	 	{
		 	 $arr_data= Array();
		 	 $arr_data[] = trim($value['tco_id']);
			 $arr_data[] = trim($value['tco_created_at']);
			 $arr_data[] = trim($value['tStatus']);
			 $arr_data[] = trim($value['tco_r_setup_stat']);
			 $arr_data[] = trim($value['ttr_trans_at']);
			 $arr_data[] = trim($value['ttr_delevered_at']);
			 $arr_data[] = trim($value['lt_tenant_id']);
			 $arr_data[] = trim($value['lbu_acroname']);
			 $arr_data[] = '';

			array_push($arr,$arr_data);
		}
		echo json_encode($arr);
	}

	public function gc_download_history_items_mod($r_id_number)
	{
		//$r_id_number = '000001-2020';
		$result = array();


		$this->db->select("gfo.id as gfo_id, tkts.ticket as tkts_ticket, cdi.id as cdi_id, cdi.firstname as cdi_firstname, 
			cdi.lastname as cdi_lastname, cdi.mobile_number as cdi_mobile_number, b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, 
			group_concat(DISTINCT(gpi.product_name)) as gpi_product_name, cb.delivery_charge as cb_delivery_charge,
			cb.picking_charge as cb_picking_charge, gos.view_status as gos_view_status, 
			gos.released_status as gos_released_status, 
			gos.delivered_status as gos_delivered_status, gos.cancelled_status as gos_cancelled_status, 
			lbu.business_unit as lbu_business_unit, gpu.UOM as gpu_UOM, tkts.type as tkts_type, gos.created_at as gos_created_at,
			cb.amount as cb_amount, cb.change cb_change, ptr.main_rider_stat as ptr_main_rider_stat, epd.payment_platform as epd_payment_platform,


			(SELECT group_concat(' ', gbt.type,' - ',gpd.qty) 
			FROM gc_package_details gpd 
			INNER JOIN gc_barcode_type as gbt ON gbt.id = gpd.barcodetype_id
			WHERE gpd.ticket_id = tkts_ticket) as num_pack,
			
			(SELECT sum(total_price) FROM gc_final_order gfo1 INNER JOIN tickets tkts1 ON gfo1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as total_price,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider
			");

		$this->db->from('gc_final_order as  gfo');
		$this->db->join('tickets as tkts', 'tkts.id = gfo.ticket_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = gfo.bu_id', 'inner');
		$this->db->join('gc_product_items as gpi', 'gpi.product_id = gfo.product_id', 'inner');
		$this->db->join('gc_product_uoms as gpu', 'gpu.uom_id = gfo.uom_id', 'inner');
		$this->db->join('gc_order_statuses as gos', 'gos.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('toms_riders_data as trd', 'trd.id = gos.rider_id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = tkts.id', 'inner');
		$this->db->join('e_payment_details as epd', 'epd.ticket_id = gfo.ticket_id', 'left');

		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('gfo.canceled_status','0');
		$this->db->where('gfo.status','1');
		$this->db->where('gos.mode_of_order','0');
		$this->db->where('gos.remitted_status','1');
		$this->db->group_by("gfo.ticket_id");

		$query = $this->db->get();
	    $data = $query->result_array();	
	    
	    $arr = Array();
	 	foreach($data as $value)
	 	{

			 	 $arr_data= Array();
				 $arr_data[] = trim($value['gfo_id']);
				 $arr_data[] = trim($value['cdi_id']);
				 $arr_data[] = trim($value['cdi_firstname']);
				 $arr_data[] = trim($value['cdi_lastname']); 
				 $arr_data[] = trim($value['b_brgy_name']); 
				 $arr_data[] = trim($value['t_town_name']); 
				 $arr_data[] = trim($value['gpi_product_name']);
				 $arr_data[] = trim($value['total_price']);
				 $arr_data[] = '0.00';//($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00';
				 $arr_data[] = trim($value['cb_delivery_charge']); 
				 $arr_data[] = trim($value['gos_view_status']);
				 $arr_data[] = trim($value['gos_released_status']);
				 $arr_data[] = trim($value['gos_delivered_status']); 
				 $arr_data[] = trim($value['gos_cancelled_status']); 
				 $arr_data[] = '0';//wala gamita...
				 $arr_data[] = trim($value['tkts_ticket']); 
				 $arr_data[] = trim($value['cdi_mobile_number']);
				 $arr_data[] = trim($value['num_pack']);
				 $arr_data[] = trim($value['cdi_land_mark']); 
				 $arr_data[] = trim($value['tkts_type']);
				 $arr_data[] = trim($value['gos_created_at']); 
				 $arr_data[] = trim($value['cb_amount']); 
				 $arr_data[] = trim($value['cb_change']); 
				 $arr_data[] = "(" . trim($value['lbu_business_unit']) . ")";
				 $arr_data[] = trim($value['ptr_main_rider_stat']);
				 $arr_data[] = trim($value['count_rider']);
				 $arr_data[] = 'None';//($value['csi_instructions']) ? trim($value['csi_instructions']) : 'None';
				 $arr_data[] = '1';//$value['cds_submit_status'];
				 $arr_data[] = ($value['cb_picking_charge']) ? trim($value['cb_picking_charge']) : '0.00'; //trim($value['cb_picking_charge']); 
				 $arr_data[] = $value['epd_payment_platform'];

			    array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}

	public function gc_download_reports_items_mod($r_id_number,$delevered_status,$selected_date)
	{


		$result = array();


		$this->db->select("gfo.id as gfo_id, tkts.ticket as tkts_ticket, cdi.id as cdi_id, cdi.firstname as cdi_firstname, 
			cdi.lastname as cdi_lastname, cdi.mobile_number as cdi_mobile_number, b.brgy_name as b_brgy_name, t.town_name as t_town_name, cdi.land_mark as cdi_land_mark, 
			group_concat(DISTINCT(gpi.product_name)) as gpi_product_name, cb.delivery_charge as cb_delivery_charge,
			cb.picking_charge as cb_picking_charge, gos.view_status as gos_view_status, 
			gos.released_status as gos_released_status, 
			gos.delivered_status as gos_delivered_status, gos.cancelled_status as gos_cancelled_status, 
			lbu.business_unit as lbu_business_unit, gpu.UOM as gpu_UOM, tkts.type as tkts_type, gos.created_at as gos_created_at,
			cb.amount as cb_amount, cb.change cb_change, ptr.main_rider_stat as ptr_main_rider_stat,


			(SELECT group_concat(' ', gbt.type,' - ',gpd.qty) 
			FROM gc_package_details gpd 
			INNER JOIN gc_barcode_type as gbt ON gbt.id = gpd.barcodetype_id
			WHERE gpd.ticket_id = tkts_ticket) as num_pack,
			
			(SELECT sum(total_price) FROM gc_final_order gfo1 INNER JOIN tickets tkts1 ON gfo1.ticket_id = tkts1.id  WHERE tkts1.ticket = tkts_ticket AND canceled_status = '0' AND status = '1') as total_price,
			(SELECT COUNT(trd1.r_id_num) FROM partial_tag_riders ptr1 INNER JOIN tickets tkts7 ON ptr1.ticket_id = tkts7.id INNER JOIN toms_riders_data trd1 ON trd1.id = ptr1.rider_id WHERE tkts7.ticket = tkts_ticket) as count_rider
			");

		$this->db->from('gc_final_order as  gfo');
		$this->db->join('tickets as tkts', 'tkts.id = gfo.ticket_id', 'inner');
		$this->db->join('locate_business_units as lbu', 'lbu.bunit_code = gfo.bu_id', 'inner');
		$this->db->join('gc_product_items as gpi', 'gpi.product_id = gfo.product_id', 'inner');
		$this->db->join('gc_product_uoms as gpu', 'gpu.uom_id = gfo.uom_id', 'inner');
		$this->db->join('gc_order_statuses as gos', 'gos.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('customer_delivery_infos as cdi', 'cdi.ticket_id = gfo.ticket_id', 'inner');
		$this->db->join('barangays as b', 'b.brgy_id = cdi.barangay_id', 'inner');
		$this->db->join('towns as t', 't.town_id = b.town_id', 'inner');
		$this->db->join('toms_riders_data as trd', 'trd.id = gos.rider_id', 'inner');
		$this->db->join('customer_bills as cb', 'cb.ticket_id = tkts.id', 'inner');
		$this->db->join('partial_tag_riders as ptr', 'ptr.ticket_id = tkts.id', 'inner');

		$this->db->where('trd.r_id_num', $r_id_number);
		$this->db->where('gos.delivered_status', $delevered_status);
		$this->db->where('date(gfo.created_at)', $selected_date);
		$this->db->where('gfo.canceled_status','0');
		$this->db->where('gfo.status','1');
		$this->db->where('gos.mode_of_order','0');
		$this->db->where('gos.remitted_status','0');
		$this->db->group_by("gfo.ticket_id");

		$query = $this->db->get();
	    $data = $query->result_array();	
	    
	    $arr = Array();
	 	foreach($data as $value)
	 	{

			 	 $arr_data= Array();
				 $arr_data[] = trim($value['gfo_id']); //0
				 $arr_data[] = trim($value['cdi_firstname']); //1
				 $arr_data[] = trim($value['cdi_lastname']); //2
				 $arr_data[] = trim($value['b_brgy_name']); //3
				 $arr_data[] = trim($value['t_town_name']); //4
				 $arr_data[] = trim($value['gpi_product_name']); //5
				 $arr_data[] = trim($value['total_price']); //6
				 $arr_data[] = '0.00';//($value['cda_discounted_amount2']) ? trim($value['cda_discounted_amount2']) : '0.00'; //7
				 $arr_data[] = trim($value['cb_delivery_charge']); //8
				 $arr_data[] = trim($value['gos_view_status']); //9
				 $arr_data[] = trim($value['gos_released_status']); //10
				 $arr_data[] = trim($value['gos_delivered_status']); //11
				 $arr_data[] = trim($value['gos_cancelled_status']); //12
				 $arr_data[] = '0';//wala gamita... //13
				 $arr_data[] = trim($value['tkts_ticket']); //14 
				 $arr_data[] = trim($value['cdi_mobile_number']); //15 
				 $arr_data[] = trim($value['tkts_type']); //16
				 $arr_data[] = trim($value['cb_amount']); //17
				 $arr_data[] = trim($value['cb_change']); //18
				 $arr_data[] = trim($value['ptr_main_rider_stat']); //19 
				 $arr_data[] = trim($value['count_rider']);  //20
				 $arr_data[] = ($value['cb_picking_charge']) ? trim($value['cb_picking_charge']) : '0.00'; //trim($value['cb_picking_charge']); //21

			    array_push($arr, $arr_data);
		}

		echo json_encode($arr);
	}
}
