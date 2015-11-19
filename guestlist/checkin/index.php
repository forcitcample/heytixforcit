<?php
//define('WP_USE_THEMES', false);
require('../../wp-load.php');
require('TribeWooTicketsExtended.php');

require_once('../../wp-includes/class-phpass.php');
require_once('includes/phpqrcode/qrlib.php');
$f3 = require('fatfree/lib/base.php');
$f3->config('heytix_checkin.cfg');


class Controller {

	protected $db;
	protected $_secrets = null;
	protected $_public_routes = array(
		'/api/login','/api/online', '/api/events/@event_id/checkedin/counts',
		'/api/events/@event_id/guestlist', '/api/tickets/qrcode/@security_code',
		'/api/tickets/qrcode/@security_code/@size', '/api/tickets/qrcodeorder/@order_id',
		'/api/events/uid/@user_id', '/api/tickets/gifted_ticket/qrcode/@security_code',
		'/api/tickets/gifted_ticket/qrcode/@security_code/token/@token',
		'/api/tickets/gifted_ticket/qrcode/@security_code/token/@token/updated'
	);
	protected $_modified_after = '';

	public $ticket_sql;


	function beforeRoute($f3,$params) {
		$headers = apache_request_headers();
		date_default_timezone_set('America/New_York');
		if(isset($_GET['When-Modified-After']) || array_key_exists('When-Modified-After', $_GET)) {
			$headers['When-Modified-After'] = $_GET['When-Modified-After'];
		}
		if(array_key_exists('When-Modified-After', $headers)) {
			if(is_string($headers['When-Modified-After'])) {
				$headers['When-Modified-After'] = trim($headers['When-Modified-After']);
				$this->_modified_after = date('Y-m-d H:i:s', strtotime('@' . $headers['When-Modified-After']));
				error_log('Local Time: ' . $this->_modified_after);
			}
		}
		if(in_array($f3->get('PATTERN'), $this->_public_routes) === false) {
			if(array_key_exists('Authorization', $headers) !== false) {
				$parts = explode(' ', $headers['Authorization']);
				$token = $parts[1];
				if($token !=md5(implode(',', $this->_secrets))) {
					$f3->error(401);
				}
			} else {
				$f3->error(401);
			}
		}
	}

	function __construct() {
		$f3=Base::instance();
		$db_database=$f3->get('db_database');
		$db_user = $f3->get('db_user');
		$db_password = $f3->get('db_password');
		$this->db=new DB\SQL(
			'mysql:host=localhost;port=3306;dbname=' . $db_database,
			$db_user,
			$db_password
		);


		$this->_secrets = array(
			'asLJLF()&0879,jnkjasdf*&987sdf,naljijqm988yd90fs777(*&)(&(*&fasfi897',
			'aldfjks*jkhbsfsjb234*(&(YHSDIFUH$kjhw5wbjhrludah87YFvuidagf',
			'OIfhakJDSPYU(*F(*VCH&*VY98UFS**F&_*)(U(*YREIHK3JRHLIRUHYOIUH98S&()*7Y9U(*S(*FYIUVFHIAI*YIOUCH*&OZTCGVHWR',
			date('m/Y')
		);

		$this->ticket_sql  = "
		select o_fname.order_id, e.event_id, tp.ticket_title as ticket_type, s.post_id, s.security_code as id, ifnull(c.checked_in,0) as checked_in, CONCAT(o_fname.first_name, ' ',  o_lname.last_name) as name, um.photo_url, op.sku, o_user.user_id from
		(
			select m.post_id, m.meta_value as security_code, p.post_modified as post_modified from wp_postmeta as m left join wp_posts as p on p.ID = m.post_id where m.meta_key='_tribe_wooticket_security_code'
		) as s
		left join 
		(
			select post_id, meta_value as checked_in from wp_postmeta where meta_key='_tribe_wooticket_checkedin'
		) as c on s.post_id = c.post_id
		left join
		(
			select post_id, meta_value as event_id from wp_postmeta where meta_key='_tribe_wooticket_event'
		) as e on s.post_id = e.post_id
		left join
		(
			select t.post_id as post_id, wp_posts.post_title as ticket_title from 
			(
				select post_id, meta_value as ticket_product_id from wp_postmeta where meta_key='_tribe_wooticket_product'
			) as t
			left join wp_posts on ID = t.ticket_product_id
		) as tp on s.post_id = tp.post_id
		left join
		(
			select wp_postmeta.post_id as post_id, of.post_id as order_id, meta_value as first_name from wp_postmeta
				inner join
				(
					select post_id, meta_value as ticket_product_id from wp_postmeta where meta_key='_tribe_wooticket_order'
				) as of on wp_postmeta.post_id = of.ticket_product_id
			where meta_key = '_billing_first_name'
		) as o_fname on o_fname.order_id=s.post_id
		left join
		(
			select wp_postmeta.post_id as post_id, ol.post_id as order_id, meta_value as last_name from wp_postmeta
				inner join
				(
					select post_id, meta_value as ticket_product_id from wp_postmeta where meta_key='_tribe_wooticket_order'
				) ol on wp_postmeta.post_id = ol.ticket_product_id
			where meta_key = '_billing_last_name'
		) as o_lname on o_lname.order_id=s.post_id
		left join
		(
			select wp_postmeta.post_id as post_id, ou.post_id as order_id, meta_value as user_id from wp_postmeta
				inner join
				(
					select post_id, meta_value as ticket_product_id from wp_postmeta where meta_key='_tribe_wooticket_order'
				) as ou on wp_postmeta.post_id = ou.ticket_product_id
			where meta_key = '_customer_user'
		) as o_user on o_user.order_id=s.post_id
		left join
		(
			select wp_usermeta.user_id as user_id, wp_usermeta.meta_value as photo_url from wp_usermeta WHERE meta_key='fb_profile_picture'
		) as um on um.user_id = o_user.user_id
		left join
		(
			select wp_postmeta.post_id as post_id, ol.post_id as product_id, meta_value as sku from wp_postmeta
				inner join
				(
					select post_id, meta_value as ticket_product_id from wp_postmeta where meta_key='_tribe_wooticket_product'
				) ol on wp_postmeta.post_id = ol.ticket_product_id
			where meta_key = '_sku'
		) as op on op.product_id=s.post_id
		";
	}
}

