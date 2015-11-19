<?php
/**
 * Sales report email
 *
 * @author        WooThemes
 * @version       1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
	<style type="text/css">
		h1, h2 { text-align: center !important; }
		#template_footer p { text-align: center !important; }
		.info_g td.date { background-color: #eeeeee; !important; }
		.sales_report {border-collapse:collapse;border-spacing:0;margin:0px auto; width: 100%;}
		.sales_report td {font-family:Helvetica, Arial, sans-serif;color:#343434; font-size:12px;padding:10px 5px;border-style:none;overflow:hidden;word-break:normal;}
		.sales_report th {font-family:Helvetica, Arial, sans-serif;font-size:18px;font-weight:normal;padding:10px 5px;border-style:none;overflow:hidden;word-break:normal;}
		.sales_report .info_w { }
		.sales_report .info_g { background-color: #eeeeee; !important; }
		.sales_report .venue { text-transform: uppercase; font-weight: bold; font-size:18px;letter-spacing: 1.5;background-color:#173a5e;color:#ffffff; }

		.sales_report .date { text-align:left; width: 20%; padding-left: 8px; }
		.sales_report .artist { text-align:left; width: 45%; }
		.sales_report .type { text-align:left; width: 25%; padding-left: 8px; }
		.sales_report .sales { text-align:right; width: 10%; padding-right: 8px; }

		.sales_report .total_items, .sales_report .items_total { background-color: #f5bb91; font-weight: bold; font-size: 12px; }
		.sales_report .sales_total, .sales_report .total_sales { background-color: #ec742d; font-weight: bold; font-size: 12px; }

		.sales_report .items_total { text-align: right; padding-right: 8px; }
		.sales_report .sales_total { text-align: right; padding-right: 8px; }

		.sales_report .total_items { padding-left: 8px; }
		.sales_report .total_sales { padding-left: 8px; }
	</style>
<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php
$events = array_values($events);

if(count($events) > 0) :
	$total_revenue = 0;
	$total_items = 0;
	?>
	<table class="sales_report">
		<tr>
			<th class="venue" colspan="4"><?php echo $events[0]['venue_name']; ?></th>
		</tr>
	<?php
		$rows = count($events)-1;
		for($row = 0; $row <= $rows; $row++) :
			$total_revenue += $events[$row]['revenue'];
			$total_items += $events[$row]['total_tickets'];
			?>
			<tr class="info_<?php echo (($row % 2) == 0) ? 'w' : 'g'; ?>">
				<td class="date"><?php echo date('m.d.y', strtotime($events[$row]['date'])); ?></td>
				<td class="artist"><?php echo $events[$row]['artist']; ?></td>
				<td class="type"><?php echo $events[$row]['ticket_type']; ?></td>
				<td class="sales"><?php echo $events[$row]['total_tickets']; ?></td>
			</tr>
		<?php endfor; ?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr class="items">
			<td class="blank" colspan="2">&nbsp;</td>
			<td class="total_items">Total Items</td>
			<td class="items_total"><?php echo $total_items; ?></td>
		</tr>
		<tr class="bottom_line">
			<td class="blank" colspan="2">&nbsp;</td>
			<td class="total_sales">Total Sales</td>
			<td class="sales_total">$<?php echo number_format((int)$total_revenue, 2); ?></td>
		</tr>
	</table>
<?php endif; ?>
<?php do_action( 'woocommerce_email_footer' ); ?>