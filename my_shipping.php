<?php
/*
 Plugin Name: WP E-Commerce Extra Shipping Options
 Description: Custom Shipping Options For WP E-Commerce
 Author: Dijitul Developments
 Author URI: http://www.dijituldevelopments.co.uk/
 Version: 0.1.5
*/

class my_shipping {

	var $internal_name;
	var $name;
	var $is_external;

	function my_shipping () {

		// An internal reference to the method - must be unique!
		$this->internal_name = "my_shipping";
		
		// $this->name is how the method will appear to end users
		$this->name = "Special Delivery";

		// Set to FALSE - doesn't really do anything :)
		$this->is_external = FALSE;

		return true;
	}
	
	/* You must always supply this */
	function getName() {
		return $this->name;
	}
	
	/* You must always supply this */
	function getInternalName() {
		return $this->internal_name;
	}
	
	
	/* Use this function to return HTML for setting any configuration options for your shipping method
	 * This will appear in the WP E-Commerce admin area under Products > Settings > Shipping
         *
	 * Whatever you output here will be wrapped inside the right <form> tags, and also
	 * a <table> </table> block */

	function getForm() {

		$shipping = get_option('my_shipping_options');
		
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		UK - 1st Class:<br/>';
		$output .= '		<input type="text" name="shipping[first]" value="'.htmlentities($shipping['first']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		UK - Recorded Delivery:<br/>';
		$output .= '		<input type="text" name="shipping[recorded]" value="'.htmlentities($shipping['recorded']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		UK - Special Delivery (next day):<br/>';
		$output .= '		<input type="text" name="shipping[special]" value="'.htmlentities($shipping['special']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		UK - Saturday Guaranteed Special Delivery:<br/>';
		$output .= '		<input type="text" name="shipping[saturday]" value="'.htmlentities($shipping['saturday']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';		
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		Europe - International Signed For:<br/>';
		$output .= '		<input type="text" name="shipping[europe]" value="'.htmlentities($shipping['europe']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '	<td>';
		$output .= '		Rest of the World - International Signed For:<br/>';
		$output .= '		<input type="text" name="shipping[international]" value="'.htmlentities($shipping['international']).'"><br/>';
		$output .= '	</td>';
		$output .= '</tr>';

		return $output;
	}
	


	/* Use this function to store the settings submitted by the form above
	 * Submitted form data is in $_POST */

	function submit_form() {

		if($_POST['shipping'] != null) {

			$shipping = (array)get_option('my_shipping_options');
			$submitted_shipping = (array)$_POST['shipping'];

			update_option('my_shipping_options',array_merge($shipping, $submitted_shipping));

		}

		return true;

	}
	
	/* If there is a per-item shipping charge that applies irrespective of the chosen shipping method
         * then it should be calculated and returned here. The value returned from this function is used
         * as-is on the product pages. It is also included in the final cart & checkout figure along
         * with the results from GetQuote (below) */

	function get_item_shipping(&$cart_item) {

		global $wpdb;

		// If we're calculating a price based on a product, and that the store has shipping enabled

		$product_id = $cart_item->product_id;
		$quantity = $cart_item->quantity;
		$weight = $cart_item->weight;
		$unit_price = $cart_item->unit_price;

    		if (is_numeric($product_id) && (get_option('do_not_use_shipping') != 1)) {

			$country_code = $_SESSION['wpsc_delivery_country'];

			// Get product information
      			$product_list = $wpdb->get_row("SELECT *
			                                  FROM `".WPSC_TABLE_PRODUCT_LIST."`
				                         WHERE `id`='{$product_id}'
			                                 LIMIT 1",ARRAY_A);
			/*
       			// If the item has shipping enabled
      			if($product_list['no_shipping'] == 0) {

        			if($country_code == get_option('base_country')) {

					// Pick up the price from "Local Shipping Fee" on the product form
          				$additional_shipping = $product_list['pnp'];

				} else {

					// Pick up the price from "International Shipping Fee" on the product form
          				$additional_shipping = $product_list['international_pnp'];

				}          

        			$shipping = $quantity * $additional_shipping;

			} else {
			*/
					//djb31st edited the rest out as we don't appear to use postage on the site
        			//if the item does not have shipping
        			$shipping = 0;

			//}

		} else {

      			//if the item is invalid or store is set not to use shipping
			$shipping = 0;

		}

    		return $shipping;	
	}
	


	/* This function returns an Array of possible shipping choices, and associated costs.
         * This is for the cart in general, per item charges (As returned from get_item_shipping (above))
         * will be added on as well. */

	function getQuote() {

		global $wpdb, $wpsc_cart;

		// This code is let here to show how you can access delivery address info
		// We don't use it for this skeleton shipping method

		if (isset($_POST['country'])) {

			$country = $_POST['country'];
			$_SESSION['wpsc_delivery_country'] = $country;

		} else {

			$country = $_SESSION['wpsc_delivery_country'];

		}
		
		//get county region for europe
		$region = $wpdb->get_row("SELECT continent
									  FROM `".WPSC_TABLE_CURRENCY_LIST."`
								 WHERE `isocode`='{$country}'
									 LIMIT 1",ARRAY_A);
		
		
		// Retrieve the options set by submit_form() above
		$my_shipping_rates = get_option('my_shipping_options');
		
		$region = $region['continent'];
					  
		// Return an array of options for the user to choose
		// The first option is the default
		//list of eu countries
		//turns out this already exists in software
		//does it match http://www.royalmail.com/portal/rm/content3?catId=400036&mediaId=53600700
		//looks like it does
	
		if($country=="UK"||$country=="GB"||$country=="IM") //uk, great britan, isle of man
		{
				if($my_shipping_rates['special']!=0)
					$ret_arr["UK - Special Delivery (next day)"] = (float) $my_shipping_rates['special'];
				if($my_shipping_rates['recorded']!=0)
					$ret_arr["UK - Recorded Delivery"] = (float) $my_shipping_rates['recorded'];
				if($my_shipping_rates['first']!=0)
					$ret_arr["UK - 1st Class"] = (float) $my_shipping_rates['first'];
				if($my_shipping_rates['saturday']!=0)
					$ret_arr["UK - Saturday Guaranteed Special Delivery"] = (float) $my_shipping_rates['saturday'];
				
				return $ret_arr;
		}
		elseif($region=="europe")
		{
			if($my_shipping_rates['europe']!=0)
				return array ("Europe - International Signed For" => (float) $my_shipping_rates['europe']);
		}
		else
		{
			if($my_shipping_rates['international']!=0)
				return array ("Rest of the World - International Signed For" => (float) $my_shipping_rates['international']);
		}

	}
	
	
} 

function my_shipping_add($wpsc_shipping_modules) {

	global $my_shipping;
	$my_shipping = new my_shipping();

	$wpsc_shipping_modules[$my_shipping->getInternalName()] = $my_shipping;

	return $wpsc_shipping_modules;
}
	
add_filter('wpsc_shipping_modules', 'my_shipping_add');
?>