class App extends Controller {
	function get_event_id($f3, $params) {
		if(isset($params['event_id']) && $params['event_id'] == 'current') {
			return $f3->get('current_event_id');
		}
		return $params['event_id'];
	}


	function events($f3) {
		$results = $this->db->exec(
			"select
				e.post_id, wp_posts.post_title
			from
				(SELECT post_id FROM wp_postmeta where meta_key='_EventCost') as e
			left join wp_posts
				on e.post_id = wp_posts.ID;"
		);
		return $results;
	}

	function user_events($f3, $params) {
		global $wpdb;

		$venues = "'".implode("','", explode(',', get_user_meta($params['user_id'], 'calvenue', true)))."'";

		$querystr = "
    		SELECT $wpdb->posts.ID as id, $wpdb->posts.post_title as name, $wpdb->postmeta.meta_value as venue_id
			FROM $wpdb->posts, $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->postmeta.meta_key = '_EventVenueID'
			AND $wpdb->postmeta.meta_value  IN(".$venues.")
			AND $wpdb->posts.post_status = 'publish' ";
		if($this->_modified_after) {
			$querystr .= "AND $wpdb->posts.post_modified >= '".$this->_modified_after."'";
		} else {
			$querystr .= "AND $wpdb->posts.post_date < NOW()";
		}

		$querystr .= " ORDER BY $wpdb->posts.post_date DESC";

		$results = $wpdb->get_results($querystr, OBJECT);

		$venue_logo = '';
		$brand_logo = '';
		foreach($results as $result) {
			$venue_id = $result->venue_id;
			$post = get_post($result->venue_id);
			$result->venue_name = $post->post_title;
			$artist = get_post_meta($result->id, 'htmh_event_mobile_artist_name', true);
			$result->start_date = get_post_meta($result->id, '_EventStartDate', true);

			if($artist != '') {
				$result->name = $artist;
			}

			$venue_image_id = get_post_meta($venue_id, 'tribe_venue_feature-image-venue_thumbnail_id', true);
			$brand_image_id = get_post_meta($venue_id, 'tribe_venue_feature-image-company_thumbnail_id', true);
			$upload_dir_data = wp_upload_dir();
			$venue_image_meta = get_post_meta(
				$venue_image_id,
				'_wp_attached_file',
				true
			);
			if($venue_image_meta !== false) {
				$venue_logo = $upload_dir_data['baseurl'] . '/' . $venue_image_meta;
			}

			$brand_image_meta = get_post_meta(
				$brand_image_id,
				'_wp_attached_file',
				true
			);
			if($brand_image_meta !== false) {
				$brand_logo = $upload_dir_data['baseurl'] . '/' . $brand_image_meta;
			}

			$result->brand_logo = $brand_logo;
			$result->venue_logo = $venue_logo;

		}

		return $results;

	}

	function event($f3, $params) {
		$results = $this->db->exec(
			"select
				e.post_id, wp_posts.post_title 
			from 
				(SELECT post_id FROM wp_postmeta where meta_key='_EventCost') as e
			left join 
				wp_posts on e.post_id = wp_posts.ID
			where
				e.post_id = ?;",
			$this->get_event_id($f3, $params)
		);
		return $results;
	}

	function current_event($f3) {
		return array('event_id'=>$f3->get('current_event_id'));
	}

	function event_tickets($f3, $params) {
		$this->ticket_sql .= " where e.event_id = ? and op.sku NOT LIKE 'GL-%'";

		if($this->_modified_after) {
			$this->ticket_sql .= " AND s.post_modified >='".$this->_modified_after."'";
		}

		$tickets = $this->db->exec(
			$this->ticket_sql,
			$this->get_event_id($f3, $params)
		);

		$new_tickets = array();
		foreach($tickets as $ticket) {
			$matches = array();
			preg_match_all('/src\s*=\s*[\'""](.+?)[\'""]/', get_avatar($ticket['user_id'], 200), $matches);
			$ticket['photo_url'] = '';
			if(array_key_exists(1, $matches) && array_key_exists(0, $matches[1])) {
				$ticket['photo_url'] = urldecode($matches[1][0]);
			}

			// fix for the name
			$name = explode(' ', $ticket['name']);
			$ticket['firstname'] = $name[0];
			$ticket['lastname'] = $name[count($name)-1];

			$new_tickets[] = $ticket;
		}

		return $new_tickets;
	}

