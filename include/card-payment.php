<?php

add_filter( 'woocommerce_payment_gateways', 'cookiepayments_card_add_gateway_class' );
function cookiepayments_card_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_cookiepayments_card_Gateway'; // your class name is here
	return $gateways;
}

/**
 * after select refund this field will show in order details
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'cookiepayments_card_after_select_refund' );
function cookiepayments_card_after_select_refund($order){

	// if($order->data['status'] == 'refunded'){	
		?>

		<div class="refund-reason">
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reason">Refund Reason</label>
				<textarea rows="3" name="reason" id="reason" placeholder="Add the refund reason"><?php echo get_post_meta($order->ID, '_refund_reason', true); ?></textarea>
			</p>
			
	</div>
		<?php
	// }
}


add_action('woocommerce_process_shop_order_meta', function($order_id){

	$refund_reason = isset($_REQUEST['reason']) ? $_REQUEST['reason']: '';
	update_post_meta($order_id, '_refund_reason', $refund_reason);

	$api_id = get_option('woocommerce_cookiepayments_card_settings')['cookiepayments_card_api_id'];    
	$api_key = get_option('woocommerce_cookiepayments_card_settings')['cookiepayments_card_api_key']; 


	$TID = get_post_meta($order_id, '_TID', true);
	// $ACCEPTDATE = get_post_meta($order_id, '_ACCEPTDATE', true);
	// $ACCEPTNO = get_post_meta($order_id, '_ACCEPTNO', true);
	// $CASH_BILL_NO = get_post_meta($order_id, '_CASH_BILL_NO', true);
	// $CARDNAME = get_post_meta($order_id, '_CARDNAME', true);
	// $ACCOUNTNO = get_post_meta($order_id, '_ACCOUNTNO', true);
	// $RECEIVERNAME = get_post_meta($order_id, '_RECEIVERNAME', true);
	// $DEPOSITENDDATE = get_post_meta($order_id, '_DEPOSITENDDATE', true);


	///cancle curl api 
	$tokenheaders = array(); 
	array_push($tokenheaders, "content-type: application/json; charset=utf-8");

	$token_url = "https://www.cookiepayments.com/payAuth/token";

	$token_request_data = array(
		'pay2_id' => $api_id,
		'pay2_key'=> $api_key,
	);

	$req_json = json_encode($token_request_data, TRUE);

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $token_url);
	curl_setopt($ch,CURLOPT_POST, false);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $req_json);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
	curl_setopt($ch,CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $tokenheaders);
	$RES_STR = curl_exec($ch);
	curl_close($ch);
	$RES_STR = json_decode($RES_STR,TRUE);

	if($RES_STR['RTN_CD'] == '0000'){

		$headers = array(); 
		array_push($headers, "content-type: application/json; charset=utf-8");
		array_push($headers, "TOKEN:".$RES_STR['TOKEN']."");
		array_push($headers, "ApiKey: $api_key");

		$cookiepayments_url = "https://www.cookiepayments.com/api/cancel";

		$request_data_array = array(
			'tid' => $TID,
			'reason'=> $refund_reason,
		);

		$cookiepayments_json = json_encode($request_data_array, TRUE);

		$ch = curl_init();

		curl_setopt($ch,CURLOPT_URL, $cookiepayments_url);
		curl_setopt($ch,CURLOPT_POST, false);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $cookiepayments_json);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
		curl_setopt($ch,CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch);
		curl_close($ch);

		$result_array = json_decode($response,TRUE);
		var_dump($result_array);
	}

});

/**
 * Show payment details after admin order billing address
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'cookiepayments_card_admin_order_page_meta' );
function cookiepayments_card_admin_order_page_meta( $order ){

	if( $order->get_payment_method() != 'cookiepayments_card' )
	return;

	$TID = get_post_meta($_GET['post'], '_TID', true);
	$ACCEPTDATE = get_post_meta($_GET['post'], '_ACCEPTDATE', true);
	$ACCEPTNO = get_post_meta($_GET['post'], '_ACCEPTNO', true);
	$CASH_BILL_NO = get_post_meta($_GET['post'], '_CASH_BILL_NO', true);
	$CARDNAME = get_post_meta($_GET['post'], '_CARDNAME', true);
	$ACCOUNTNO = get_post_meta($_GET['post'], '_ACCOUNTNO', true);
	$RECEIVERNAME = get_post_meta($_GET['post'], '_RECEIVERNAME', true);
	$DEPOSITENDDATE = get_post_meta($_GET['post'], '_DEPOSITENDDATE', true);

	?>
	<div class="form-field form-field-wide">
		<img src='<?php echo plugins_url("../assets/img/cookiepayments-logo.png", __FILE__); ?>' alt="Cookiepayments VACCT">
		<table class="wp-list-table widefat fixed striped posts">
			<tbody>
				<tr>
					<th><strong><?php esc_html_e('Transaction ID:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $TID );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Accept Date:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $ACCEPTDATE );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Accept No:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $ACCEPTNO );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Cash Bill No:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $CASH_BILL_NO );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Card Name:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $CARDNAME );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Account no:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $ACCOUNTNO );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Receiver name:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $RECEIVERNAME );?></td>
				</tr>
				<tr>
					<th><strong><?php esc_html_e('Deposit End Date:', 'cookie') ;?></strong></th>
					<td> <?php echo esc_attr( $DEPOSITENDDATE );?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php

}


add_action('wp_head', function(){

	if( isset( $_GET['orderid'] ) ){
		$order_id = $_GET['orderid'];

		$order = wc_get_order( $order_id );
	
		if( empty( $order_id ) ){
			wp_redirect( home_url() );
		}else {
				$TID = isset( $_REQUEST['TID'] ) ? $_REQUEST['TID'] : '';
				$ACCEPTDATE = isset( $_REQUEST['ACCEPTDATE'] ) ? $_REQUEST['ACCEPTDATE'] : '';
				$ACCEPTNO = isset( $_REQUEST['ACCEPTNO'] ) ? $_REQUEST['ACCEPTNO'] : '';
				$CASH_BILL_NO = isset( $_REQUEST['CASH_BILL_NO'] ) ? $_REQUEST['CASH_BILL_NO'] : '';
				$CARDNAME = isset( $_REQUEST['CARDNAME'] ) ? $_REQUEST['CARDNAME'] : '';
				$ACCOUNTNO = isset( $_REQUEST['ACCOUNTNO'] ) ? $_REQUEST['ACCOUNTNO'] : '';
				$RECEIVERNAME = isset( $_REQUEST['RECEIVERNAME'] ) ? $_REQUEST['RECEIVERNAME'] : '';
				$DEPOSITENDDATE = isset( $_REQUEST['DEPOSITENDDATE'] ) ? $_REQUEST['DEPOSITENDDATE'] : '';
			
				update_post_meta($order_id, '_TID', $TID);
				update_post_meta($order_id, '_ACCEPTDATE', $ACCEPTDATE);
				update_post_meta($order_id, '_ACCEPTNO', $ACCEPTNO);
				update_post_meta($order_id, '_CASH_BILL_NO', $CASH_BILL_NO);
				update_post_meta($order_id, '_CARDNAME', $CARDNAME);
				update_post_meta($order_id, '_ACCOUNTNO', $ACCOUNTNO);
				update_post_meta($order_id, '_RECEIVERNAME', $RECEIVERNAME);
				update_post_meta($order_id, '_DEPOSITENDDATE', $DEPOSITENDDATE);

			if ($order->data['status'] == 'pending') {
				$payment_method=$order->get_payment_method();
				if ($payment_method != "cod")
				{
					$order->update_status( 'processing' );

					wp_redirect( home_url() . '/thankyou' );

				}
			}

			// if(isset($_REQUEST['RESULT_CODE'])) {
			// 	// pay success  
			// 	if($_REQUEST['RESULT_CODE'] == "0000") {  
			// 		// echo $_REQUEST['RESULT_MSG'];
			// 		if ($order->data['status'] == 'pending') {
			// 			$payment_method=$order->get_payment_method();
			// 			if ($payment_method != "cod")
			// 			{
			// 				$order->update_status( 'processing' );
			// 				// $method_class = new WC_Payment_Gateway;
			// 				// wp_redirect( $method_class->get_return_url( $order ));
			// 				wp_redirect( home_url() . '/thankyou' );
			// 			}
			// 		}
			// 		// pay success process area  
			// 	} 
			// 	// pay fail  
			// 	else if($_REQUEST['RESULT_CODE]'] != "0000") {  
			// 		// echo $_REQUEST['RESULT_MSG'];
			// 		wp_redirect( home_url() );
			// 		// pay fail process area  ​  
			// 	}  
			// }else {
			// 	wp_redirect( home_url() );
			// }


			
		}
	}
	

});

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'cookiepayments_card_init_gateway_class' );
function cookiepayments_card_init_gateway_class() {

	class WC_cookiepayments_card_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
	        $this->id = 'cookiepayments_card'; // payment gateway plugin ID
	        $this->icon =  plugin_dir_url( ( __FILE__ ) ) . '../assets/img/cookiepayments-logo.png';; // URL of the icon that will be displayed on checkout page near your gateway name
	        $this->has_fields = true; // in case you need a custom credit card form
	        $this->method_title = 'CookiePayments Card Gateway';
	        $this->method_description = 'CookiePayments Card payment gateway'; // will be displayed on the options page

	        // gateways can support subscriptions, refunds, saved payment methods,
	        // but in this tutorial we begin with simple payments
	        $this->supports = array(
	            'products'
	        );

	        // Method with all the options fields
	        $this->init_form_fields();
	        //CookiePayments
	        // Load the settings.
	        $this->init_settings();
	        $this->title = $this->get_option( 'cookiepayments_card_title' );
	        $this->description = $this->get_option( 'cookiepayments_card_description' );
	        $this->enabled = $this->get_option( 'enabled' );
	        $this->get_option( 'cookiepayments_card_api_key' );
	        $this->get_option( 'cookiepayments_card_api_id' );
	        // This action hook saves the settings
	        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );



	        // thank you page hook
	        add_action('woocommerce_thankyou', function($order_id){
	        	$success = isset( $_GET['success'] ) ? $_GET['success'] : 0;
				$order = wc_get_order( $order_id );

				if($success == 1){
					if ($order->data['status'] == 'pending') {
				        $payment_method=$order->get_payment_method();
				        if ($payment_method != "cod")
				        {
				            $order->update_status( 'processing' );
				            wp_redirect( $this->get_return_url( $order ));
				        }
				    }
				}
				
				echo  $this->get_return_url( $order ) . '&success=' . $success;
				// return;
				$order_data = $order->get_data();
         		$payment_method = $order_data['payment_method'];

	         if($order->get_status() == 'pending' && $payment_method == 'cookiepayments_card'){

		         $api_id = get_option('woocommerce_cookiepayments_card_settings')['cookiepayments_card_api_id'];    
		         $api_key = get_option('woocommerce_cookiepayments_card_settings')['cookiepayments_card_api_key']; 

					$order_id = $order_data['id']; 

		         $order_total = $order_data['total'];

		         $order_payment_method = $order_data['payment_method'];

		         $order_billing_first_name = $order_data['billing']['first_name'];
					$order_billing_last_name = $order_data['billing']['last_name'];
					$order_billing_fullname = $order_billing_first_name." ".$order_billing_last_name;

					$order_billing_country = $order_data['billing']['country'];
					$order_billing_email = $order_data['billing']['email'];
					$order_billing_phone = $order_data['billing']['phone'];

					$order_customer_id = $order_data['customer_id'];

					$order_billing_address_1 = $order_data['billing']['address_1'];
					$order_billing_address_2 = $order_data['billing']['address_2'];

					$order_billing_address = $order_billing_address_1." ".$order_billing_address_2;

					$product_names = array();
					$product_ids = array();

				    foreach( $order->get_items() as $item_id => $item ){
				        $product = $item->get_product(); 
				        $product_id = $item->get_product_id();
				        $product_ids[] = $item->get_product_id();
				        $product_names[] = $item->get_name();
				    }

					$headers = array(); 

					array_push($headers, "Content-Type: application/json; charset=utf-8");
					array_push($headers, "ApiKey: $api_key");

					$cookiepayments_card_url = "https://www.cookiepayments.com/pay/ready";

					$product1 = isset($product_names[0]) ? $product_names[0] : '';
					$product2 = isset($product_names[1]) ? $product_names[1] : '';
					$product3 = isset($product_names[2]) ? $product_names[2] : '';
					$product4 = isset($product_names[3]) ? $product_names[3] : '';
					$product5 = isset($product_names[4]) ? $product_names[4] : '';

					$request_data_array = array(
					    'API_ID' => $api_id,
					    'ORDERNO' => $order_id,
					    'PRODUCTNAME' => $product_names[0],
					    'AMOUNT' => $order_total,
					    'BUYERNAME' => $order_billing_fullname,
					    'BUYEREMAIL' => $order_billing_email,
					    'RETURNURL' => home_url() . "/?orderid=$order_id",
					    'PRODUCTCODE' => $product_ids[0],
					    'PAYMETHOD' => 'CARD',
					    'BUYERID' => $order_customer_id,
					    'BUYERADDRESS' => $order_billing_address,
					    'BUYERPHONE' => $order_billing_phone,
					    'ETC1' => $product1,
					    'ETC2' => $product2,
					    'ETC3' => $product3,
					    'ETC4' => $product4,
					    'ETC5' => home_url(),
						'CANCELURL' => home_url(),
						'CANCELULR' => home_url(),
						'HOMEURL' => home_url(),
						'FAILURL' => home_url(),
						'CLOSEURL' => home_url(),
					);

					$cookiepayments_card_json = json_encode($request_data_array, JSON_UNESCAPED_UNICODE);

					$ch = curl_init();

					curl_setopt($ch, CURLOPT_URL, $cookiepayments_card_url);
					curl_setopt($ch, CURLOPT_POST, false);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $cookiepayments_card_json);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
					curl_setopt($ch, CURLOPT_TIMEOUT, 20);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					$response = curl_exec($ch);
					curl_close($ch);

					echo $response;
				}
	        });
 		}

		/**
 		* Plugin options, we deal with it in Step 3 too
 		*/
 		public function init_form_fields(){

	        $this->form_fields = array(
	            'enabled' => array(
	               'title'       => 'Enable/Disable',
	               'label'       => 'Enable CookiePayments Card Gateway',
	               'type'        => 'checkbox',
	               'description' => '',
	               'default'     => 'no'
	            ),
	            'cookiepayments_card_title' => array(
	               'title'       => 'Title',
	               'type'        => 'text',
	               'description' => 'This controls the title which the user sees during checkout.',
	               'default'     => 'CookiePayments Card',
	               'desc_tip'    => true,
	            ),
	            'cookiepayments_card_description' => array(
	               'title'       => 'Description',
	               'type'        => 'textarea',
	               'description' => 'This controls the description which the user sees during checkout.',
	               'default'     => 'Pay with cookiepayments card payment gateway.',
	            ),
	            'cookiepayments_card_api_id' => array(
	               'title'       => 'API ID',
	               'type'        => 'text'
	            ),
	            
	            'cookiepayments_card_api_key' => array(
	               'title'       => 'API Key',
	               'type'        => 'text'
	            ),
	           
	        );
      
	 	}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {

	        global $woocommerce;
	        // we need it to get any order detailes
	        $order = wc_get_order( $order_id );
			return array(
		        'result' => 'success',
		        'redirect' => $this->get_return_url( $order )
		    );
					
	 	}
 	}
}