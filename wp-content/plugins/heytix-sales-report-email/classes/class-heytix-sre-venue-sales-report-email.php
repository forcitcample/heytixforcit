<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Heytix_SRE_Venue_Sales_Report_Email extends WC_Email {

	/**
	 * Array containing all Report Rows
	 *
	 * @var array<Heytix_SRE_Report_Row>
	 */
	private $venues = array();
	private $events = array();

	public function __construct() {

		// WC_Email basic properties
		$this->id          = 'sales_report_email';
		$this->title       = __( 'Venue Sales Reports', 'heytix-sales-report-email' );
		$this->description = __( 'The Sales Report Emails plugin extends WooCommerce by emailing you a daily, weekly or monthly sales report.', 'heytix-sales-report-email' );

		// Parent Constructor
		parent::__construct();

	}

	/**
	 * Initialize the class via this init method instead of the constructor to enhance performance.
	 *
	 * @access private
	 * @since  1.0.0
	 */
	private function init() {

		// Create the date range object
		$date_range = new Heytix_SRE_Date_Range( Heytix_SRE_Options::get_interval() );

		$venue_report = new Heytix_SRE_Venue_Report_Data( $date_range );
		$this->venues =	$venue_report->get_data();


		// Subject & Heading
		$this->subject = __( 'Your Daily {site_title} Venue Report', 'heytix-sales-report-email' );
		$this->heading = __( 'Your Daily {site_title} Venue Report', 'heytix-sales-report-email' );

		// Set the template base path
		$this->template_base = plugin_dir_path( Heytix_Sales_Report_Email::get_plugin_file() ) . 'templates/';

		// Set the templates
		$this->template_html  = 'venue-sales-report.php';
		$this->template_plain = 'plain/venue-sales-report.php';

		// Find & Replace vars
		$this->find['site-title']    = '{site_title}';
		$this->find['interval']      = '{interval}';
		$this->replace['site-title'] = $this->get_blogname();
		$this->replace['interval']   = Heytix_SRE_Options::get_interval();

	}

	public function get_events() {
		return apply_filters( 'wc_sales_report_email_rows', $this->events );
	}

	/**
	 * This method is triggered on WP Cron.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function trigger() {

		// Check if extension is active
		if ( true !== Heytix_SRE_Options::is_enabled() ) {
			return;
		}

		// Check if an email should be send
		$send_today = false;
		$interval   = Heytix_SRE_Options::get_interval();
		$now        = new DateTime( null, new DateTimeZone( wc_timezone_string() ) );

		switch ( $interval ) {
			case 'monthly':
				// Send monthly reports on the first day of the month
				if ( 1 == (int) $now->format( 'j' ) ) {
					$send_today = true;
				}
				break;
			case 'weekly':
				// Send weekly reports on monday
				if ( 1 == (int) $now->format( 'w' ) ) {
					$send_today = true;
				}
				break;
			case 'daily':
				// Send everyday if the interval is daily
				$send_today = true;
				break;
			case 'every2min':
				$send_today = true;
				break;
		}

		// Check if we need to send an email today
		if ( true !== $send_today ) {
			return;
		}

		// All checks are done, initialize the object
		$this->init();

		// Add the 'woocommerce_locate_template' filter so we can load our plugin template file
		add_filter( 'woocommerce_locate_template', array( $this, 'load_plugin_template' ), 10, 3 );

		// Add email header and footer
		add_action( 'woocommerce_email_header', array( $this, 'email_header' ) );
		add_action( 'woocommerce_email_footer', array( $this, 'email_footer' ) );

		if(is_array($this->venues) && count($this->venues) > 0) {
			foreach ($this->venues as $venue_id => $events) {
				$this->events = $events;

				// Set recipients
				$this->recipient = $this->get_recipients($venue_id);

				if ($this->recipient != '') {
					// Send the emails
					$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
				}
			}
		}

		// Remove the woocommerce_locate_template filter
		remove_filter( 'woocommerce_locate_template', array( $this, 'load_plugin_templates' ), 10 );

		// Remove the header and footer actions
		remove_action( 'woocommerce_email_header', array( $this, 'email_header' ) );
		remove_action( 'woocommerce_email_footer', array( $this, 'email_footer' ) );

	}

	public function get_recipients($venue_id) {
		$email = get_post_meta($venue_id, '_VenueEmailAddresses');
		$recipients = implode(', ', $email);
		return $recipients;
	}

	/**
	 * Load template files of this plugin
	 *
	 * @param String $template
	 * @param String $template_name
	 * @param String $template_path
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @return String
	 */
	public function load_plugin_template( $template, $template_name, $template_path ) {
		if ( 'venue-sales-report.php' == $template_name || 'plain/venue-sales-report.php' == $template_name) {
			$template = $template_path . $template_name;
		}

		return $template;
	}

	/**
	 * Get the email header.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param mixed $email_heading heading for the email
	 *
	 * @return void
	 */
	public function email_header( $email_heading ) {
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}

	/**
	 * Get the email footer.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function email_footer() {
		wc_get_template( 'emails/email-footer.php' );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'email_heading' => $this->get_heading(),
			'events'		=> $this->get_events(),
			'interval'      => Heytix_SRE_Options::get_interval(),
			'plain_text'    => false
		), $this->template_base );
		$return = ob_get_clean();
		return $return;
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'email_heading' => $this->get_heading(),
			'events'		=> $this->get_events(),
			'interval'      => Heytix_SRE_Options::get_interval(),
			'plain_text'    => true
		), $this->template_base );

		return ob_get_clean();
	}
}