	function event_guestlist($f3, $params) {
		$this->ticket_sql .= " where e.event_id = ? and op.sku LIKE 'GL-%'";
		if($this->_modified_after) {
			$this->ticket_sql .= " AND s.post_modified >='".$this->_modified_after."'";
		}
		$tickets = $this->db->exec(
			$this->ticket_sql,
			$this->get_event_id($f3, $params)
		);
		$new_tickets = array();
		foreach($tickets as $ticket) {
			$matches = array();
			preg_match_all('/src\s*=\s*[\'""](.+?)[\'""]/', get_avatar($ticket['user_id'], 200), $matches);
			$ticket['photo_url'] = '';
			if(array_key_exists(1, $matches) && array_key_exists(0, $matches[1])) {
				$ticket['photo_url'] = urldecode($matches[1][0]);
			}
			// fix for the name
			$name = explode(' ', $ticket['name']);
			$ticket['firstname'] = $name[0];
			$ticket['lastname'] = $name[count($name)-1];
			$new_tickets[] = $ticket;
		}
		return $new_tickets;
	}

	function event_report($f3, $params) {
		$woocommerce_extended = TribeWooTicketsExtended::get_instance();
		$attendees = $woocommerce_extended->get_attendee_list($this->get_event_id($f3, $params));

		$total_attendees = count($attendees)-1;
		$totals = array('totals' => array(), 'lines' => array());
		for($x=0; $x <= $total_attendees; $x++) {
			$line = array();
			$order = new WC_Order($attendees[$x]['order_id']);
			$items = $order->get_items();
			foreach($items as $item) {
				if(array_key_exists($attendees[$x]['ticket'], $totals['totals']) == false) {
					$totals['totals'][$attendees[$x]['ticket']]['sold'] = 0;
					$totals['totals'][$attendees[$x]['ticket']]['cost'] = $item['line_subtotal'] / $item['qty'];
				}
			}

			if(array_key_exists($attendees[$x]['ticket'], $totals['totals'])) {
				$totals['totals'][$attendees[$x]['ticket']]['sold'] += 1;
			} else {
				$totals['totals'][$attendees[$x]['ticket']]['sold'] = 1;
			}

			$line['security'] = $attendees[$x]['security'];
			$line['purchaser_name'] = $attendees[$x]['purchaser_name'];
			$line['purchaser_email'] = $attendees[$x]['purchaser_email'];
			$line['ticket'] = $attendees[$x]['ticket'];
			$line['check_in'] = ($attendees[$x]['check_in'] == null) ? 'No' : 'Yes';

			$totals['lines'][] = $line;
		}

		$results = $totals;


		return $results;

	}

	function venue_report($f3, $params) {

		// get the Venue Name from the Params so we can get the post_id for the venue
		$venue = null;
		$venue_id = null;
		if(array_key_exists('venue', $params) && isset($params['venue'])) {
			$venue = $params['venue'];
		}

		$results = $this->db->exec(
			"SELECT id
			FROM wp_posts
			WHERE LOWER(post_title) = ?",
			strtolower($venue)
		);
		if(isset($results[0]) && array_key_exists('id', $results[0])) {
			$venue_id = $results[0]['id'];
		}

		if(is_null($venue_id)) {
			echo "Unable to find a Venue with the name of: '".$venue."'.";
			exit();
		}

		// get all posts which are events and associated with the venue_id
		$event_ids = $this->db->exec(
			"SELECT post_id FROM wp_postmeta WHERE meta_key='_EventVenueID' and meta_value = ?",
			$venue_id
		);

		$data = array();
		// Loop over events calculating the results
		$woocommerce_extended = TribeWooTicketsExtended::get_instance();
		$prev_event_id = 0;

		foreach($event_ids as $event_id) {
			$event_id = $event_id['post_id'];

			$attendees = $woocommerce_extended->get_attendee_list($event_id);

			foreach($attendees as $attendee) {
				$ticket_id = get_post_meta($attendee['attendee_id'], '_tribe_wooticket_product', true);
				$sku = get_post_meta($ticket_id, '_sku', true);
				$index = md5($attendee['purchaser_email']);
				if(array_key_exists($index, $data) === false) {
					$data[$index]['gl-ticket-count'] = 0;
					$data[$index]['ticket-count'] = 0;
					$data[$index]['event-count'] = 0;
				}
				if(strpos($sku, 'GL-') !== false) {
					// guest list ticket
					$data[$index]['gl-ticket-count']++;
				} else {
					// regular ticket
					$data[$index]['ticket-count']++;
				}
				if($event_id !== $prev_event_id) $data[$index]['event-count']++;

				$data[$index] = array_merge(array(
					'order_id' => $attendee['order_id'],
					'order_status' => $attendee['order_status'],
					'purchaser_name' => $attendee['purchaser_name'],
					'purchaser_email' => $attendee['purchaser_email'],
					'ticket' => $attendee['ticket'],
					'ticket_number' => $attendee['attendee_id'],
					'security' => $attendee['security'],
					'check_in' => $attendee['check_in'],
				), $data[$index]);
			}
			$prev_event_id = $event_id;
		}
		return $data;
	}

