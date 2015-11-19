<?php
/**
 * Template Name: Send tickets
 *
 */
get_header();

//echo '<pre>'; print_r($_REQUEST); echo '</pre>';


$product_id = $_REQUEST['product_id'][0];
$event_id = get_post_meta($product_id, '_tribe_wooticket_for_event', true);
$event = get_post($event_id);
$title = urlencode($event->post_title);
$message = urlencode("I am going to see ".$event->post_title.", you should too, get your ticket now and join me for a fun night out!");
$url = urlencode(get_site_url().'/event/'.$event->post_name.'/');

if (has_post_thumbnail( $event_id ) ) {
$image = wp_get_attachment_image_src( get_post_thumbnail_id( $event_id ), 'single-post-thumbnail' );
$image = $image[0];
}

?>

<div class="etitle"><?php echo $_GET['ename']; ?></div>
<div id="form">
<input type="text" placeholder="First Name" id="fname" name="first name"></br></br>
<input type="text" placeholder="Last Name" id="lname" name="last name"></br></br>
<input type="text" placeholder="Email Address" id="emailadd" name="emailadd"></br></br>
<input type="hidden" name="event_id" id="event_id" value="<?php echo $event_id; ?>">
<input type="hidden" name="user_id" id="user_id" value="<?php echo get_current_user_id(); ?>">
<input type="hidden" name="ticket_id" id="ticket_id" value="<?php echo $product_id; ?>">
GA Tickets
<select class="htmh_ticket_number" id="ga_tickets" name="ga_tickets">
<?php for($i=0; $i <= 20; $i++){ ?>
<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php } ?>

</select>
</br></br>
VIP Tickets
<select class="htmh_ticket_number" id="vip_tickets" name="vip_tickets">
<?php for($i=0; $i <= 20; $i++){ ?>
<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php } ?>
</select></br></br>

All Access Tickets

<select class="htmh_ticket_number" id="all_tickets" name="all_tickets">
<?php for($i=0; $i <= 20; $i++){ ?>
<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
<?php } ?>

</select></br></br>

<button id="send" >SEND TICKETS</button>
</div>
</form>
<div id="tab2" style="display:none">
<div id="lab">COMP TICKET ORDER</div>
<table class="capitalise">
<tr>
<td>Name</td>
<td id="name"></td>
</tr>
<tr>
<td>Email</td>
<td id="emaila"></td>
</tr>
<tr>
<td>GA Tickets</td>
<td id="ga"></td>
</tr>
<tr>
	<td>VIP Tickets</td>
	<td id="vip"></td>
</tr>
<tr>
<td>All Access Tickets</td>
<td id="all"> </td>
</tr>

</table>
<div class="clearfix"></div>
<div>
	<a href="javascript:void(0)" id="confirm">CONFIRM  </a> |
	<a href="javascript:void(0)" id="edit">EDIT</a> | 
	<a href="javascript:void(0)" id="cancel">CANCEL</a>
</div>

<div class="clearfix"></div>

<div id="send_mail" style="display:none">
	<div>Send Tickets With</div>
	<!-- <a href="javascript:void(0)"  >MMS</a>  |  -->
	<a href="javascript:void(0)" id="email">EMAIL</a>
</div>
</div>
<!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script> -->
<script>
jQuery('document').ready(function($){
	$('#send').click(function(){
		var fname = $('#fname').val();
		var lname = $('#lname').val();
		var email = $('#emailadd').val();
		var ga_tickets = $('#ga_tickets').val();
		var vip_tickets = $('#vip_tickets').val();
		var all_tickets = $('#all_tickets').val();

		if(fname == '' && lname == ""){
			alert('Please enter name!');
			return false;
		}

		if(ga_tickets == '0' && all_tickets == '0' && vip_tickets == '0' ){
			alert('Please select Tickets!');
			return false;
		}

		
		$('#tab2').show();
		$('#form').hide();

		$('#name').text(fname + ' ' + lname);
		$('#emaila').text(email);
		$('#ga').text(ga_tickets);
		$('#vip').text(vip_tickets);
		$('#all').text(all_tickets);

	});

	$('#edit').click(function(){
		$('#tab2').hide();
		$('#form').show();
	});
	$('#confirm').click(function(){
		$('#send_mail').toggle();
		//$('#confirm').attr('disabled','disabled');
	});

$('#email').click(function(){
	var fname = $('#fname').val();
	var lname = $('#lname').val();
	var email = $('#emailadd').val();
	var ga_tickets = $('#ga_tickets').val();
	var vip_tickets = $('#vip_tickets').val();
	var all_tickets = $('#all_tickets').val();
	var event_id = $('#event_id').val();
	var user_id = $('#user_id').val();
	var ticket_id = $('#ticket_id').val();
$.ajax({
	type: 'POST',
	url: '<?php echo dirname(get_template_directory_uri()); ?>/fitness-child/m-data.php',
	data: 'fname='+fname+'&lname='+lname+'&ga_tickets='+ga_tickets+'&vip_tickets='+vip_tickets+'&all_tickets='+all_tickets+'&event_id='+event_id+'&user_id='+user_id+'&email='+email+'&ticket_id='+ticket_id,
	success: function(response) {
		//alert('sucess.');
	},
	error: function(){
		//alert('fail');
	}
  });
  });
});
</script>
<?php get_footer(); ?>