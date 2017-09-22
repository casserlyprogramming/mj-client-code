<?php
/*
  Plugin Name: MJ Client Code
  Plugin URI: https://github.com/casserlyprogramming/mj-client-code 
  Description: Add a client code to the customer in Woocommerce 
  Version: 1.0.0
  Author: Daniel Casserly
  Author URI: http://dandalfprogramming.blogspot.co.uk/
 */

// Code adapted from :
// https://www.jnorton.co.uk/woocommerce-custom-fields

// Actions
add_action( 'woocommerce_process_shop_order_meta', 'mj_woocommerce_process_shop_order', 10, 2 );
add_action( 'woocommerce_checkout_update_order_meta', 'mj_custom_checkout_field_update_order_meta' );

// Filters
add_filter( 'woocommerce_customer_meta_fields' , 'mj_user_profile_custom_fields' );
add_filter( 'woocommerce_admin_billing_fields' , 'mj_order_admin_custom_fields' );
add_filter( 'woocommerce_found_customer_details', 'mj_add_custom_fields_to_admin_order', 10, 1 );
add_filter( 'woocommerce_checkout_fields' , 'mj_add_custom_fields_to_checkout' );
add_filter("woocommerce_checkout_fields", "mj_order_fields");

//ADD CUSTOM USER FIELDS TO WOOCOMMERCE BILLING ADDRESS IN USER PROFILE
function mj_user_profile_custom_fields( $fields ) {
	$fields['billing']['fields']['billing_customer_code'] = array(
		'label' => __( 'Customer Code', 'woocommerce' ),
		'description' => '',
	);

	return $fields;
}

//ADD CUSTOM USER FIELDS TO ADMIN ORDER SCREEN
function mj_order_admin_custom_fields( $fields ) {
	global $theorder;
	$fields['customer_code'] = array(
		'label' => __( 'Customer Code', '_billing_customer_code' ),
		'value'=> get_post_meta( $theorder->id, '_billing_customer_code', true ),
		'show'  => true,
		//'class'   => '',
		'wrapper_class' => 'form-field-wide',
		'style' => '',
		//'id' => '',
		//'type' => '',
		//'name' => '',
		//'placeholder' => '',
		//'description' => '',
		//'desc_tip' => bool,
		//'custom_attributes' => '',
	);
	return $fields;
}

//LOAD CUSTOMER USER FIELDS VIA AJAX ON ADMIN ORDER SCREEN FROM CUSTOMER RECORD
function mj_add_custom_fields_to_admin_order($customer_data){
	$user_id = $_POST['user_id'];
	$customer_data['billing_customer_code'] = get_user_meta( $user_id, 'billing_customer_code', true );
	return $customer_data;
}

//SAVE META DATA / CUSTOM FIELDS WHEN EDITING ORDER ON ADMIN SCREEN
function mj_woocommerce_process_shop_order ( $post_id, $post ) {

	if ( empty( $_POST['woocommerce_meta_nonce'] ) ) {
		return;
	}

	if(!wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' )){
		return;
	}

	if(!isset($_POST['cxccoo-save-billing-address-input'])){
		return;
	}

	if(isset($_POST['_billing_customer_code'])){
		update_user_meta( $_POST['user_ID'], 'billing_customer_code', sanitize_text_field( $_POST['_billing_customer_code'] ) );
	}

}

// SHOW CUSTOM FIELDS ON CHECKOUT
function mj_add_custom_fields_to_checkout( $fields ) {
	$fields['billing']['billing_customer_code'] = array(
		'label'     => __('Customer Code', 'woocommerce'),
		'placeholder'   => _x('Customer Code', 'placeholder', 'woocommerce'),
		'required'  => false,
		'class'     => array('form-row-first'),
		'clear'     => false
	);

	$user_id = get_current_user_id();
	if(isset($user_id)){
		$fields['billing']['save_address'] = array(
			'label'     => __('Save Address Details', 'woocommerce'),
			'placeholder'   => _x('Save Address Details', 'placeholder', 'woocommerce'),
			'required'  => false,
			'type'  => 'checkbox',
			'default' => 1
		);
	}

	return $fields;
}


function mj_order_fields($fields) {

    $order = array(
        "billing_first_name",
        "billing_last_name",
        "billing_email",
        "billing_phone",
        "billing_company",
        "billing_customer_code",
        "billing_address_1",
        "billing_address_2",
        "billing_postcode",
        "billing_state",
        "billing_country",
        "save_address",
    );
    foreach($order as $field)
    {
        $ordered_fields[$field] = $fields["billing"][$field];
    }

    $fields["billing"] = $ordered_fields;
    return $fields;

}

//SAVE CUSTOM USER FIELDS TO ORDER ON CHECKOUT
function mj_custom_checkout_field_update_order_meta( $order_id ) {
	$user_id = get_current_user_id();
	//save po and vat numbers
	if ( ! empty( $_POST['_billing_customer_code'] ) ) {
		update_post_meta( $order_id, '_billing_customer_code', sanitize_text_field( $_POST['_billing_customer_code'] ) );
	}

	//if customer logged in and save address checkbox checked, save customer data to customer profile
	if(!isset( $_POST['save_address'] )){
		return;
	}
	if ( ! empty( $_POST['_billing_customer_code'] && isset($user_id) ) ) {
		update_user_meta( $user_id, 'billing_customer_code', sanitize_text_field( $_POST['_billing_customer_code'] ) );
	}

}