	function unused_event_tickets($f3, $params, $limit=NULL) {
		$sql = $this->ticket_sql . " where e.event_id = ? and c.checked_in is NULL";
		if($limit) {
			$sql .= " limit " . $limit;
		}
		$sql .= ';';
		$results = $this->db->exec(
			$sql,
			$this->get_event_id($f3, $params)
		);
		return $results;
	}

	function used_event_tickets($f3, $params) {
		$results = $this->db->exec(
			$this->ticket_sql . " where e.event_id = ? and c.checked_in = '1';",
			$this->get_event_id($f3, $params)
		);
		return $results;
	}

	function used_event_ticket_count($f3, $params) {
		$results = $this->db->exec(
			"select count(s.post_id) as ticket_ from
				(select post_id, meta_value as security_code from wp_postmeta where meta_key='_tribe_wooticket_security_code') s
			left join
				(select post_id, meta_value as checked_in from wp_postmeta where meta_key='_tribe_wooticket_checkedin') c on s.post_id = c.post_id
			left join
				(select post_id, meta_value as event_id from wp_postmeta where meta_key='_tribe_wooticket_event') as e on s.post_id = e.post_id
			where e.event_id = ? and c.checked_in = 1;",
			$this->get_event_id($f3, $params)
		);
		return $results;
	}

	function used_event_checkedin_counts($f3, $params) {
		$tickets = $this->db->exec(
			$this->ticket_sql . " where checked_in = 1 and e.event_id = ? and op.sku NOT LIKE 'GL-%'",
			$this->get_event_id($f3, $params)
		);
		$gl = $this->db->exec(
			$this->ticket_sql . " where checked_in = 1 and e.event_id = ? and op.sku LIKE 'GL-%'",
			$this->get_event_id($f3, $params)
		);

		$tickets = count($tickets);
		$gl = count($gl);

		$ret = new stdClass();
		$ret->tickets = ($tickets > 0) ? $tickets : 0;
		$ret->guestlist = ($gl > 0) ? $gl : 0;


		return $ret;
	}

	function ticket($f3, $params) {
		return $this->get_ticket($params['ticket_id']);
	}

	function get_ticket($ticket_id) {
		$results = $this->db->exec(
			$this->ticket_sql . " where s.security_code=?;",
			$ticket_id
		);
		if(! $results) {
			return array(
				'post_id'=>NULL,
				'ticket_id'=>$ticket_id,
				'checked_in'=>'-1'
			);
		}
		return $results[0];
	}

	function qrcode($f3, $params) {
		global $wpdb;
		$security_code = $params['security_code'];

		$results  = $wpdb->get_row( $wpdb->prepare("select post_id from $wpdb->postmeta where meta_value = %s", $security_code), ARRAY_N);
		if($results !== null) {
			$ticket_id = $results[0];
			$event_id = get_post_meta($ticket_id, '_tribe_wooticket_event', true);
			$order_id = get_post_meta($ticket_id, '_tribe_wooticket_order', true);
			$purchaser_name = get_post_meta($order_id, '_billing_first_name', true) . ' ' . get_post_meta(
					$order_id,
					'_billing_last_name',
					true
				);
			$product_id = get_post_meta($ticket_id, '_tribe_wooticket_product', true);
			$ticket_type = get_the_title($product_id);


			$ticket_header_post_id = get_post_meta($event_id, '_tribe_ticket_header', true);
			$start_date = get_post_meta($event_id, '_EventStartDate', true);
			$artist_name = get_post_meta($event_id, 'htmh_event_mobile_artist_name', true);
			$venue_id = get_post_meta($event_id, '_EventVenueID', true);
			$venue_image_id = get_post_meta($venue_id, 'tribe_venue_feature-image-venue_thumbnail_id', true);

			$upload_dir = wp_upload_dir();

			$venue_image_path = get_post_meta($venue_image_id, '_wp_attached_file', true);
			$venue_image = $upload_dir['baseurl'] . '/' . $venue_image_path;

			$ticket_header_image_path = get_post_meta($ticket_header_post_id, '_wp_attached_file', true);
			$ticket_header_image = $upload_dir['baseurl'] . '/' . $ticket_header_image_path;

			$size = (isset($params['size']) && array_key_exists('size', $params)) ? $params['size'] : 5;
			$cache_filename = 'cache/' . $security_code . '.' . $size . '.png';
			if (!file_exists($cache_filename)) {
				QRcode::png($security_code, $cache_filename, QR_ECLEVEL_L, $size, 0);
			}

			$owner_name = $this->db->exec('SELECT first_name, last_name FROM gifted_ticket WHERE security_code = "'.$security_code.'"');
			$name = null;
			if ($owner_name) {
				$name = $owner_name[0]['first_name'] . ' ' . $owner_name[0]['last_name'];
			}

			$ticket = new stdClass();
			$ticket->name = $name;
			$ticket->purchaser_name = $purchaser_name;
			$ticket->type = $ticket_type;
			$ticket->artist_name = $artist_name;
			$ticket->header_image = $ticket_header_image;
			$ticket->venue_image = $venue_image;
			$ticket->event_start = date('m.d.y - h:i A', strtotime($start_date));
			$ticket->barcode = base64_encode(file_get_contents($cache_filename));
			$ticket->security_code = strtoupper($params['security_code']);
		} else {
			$ticket = new stdClass();
		}
		return $ticket;
	}

