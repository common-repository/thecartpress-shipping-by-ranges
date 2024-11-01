<?php
/*
Plugin Name: TheCartPress Shipping by Ranges
Plugin URI: http://extend.thecartpress.com/ecommerce-plugins/shipping-by-ranges/
Description: Calculates Shipping cost by range prices
Version: 1.1
Author: TheCartPress team
Author URI: http://thecartpress.com
License: GPL
Parent: thecartpress
*/

/**
 * This file is part of TheCartPress.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
require_once( WP_PLUGIN_DIR . '/thecartpress/classes/TCP_Plugin.class.php' );

class TCPShippingByRanges extends TCP_Plugin {

	function getTitle() {
		return 'Shipping by Ranges';
	}
	
	function getDescription() {
		return __( 'Calculate the shipping cost using a table of ranges of prices.<br>Author: <a href="http://thecartpress.com" target="_blank">TheCartPress team</a>', 'tcp' );
	}

	function getCheckoutMethodLabel( $instance, $shippingCountry, $shoppingCart ) {
		$data = tcp_get_shipping_plugin_data( get_class( $this ), $instance );
		$title = isset( $data['title'] ) ? $data['title'] : '';
		$cost = $this->getCost( $instance, $shippingCountry, $shoppingCart );
		return sprintf( __( '%s. Cost: %s', 'tcp' ), $title, tcp_format_the_price( $cost ) );
	}

	function showEditFields( $data ) {
		$ranges = isset( $data['shipping-by-ranges'] ) ? $data['shipping-by-ranges'] : false; ?>
<script>
function tcp_delete_row(id) {
	jQuery('#tcp_range_row_' + id).remove();
}
</script>
<table class="widefat fixed">
<thead>
<tr>
	<th scope="col" class="manage-column"><?php _e( 'Range', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column"><?php _e( 'Cost', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column"><?php _e( 'Type', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column" style="width: 20%;">&nbsp;</th>
</tr>
</thead>

<tfoot>
<tr>
	<th scope="col" class="manage-column"><?php _e( 'Range', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column"><?php _e( 'Cost', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column"><?php _e( 'Type', 'tcp-sbr' ); ?></th>
	<th scope="col" class="manage-column" style="width: 20%;">&nbsp;</th>
</tr>
</tfoot>
<tbody>
<?php if ( is_array( $ranges ) && count( $ranges ) > 0 )
	foreach( $ranges as $id => $range ) : ?>
		<tr id="tcp_range_row_<?php echo $id; ?>">
		<td><?php _e( 'equal or less than', 'tcp_sbr' ); ?><input type="text" name="tcp_range[]" value="<?php echo tcp_number_format( $range['range'] ); ?>" id="tcp_range_<?php echo $id; ?>" size="10em" />&nbsp;<?php tcp_the_currency(); ?></td>
		<td><input type="text" name="tcp_cost[]" value="<?php echo tcp_number_format( $range['cost'] ); ?>" id="tcp_cost_<?php echo $id; ?>" size="10em" /></td>
		<td>
			<select name="tcp_type[]" id="tcp_type_<?php echo $id; ?>">
				<option value="fix" <?php selected( $range['type'], 'fix' ); ?>><?php tcp_the_currency(); ?></option>
				<option value="per" <?php selected( $range['type'], 'per' ); ?>>%</option>
			</select>
		</td>
		<td><input type="button" onclick="tcp_delete_row(<?php echo $id; ?>)" value="delete" class="button-secondary"/></td>
		</tr>
	<?php endforeach; ?>
		<tr>
		<td><?php _e( 'equal or less than', 'tcp_sbr' ); ?> <input type="text" name="tcp_range[]" placeholder="<?php tcp_number_format_example(); ?>" id="tcp_range" size="10em" />&nbsp;<?php tcp_the_currency(); ?></td>
		<td><input type="text" name="tcp_cost[]" placeholder="<?php tcp_number_format_example(); ?>" id="tcp_cost" size="10em" /></td>
		<td>
			<select name="tcp_type[]" id="tcp_type_<?php echo $id; ?>">
				<option value="fix"><?php tcp_the_currency(); ?></option>
				<option value="per">%</option>
			</select>
		</td>
		<td>&nbsp;</td>
		</tr>
</tbody>
</table>
		<?php
	}

	function saveEditFields( $data ) {
		$ranges	= isset( $_REQUEST['tcp_range'] ) ? $_REQUEST['tcp_range'] : array();
		$costs	= isset( $_REQUEST['tcp_cost'] ) ? $_REQUEST['tcp_cost'] : array();
		$types	= isset( $_REQUEST['tcp_type'] ) ? $_REQUEST['tcp_type'] : array();
		$data['shipping-by-ranges'] = array();
		if ( is_array( $ranges ) && count( $ranges ) > 0 ) {
			foreach( $ranges as $id => $range ) {
				$range = tcp_input_number( $range );
				$cost = tcp_input_number( $costs[$id] );
				$type = $types[$id];
				if ( $range > 0 ) {
					$data['shipping-by-ranges'][$range] = array(
						'range'	=> $range,
						'cost'	=> $cost,
						'type'	=> $type,
					);
				}
			}
		}
		sort( $data['shipping-by-ranges'] );
		return $data;
	}
	
	function getCost( $instance, $shippingCountry, $shoppingCart ) {
		$data = tcp_get_shipping_plugin_data( get_class( $this ), $instance );
		$ranges = $data['shipping-by-ranges'];
		if ( is_array( $ranges ) && count( $ranges ) > 0 ) {
			if ( $shoppingCart === false ) $shoppingCart = TheCartPress::getShoppingCart();
			$total = $shoppingCart->getTotal();
			foreach( $ranges as $id => $range ) {
				if ( $range['range'] > $total ) {
					$selected_range = $id;
					break;
				}
			}
			if ( ! isset( $selected_range ) ) {
				end( $ranges );
				$selected_range = key( $ranges );
			}
			$range_info = $ranges[$selected_range];
			if ( $range_info['type'] == 'fix' ) {
				return $range_info['cost'];
			} else {
				$cost = $total * $range_info['cost'] / 100 ;
				return $cost;
			}
		}
		return 0;
	}

	function init() {
		tcp_register_shipping_plugin( 'TCPShippingByRanges' );
	}

	function __construct() {
		add_action( 'init', array( $this, 'init' ), 20 );
	}
}

new TCPShippingByRanges();
?>
