<?php
/*
Plugin Name: WC Auto-Ship TaxNOW
Plugin URI: http://wooautoship.com
Description: Integrate WC Auto-Ship with TaxNOW for WooCommerce
Version: 1.0
Author: Patterns in the Cloud
Author URI: http://patternsinthecloud.com
License: Single-site
*/

define( 'WC_Autoship_TaxNOW_Version', '1.0.0' );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) && is_plugin_active( 'taxnow_woo/taxnow.php' ) ) {
	
	function wc_autoship_taxnow_install() {
	}
	register_activation_hook( __FILE__, 'wc_autoship_taxnow_install' );
	
	function wc_autoship_taxnow_deactivate() {
	
	}
	register_deactivation_hook( __FILE__, 'wc_autoship_taxnow_deactivate' );
	
	function wc_autoship_taxnow_uninstall() {

	}
	register_uninstall_hook( __FILE__, 'wc_autoship_taxnow_uninstall' );
	
	function wc_autoship_taxnow_add_tax_rates( $tax_rates, $schedule_id ) {
		// taxnow_update_woo_order_meta
		if ( class_exists( 'class_taxNOW_woo' ) && class_exists( 'WC_Autoship_Schedule' ) ) {
			// Create TaxNOW instance
			$taxnow_woo = new class_taxNOW_woo();
			
			// Get autoship schedule
			$schedule = new WC_Autoship_Schedule( $schedule_id );
			
			// Get autoship customer
			$customer = $schedule->get_customer();
			
			// Create service
			$service = $taxnow_woo->create_service( 'TaxServiceSoap', false );
			$request = new GetTaxRequest();
			$request->setDocDate( date( 'Y-m-d', current_time( 'timestamp' ) ) );
			$request->setDocCode( '' );
			$request->setCustomerCode( $customer->get_email() );
			$request->setCompanyCode( get_option( 'tnwoo_company_code' ) );
			$request->setDocType( DocumentType::$SalesOrder );
			$request->setDetailLevel( DetailLevel::$Tax );
			$request->setCurrencyCode( get_option('woocommerce_currency') );
			$request->setBusinessIdentificationNo( get_option( 'tnwoo_business_vat_id') );
			// Origin address
			$origin = new Address();
			$origin->setLine1( get_option( 'tnwoo_origin_street' ) );
			$origin->setCity( get_option( 'tnwoo_origin_city' ) );
			$origin->setRegion( get_option( 'tnwoo_origin_state' ) );
			$origin->setPostalCode( get_option( 'tnwoo_origin_zip' ) );
			$origin->setCountry( get_option( 'tnwoo_origin_country' ) );
			$request->setOriginAddress( $origin );
			// Destination address
			$destination = new Address();
			$destination->setLine1( $customer->get( 'shipping_address_1' ) );
			$destination->setCity( $customer->get( 'shipping_city' ) );
			$destination->setRegion( $customer->get( 'shipping_state' ) );
			$destination->setPostalCode( $customer->get( 'shipping_postcode' ) );
			$destination->setCountry( $customer->get( 'shipping_country' ) );
			$request->setDestinationAddress( $destination );
			
			// Lines items
			$items = $schedule->get_items();
			$lines = array();
			$global_tax_code = get_option( 'tnwoo_default_tax_code' );
			foreach ( $items as $i => $item ) {
				// Get WooCommerce product ID
				$product_id = $item->get_product_id();
				// Create line item
				$line = new Line();
				$line->setItemCode( $product_id );
				$line->setDescription( $product_id );
				$tax_code = get_post_meta( $product_id, '_taxnow_taxcode', true );
				$line->setTaxCode( ! empty( $tax_code ) ? $tax_code : $global_tax_code );
				$line->setQty( (int) $item->get_quantity() );
				$line->setAmount( (float) $item->get_autoship_price() );
				$line->setNo ( $i+1 );
				$line->setDiscounted ( 0 );
				$lines[] = $line;
			}
			$request->setLines( $lines );
			
			// Pretax discount
			$discount_pretax = 0.0;
			
			// Send request
			$taxnow_woo->log_add_entry( 'calculate_tax','request', $request );
			try {
				$response = $service->getTax( $request );
				$taxnow_woo->log_add_entry( 'calculate_tax','response', $response );
				if( $response->getResultCode() == SeverityLevel::$Success ) {
					foreach( $response->GetTaxLines() as $l => $TaxLine ) {
						foreach( $TaxLine->getTaxDetails() as $d => $TaxDetail ) {
							// Create WooCommerce tax rate
							$tax_rate = array(
								'rate' => 100.0 * $TaxDetail->getRate(),
								'label' => $TaxDetail->getTaxName(),
								'shipping' => 'no',
								'compound' => 'no'
							);
							$tax_rates[ "wc_autoship_taxnow_{$l}_{$d}" ] = $tax_rate;
// 							$lineitem_taxdetail = new class_taxnow_woo_tax_details();
// 							$lineitem_taxdetail->set_tax_name( $TaxDetail->getTaxName() );
// 							$lineitem_taxdetail->set_tax_rate( $TaxDetail->getRate() );
// 							$lineitem_taxdetail->set_juris_name( $TaxDetail->getJurisName() );
// 							$lineitem_taxdetail->set_juris_type( $TaxDetail->getJurisType() );
// 							$lineitem_taxdetail->set_region( $TaxDetail->getRegion() );
// 							$lineitem_taxdetail->set_taxable( $TaxDetail->getTaxable() );
// 							$tax_details[] = $lineitem_taxdetail;
						}
	// 					$items[ $corr_keys[ $TaxLine->GetNo() ] ]->set_tax(
	// 							$TaxLine->GetRate(), $TaxLine->GetTax(), $tax_details
	// 					);
					}
				}
			} catch( Exception $e ) {
				$taxnow_woo->log_add_entry( 'calculate_tax','exception', $e->getMessage() );
			}
		}
		// Return tax rates
		return $tax_rates;
	}
	add_filter( 'wc_autoship_schedule_tax_rates', 'wc_autoship_taxnow_add_tax_rates', 10, 2 );
}