	function barcode($f3, $params) {
		$results = $this->db->exec(
			"select c.barcode_image as barcode_image from
				(select post_id, meta_value as security_code from wp_postmeta where meta_key='_tribe_wooticket_security_code') s
			left join
				(select post_id, meta_value as barcode_image from wp_postmeta where meta_key='_tribe_wooticket_barcode_image') c on s.post_id = c.post_id
			where s.security_code=?;",
			$params['ticket_id']
		);
		if(! $results) {
			return NULL;
		}
		return $results[0];
	}

	function checkin_ticket($f3, $params = array()) {
		if(isset($_GET['ticket_id'])) {
			return $this->do_checkin(array($_GET['ticket_id']));
		} elseif($params) {
			return $this->do_checkin(array($params['ticket_id']));
		}
		return array(array());
	}

	function checkin_tickets($f3){
		return $this->do_checkin(json_decode(file_get_contents('php://input'), true));
	}

	function do_checkin($ticket_ids) {
		global $pubnub;
		$response = array();
		foreach($ticket_ids as $ticket_id) {
			$ticket = $this->get_ticket($ticket_id);
			if($ticket['checked_in'] == "0") {
				$this->db->exec("insert into wp_postmeta (post_id, meta_key, meta_value) values(?,'_tribe_wooticket_checkedin','1')", $ticket['post_id']);
				$timestamp = time();
				$modified = date('Y-m-d H:i:s', $timestamp);
				$modified_gmt = gmdate('Y-m-d H:i:s', $timestamp);

				$this->db->exec("update wp_posts set post_modified = :modified, post_modified_gmt = :modified_gmt where ID = :id", array(":modified" => $modified, ":modified_gmt" => $modified_gmt, ":id" => $ticket['post_id']));
			}
			array_push($response, $ticket);

			// log the login activity
			$logquery = 'INSERT INTO guestlist_log (user_id, action, action_item_id, date_created) VALUES('.$ticket['user_id'].', "checkin", '.$ticket['post_id'].', "'.date("Y-m-d H:i:s").'")';
			$this->db->exec($logquery);

			// send notification to clients via pubnub
			$list_type = (substr($ticket['sku'], 0, 3) == 'GL-') ? 'guestlist' : 'ticketlist';
			error_log('Publishing to channel: event_'.$ticket['event_id'].'_'.$list_type);
			$pubnub->publish('event_'.$ticket['event_id'].'_'.$list_type, 'update');
		}
	}

	function undo_checkin($ticket_ids) {
		global $pubnub;
		$response = array();
		foreach($ticket_ids as $ticket_id) {
			$ticket = $this->get_ticket($ticket_id);

			$this->db->exec("delete from wp_postmeta where post_id=? and meta_key='_tribe_wooticket_checkedin'", $ticket['post_id']);
			$timestamp = time();
			$modified = date('Y-m-d H:i:s', $timestamp);
			$modified_gmt = gmdate('Y-m-d H:i:s', $timestamp);

			$this->db->exec("update wp_posts set post_modified = :modified, post_modified_gmt = :modified_gmt where ID = :id", array(":modified" => $modified, ":modified_gmt" => $modified_gmt, ":id" => $ticket['post_id']));
			array_push($response, $ticket);

			// log the login activity
			$logquery = 'INSERT INTO guestlist_log (user_id, action, action_item_id, date_created) VALUES('.$ticket['user_id'].', "undo checkin", '.$ticket['post_id'].', "'.date("Y-m-d H:i:s").'")';
			$this->db->exec($logquery);

			// send notification to clients via pubnub
			$list_type = (substr($ticket['sku'], 0, 3) == 'GL-') ? 'guestlist' : 'ticketlist';
			error_log('Publishing to channel: event_'.$ticket['event_id'].'_'.$list_type);
			$pubnub->publish('event_'.$ticket['event_id'].'_'.$list_type, 'update');
		}
		return $response;
	}

	function put_ticket($f3, $params) {
		$ticket = json_decode($f3->get("BODY"), true);
		$results = array();
		if($ticket['checked_in']=="0") {
			$this->undo_checkin(array($params['ticket_id']));
		} else {
			$this->do_checkin(array($params['ticket_id']));
		}
	}

	function login($f3, $params) {
		global $pubnub;

		error_log($pubnub->time());
		$obj = new stdClass();
		$results = $this->db->exec(
			"select id, user_pass from wp_users where user_login=:username;",
			array(
				':username' => $_POST['user'],
			)
		);
		// if no match was found for the username / password combination
		if(! $results) {
			$obj->authenticated = false;
			return $obj;
		}

		$user = $results[0];

		$user_data = get_userdata($user['id']);

		if(in_array('venue_manager', $user_data->roles) || in_array('venue-manager', $user_data->roles)) {
			$role = 'Venue Manager';
		} elseif(in_array('Venue_Checkin', $user_data->roles)) {
			$role = 'Venue Checkin';
		} else {
			$role = null;
		}

		$wp_hasher = new PasswordHash(8, TRUE);

		$password_hashed = $user['user_pass'];

		if($wp_hasher->CheckPassword($_POST['pwd'], $password_hashed) || $password_hashed == md5($_POST['pwd'])) {
			// log the login activity
			$logquery = 'INSERT INTO guestlist_log (user_id, action, date_created) VALUES('.$user['id'].', "login", "'.date("Y-m-d H:i:s").'")';
			$this->db->exec($logquery);
			// login successful get other information about user
			$meta_results = $this->db->exec(
				"select meta_key, meta_value from wp_usermeta where user_id=:user_id and meta_key in ('nickname', 'first_name', 'last_name', 'calvenue');",
				array(':user_id' => $user['id'])
			);

			if($meta_results) {
				$obj->authenticated = true;
				$obj->user = new stdClass();
				$obj->user->id = $user['id'];
				$obj->user->role = $role;
				$brand_logo = null;

				foreach ($meta_results as $row) {
					switch ($row['meta_key']) {
						case 'first_name':
						case 'last_name':
							$obj->user->{str_replace('_', '', $row['meta_key'])} = $row['meta_value'];
							break;
						case 'nickname':
							$obj->user->username = $row['meta_value'];
							break;
						case 'calvenue':
							if(is_null($brand_logo)) {
								// determine brand image
								if(array_key_exists('meta_key', $row) && $row['meta_key'] == 'calvenue') {
									$venues = explode(',', $row['meta_value']);

									if(count($venues) > 0) {
										foreach($venues as $venue) {
											$brand_logo_id = get_post_meta($venue, 'tribe_venue_feature-image-company_thumbnail_id', true);
											$upload_dir_data = wp_upload_dir();
											$brand_logo = get_post_meta(
												$brand_logo_id,
												'_wp_attached_file',
												true
											);

											if($brand_logo != '') $brand_logo = $upload_dir_data['baseurl'] . '/' .$brand_logo;
										}
									}
								}
								$obj->user->brand = $brand_logo;
							}
							break;
						default:
							break;
					}
				}
			}
		}



		$obj->authorization_token = md5(implode(',', $this->_secrets));

		return $obj;
	}

	function get_gifted_ticket($f3, $params) {
		$token = $params['token'];
		$security_code = $params['security_code'];
	    $user_data_query = 'SELECT first_name, last_name, email, terms FROM gifted_ticket WHERE security_code = "'.$security_code.'" AND token = "'.$token.'" ';
		$results = $this->db->exec($user_data_query);
		$user_data = new stdClass();
		if (count($results) > 0 && array_key_exists(0, $results)) {
			$user_data->first_name = $results[0]['first_name'];
			$user_data->last_name = $results[0]['last_name'];
			$user_data->email = $results[0]['email'];
			$user_data->terms = $results[0]['terms'];
		}
		return $user_data;
	}

}

class JsonAPI extends App {

	function generate_output($data) {
		header('Content-Type: application/json');
		echo(json_encode($data));
	}

	function online($f3) {
		return true;
	}

	function events($f3) {
		$this->generate_output(parent::events($f3));
	}

	function user_events($f3, $params) {
		$this->generate_output(parent::user_events($f3, $params));
	}

	function event($f3, $params) {
		$this->generate_output(parent::event($f3, $params));
	}

	function current_event($f3) {
		$this->generate_output(parent::current_event($f3));
	}

	function event_report($f3, $params) {
		$this->generate_output(parent::event_report($f3, $params));
	}

	function venue_report($f3, $params) {
		$this->generate_output(parent::venue_report($f3, $params));
	}

	function event_tickets($f3, $params) {
		$this->generate_output(parent::event_tickets($f3, $params));
	}

	function event_guestlist($f3, $params) {
		$this->generate_output(parent::event_guestlist($f3, $params));
	}

	function unused_event_tickets($f3, $params) {
		$this->generate_output(parent::unused_event_tickets($f3, $params));
	}

	function used_event_tickets($f3, $params) {
		$this->generate_output(parent::used_event_tickets($f3, $params));
	}

	function used_event_ticket_count($f3, $params) {
		$this->generate_output(parent::used_event_ticket_count($f3, $params));
	}

	function used_event_checkedin_counts($f3, $params) {
		$this->generate_output(parent::used_event_checkedin_counts($f3, $params));
	}

	function checkin_ticket($f3, $params){
		$this->generate_output(parent::checkin_ticket($f3, $params));
	}

	function checkin_tickets($f3){
		$this->generate_output(parent::checkin_tickets($f3));
	}

	function ticket($f3, $params){
		$this->generate_output(parent::ticket($f3, $params));
	}

	function put_ticket($f3, $params) {
		$this->generate_output(parent::put_ticket($f3, $params));
	}

	function login($f3, $params) {
		$this->generate_output(parent::login($f3, $params));
	}

	function qrcode($f3, $params) {
		$this->generate_output(parent::qrcode($f3, $params));
	}

	function barcode($f3, $params){
		$barcode_data = parent::barcode($f3, $params);
		$barcode_data = $barcode_data['barcode_image'];
		// Get image data from barcode string
		list( $settings, $string ) = explode( ',', $barcode_data );
		list( $img_type, $method ) = explode( ';', substr( $settings, 5 ) );

		// Get image extensoin
		$img_ext = str_replace( 'image/', '', $img_type );

		// Decode barcode image
		$barcode = base64_decode( $string );

		// Set headers for image output
		if( ini_get( 'zlib.output_compression' ) ) { ini_set( 'zlib.output_compression', 'Off' ); }
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Type: ' . $img_type );
		header( 'Content-Length: ' . strlen( $barcode ) );

		// Output image and die
		echo($barcode );
	}

	function raw_barcode($f3, $params) {
		$barcode_data = parent::barcode($f3, $params);
		$barcode_data = $barcode_data['barcode_image'];
		// Get image data from barcode string
		list( $settings, $string ) = explode( ',', $barcode_data );
		list( $img_type, $method ) = explode( ';', substr( $settings, 5 ) );

		// Get image extensoin
		$img_ext = str_replace( 'image/', '', $img_type );
		echo("Image Type: " . $img_ext);
		// Decode barcode image
		echo("String: " . $string);
	}

	function send_ticket($f3, $params) {
		$token = md5( time() .mt_rand() );
		$to_email = $_POST['to']['email'];
		$to_first_name = $_POST['to']['first_name'];
		$to_last_name = $_POST['to']['last_name'];
		$security_code = $params['security_code'];

		$from_full_name = $_POST['from'];
		$event_name = $_POST['event'];
		$current_year = date("Y");
		$subject = 'ticket';
		$file = '../images/HeyTix.png';
		$uid = 'heytix-logo-uid';
		$name = 'HeyTix.png';
		$headers = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$get_ticket_query = 'SELECT * FROM gifted_ticket WHERE security_code = "'.$security_code.'"';
		$res = $this->db->exec($get_ticket_query);
		
		if (count($res) > 0) {
/* 			$to_email = $res[0]['email'];
			$subject = 'ticket';
			$message = '';
			wp_mail($to_email, $subject, $message, $headers); */
			$delete_ticket_query = 'DELETE FROM gifted_ticket WHERE security_code = "'.$security_code.'"';
			$this->db->exec($delete_ticket_query);
		}
  	    $send_ticket_query = 'INSERT INTO gifted_ticket (security_code, first_name, last_name, email, token) VALUES("'.$security_code.'", "'.$to_first_name.'" ,"'.$to_last_name.'", "'.$to_email.'", "'.$token.'")';
		$this->db->exec($send_ticket_query);


		global $phpmailer;
		add_action( 'phpmailer_init', function(&$phpmailer)use($file,$uid,$name) {
			$phpmailer->SMTPKeepAlive = true;
			$phpmailer->AddEmbeddedImage($file, $uid, $name);
		});

		$message = '
			<html>
				<body>
					<div style="width:100%; height: 100%; background-color: whitesmoke; text-align: -webkit-center; font-family: \'Brandon_light\', arial; color: rgb(51, 51, 51); padding-bottom: 70px;">
						<div>
							<img src="cid:heytix-logo-uid" style="height: 100px; margin: 30px;"/>
						</div>
						<div style="height: 350px; width: 450px; background-color: white; border: 1px solid rgb(205, 205, 205); border-radius: 10px;">
							<div style="line-height: 60px; background-color: rgb(0, 62, 99); color: white; font-size: 25px; font-weight: bold; text-transform:uppercase; border-top-left-radius: 10px; border-top-right-radius: 10px; letter-spacing: 1.5px;">you have a ticket</div>
							<div style="padding: 20px 50px; text-align: left; line-height: 20px; font-size: 14px;">
								<p>
									<span style="text-transform:uppercase">'.$to_first_name.'</span>,
								</p>
								<p>
									<span style="text-transform:uppercase">'.$from_full_name.'</span>
									has sent you a purchased ticket to
									<a href="" style="color:rgb(0, 178, 255); text-decoration:none;">'.$event_name.'. </a>
									 Click the button below to claim the ticket, and put it in your name.
								</p>
							</div>
							<a href="'.get_site_url().'/mobile_ticket/?security_code='.$security_code.'/?token='.$token.'" style="text-decoration: none;">
								<div style="background-color: rgb(1, 178, 255); line-height: 54px; cursor: pointer; color: white; border-radius: 5px; width: 290px; font-size: 22px;">GET TICKET</div>
							</a>
							<div style="margin-top: 50px; font-size: 11px;">
								<span>HeyTix '.$current_year.'</span>
							</div>
						</div>
					</div>
				</body>
			</html>
		';

	    wp_mail($to_email, $subject, $message, $headers);
	}

	function update_gifted_ticket($f3, $params) {
		$token = $params['token'];
		$security_code = $params['security_code'];
		$user = $_POST['user'];
		$first_name = $user['first_name'];
		$last_name = $user['last_name'];
		$email = $user['email'];
  	    $update_ticket_query = 'UPDATE gifted_ticket SET first_name = "'.$first_name.'", last_name = "'.$last_name.'", email = "'.$email.'", terms = "1"  WHERE token = "'.$token.'" AND security_code = "'.$security_code.'"';
		$this->db->exec($update_ticket_query);
	}

	function get_gifted_ticket($f3, $params) {
		$this->generate_output(parent::get_gifted_ticket($f3, $params));
	}
}

class WebUI extends App {
	private $ticket_status = array(
		"-1" => array(
			"message"=>"Invalid Status",
			"color"=>"red"
		),
		"1" => array(
			"message"=>"Alredy Checked In",
			"color"=>"red"
		),
		"0" => array(
			"message"=>"OK",
			"color"=>"Green"
		)
	);

	function home($f3) {
		$template=new Template;
		$f3->set('body', 'events.html');
		$f3->set('title', 'Events');
		$f3->set('data', parent::events($f3));
		echo $template->render('page.html');
	}

	function checkin($f3) {
		$event_id = $f3->get('GET');
		$event_id = $event_id['event_id'];
		$event = $this->event($f3, array('event_id' => $event_id));
		$checkin_result = $this->checkin_ticket($f3);
		$checkin_result = $checkin_result[0];
		$checkin_count = $this->used_event_ticket_count($f3, array('event_id'=>$event_id));
		$checkin_count = $checkin_count[0]['checkin_count'];
		$result = array();
		if($checkin_result) {
			$result = $this->ticket_status[$checkin_result['checked_in']];
		}
		$template=new Template;
		$f3->set('body', 'checkin.html');
		$f3->set('title', 'Checkin');
		$f3->set('event', $event[0]);
		$f3->set('result',  $result);
		$f3->set('ticket', $checkin_result);
		$f3->set('checkin_count', $checkin_count);
		echo $template->render('page.html');
	}

	function barcodes($f3) {
		$template=new Template;
		$f3->set('body', 'barcodes.html');
		$f3->set('title', 'Test Barcodes');
		$f3->set('data', parent::unused_event_tickets($f3, $this->current_event($f3), 10));
		echo $template->render('page.html');
	}

	function ticket($f3, $params) {
		$ticket = parent::ticket($f3, $params);
		$template=new Template;
		$f3->set('body', 'ticket.html');
		$f3->set('title', 'Ticket');
		$f3->set('ticket', $ticket);
		echo $template->render('page.html');
	}
}

/*
* API Routes
*/

// Events
$f3->route('GET /api/events', 'JsonAPI->events');
$f3->route('GET /api/events/@event_id', 'JsonAPI->event');
$f3->route('GET /api/online', 'JsonAPI->online');
$f3->route('HEAD /api/online', 'JsonAPI->online');

$f3->route('GET /api/report/event/@event_id', 'JsonAPI->event_report');
$f3->route('GET /api/report/venue/@venue', 'JsonAPI->venue_report');

// Event Tickets

$f3->route('GET /api/events/@event_id/ticketlist', 'JsonAPI->event_tickets');
$f3->route('GET /api/events/@event_id/guestlist', 'JsonAPI->event_guestlist');
$f3->route('GET /api/events/@event_id/checkedin/counts', 'JsonAPI->used_event_checkedin_counts');

$f3->route('GET /api/events/@event_id/tickets', 'JsonAPI->event_tickets');
$f3->route('GET /api/events/@event_id/tickets/@ticket_id', 'JsonAPI->ticket');
if($f3->get("no_checkin") == false) {
	$f3->route('PUT /api/events/@event_id/ticketlist/@ticket_id', 'JsonAPI->put_ticket');
	$f3->route('PUT /api/events/@event_id/guestlist/@ticket_id', 'JsonAPI->put_ticket');
	$f3->route('PUT /api/events/@event_id/tickets/@ticket_id', 'JsonAPI->put_ticket');
	$f3->route('PATCH /api/events/@event_id/tickets/@ticket_id', 'JsonAPI->put_ticket');
}
$f3->route('GET /api/events/@event_id/unused', 'JsonAPI->unused_event_tickets');
$f3->route('GET /api/events/@event_id/used', 'JsonAPI->used_event_tickets');
$f3->route('GET /api/events/@event_id/used/count', 'JsonAPI->used_event_ticket_count');
$f3->route('GET /api/events/uid/@user_id', 'JsonAPI->user_events');

// Tickets
$f3->route('GET /api/tickets/@ticket_id', 'JsonAPI->get_ticket');
$f3->route('GET /api/tickets/qrcode/@security_code', 'JsonAPI->qrcode');
$f3->route('GET /api/tickets/qrcode/@security_code/@size', 'JsonAPI->qrcode');
$f3->route('GET /api/tickets/@ticket_id/barcode', 'JsonAPI->barcode');
$f3->route('GET /api/tickets/@ticket_id/rawbarcode', 'JsonAPI->raw_barcode');
$f3->route('POST /api/tickets/send_ticket', 'JsonAPI->send_ticket');

$f3->route('POST /api/tickets/gifted_ticket/qrcode/@security_code', 'JsonAPI->send_ticket');
$f3->route('GET /api/tickets/gifted_ticket/qrcode/@security_code/token/@token', 'JsonAPI->get_gifted_ticket');
$f3->route('POST /api/tickets/gifted_ticket/qrcode/@security_code/token/@token/updated', 'JsonAPI->update_gifted_ticket');

// Authentication
$f3->route('POST /api/login', 'JsonAPI->login');

// Checkins
if($f3->get("no_checkin") == false) {
	$f3->route('POST /api/checkin', 'JsonAPI->checkin_tickets');
	$f3->route('GET /api/checkin/@ticket_id', 'JsonAPI->checkin_ticket');
	$f3->route('GET /api/checkin', 'JsonAPI->checkin_ticket');
}
/*
* Web UI Routes
*/
$f3->route('GET /', 'WebUI->home');
$f3->route('GET @checkin_url: /checkin', 'WebUI->checkin');
$f3->route('GET /barcodes', 'WebUI->barcodes');
$f3->route('GET /tickets/@ticket_id', 'WebUI->ticket');

/*
* Run the application
*/
$f3->run();
?>
