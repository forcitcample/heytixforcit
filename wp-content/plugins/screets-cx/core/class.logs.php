<?php
/**
 * SCREETS © 2014
 *
 * Chat Logs functions
 *
 * COPYRIGHT (c) 2014 Screets. All rights reserved.
 * This  is  commercial  software,  only  users  who have purchased a valid
 * license  and  accept  to the terms of the  License Agreement can install
 * and use this program.
 */

if( !class_exists( 'WP_List_Table' ) ) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

// Admin notifications
function cx_logs_ntf_deleted() { echo '<div><p>' . __( 'Chat log(s) deleted succesfully', 'cx' ) . '</p></div>'; }



class CX_Logs_List extends WP_List_Table {

	var $date_format = '',
		$gmt_offset = 0;

	function __construct(){

		global $status, $page;

		// Get default date format
		$this->date_format = get_option( 'date_format' );
		
		// Get GMT offset
		$this->gmt_offset = get_option( 'gmt_offset' );

		// Set parent defaults
		parent::__construct( array(
			'singular'  => 'log',     	// Singular name of the listed records
			'plural'    => 'logs',    	// Plural name of the listed records
			'ajax'      => false        // Does this table support ajax?
		) );
		
	}

	function get_columns() {
		
		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'title'     	=> __( 'User', 'cx' ),
			'email'     	=> __( 'Email', 'cx' ),
			'ip'    		=> __( 'IP Address', 'cx' ),
			'created_at'    => __( 'Create at', 'cx' ),
			'total_msgs'    => __( 'Total Messages', 'cx' )
		);

		return $columns;
	}

	function column_default( $item, $name ) {
		global $wpdb;

		switch( $name ) {
			case 'created_at':
				$timestamp = ( $item->$name / 1000 ) + $this->gmt_offset * 3600;
				return date_i18n( 'd/m/Y H:i', $timestamp );
				// return get_date_from_gmt( date( 'Y-m-d H:i:s', $item->$name / 1000 ), 'd/m/Y H:i' );

			case 'total_msgs':

				// Total messages
				return $wpdb->get_var( 
					$wpdb->prepare(
						'SELECT COUNT(*) FROM ' . CX_PX . 'chat_logs 
							WHERE cnv_id = %s',
						$item->cnv_id
					)
				);

			case 'ip':
				return long2ip( $item->$name );

			case 'email':
				if( !empty( $item->$name ) )
					return '<a href="mailto:' . $item->$name . '">' . $item->$name . '</a>';
				else
					return '<span style="color:silver">' . __( 'N/A', 'cx' ) . '</span>';

				// return $item->$column_name;

			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}

	}

	function column_title( $item ) {
		
		// Build row actions
		$actions = array(
			'edit'      => sprintf('<a href="?page=%s&action=%s&cnv_id=%s">' . __( 'Show logs', 'cx' ) . '</a>',$_REQUEST['page'],'edit',$item->cnv_id ),
			'delete'    => sprintf('<a href="?page=%s&action=%s&cnv_id=%s">' . __( 'Delete', 'cx' ) . '</a>',$_REQUEST['page'],'delete',$item->cnv_id ),
		);
		
		// Return the title contents
		return sprintf('<strong>%1$s</strong> <span style="color:silver">(%2$s)</span>%3$s',
			/*$1%s*/ $item->name,
			/*$2%s*/ $item->type,
			/*$3%s*/ $this->row_actions( $actions )
		);
	}

	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->cnv_id
		);
	}

	function get_sortable_columns() {

		$sortable_columns = array(
			'title'     	=> array( 'title', false ),     
			'created_at'  	=> array( 'created_at', true ), // True means it's already sorted
			'total_msgs'  	=> array( 'total_msgs', false )
		);

		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete'    => 'Delete'
		);
		return $actions;
	}

	function delete_item( $id ) {
		global $wpdb;

		if( empty( $id ) ) return;

		// Delete conversation
		$wpdb->query( 
			$wpdb->prepare(
				'DELETE FROM ' . CX_PX . 'conversations WHERE cnv_id = %s LIMIT 1',
				$id 
			)
		);

		// Delete conversation messages
		$wpdb->query( 
			$wpdb->prepare(
				'DELETE FROM ' . CX_PX . 'chat_logs WHERE cnv_id = %s',
				$id 
			)
		);

	}

	function process_bulk_action() {

		// Delete item(s)
		if( 'delete' === $this->current_action() ) {
			
			if( !empty( $_REQUEST['log'] ) ) {

				foreach( $_REQUEST['log'] as $log_id ) {

					$this->delete_item( $log_id );			
					
				}

				echo '<div class="updated">' . __( 'Chat log(s) has been deleted', 'cx' ) . '</div>';

				echo '<p><a href="' . admin_url( 'admin.php?page=cx_chat_logs' ) . '" class="button">&laquo; ' . __( 'Chat Logs', 'cx' ) . '</a></p>';


			}

		}
		
	}

	function prepare_items() {
		global $wpdb;

		$per_page = 50;
		
		// Column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		// Bulk actions
		$this->process_bulk_action();
		
		// Get conversations
		$data = $wpdb->get_results( 
			'SELECT c.cnv_id, c.user_id, c.created_at, u.name, u.type, u.ip, u.email
				FROM ' . CX_PX . 'conversations as c
				LEFT JOIN ' . CX_PX . 'users as u ON c.user_id = u.user_id
				GROUP BY c.cnv_id
				ORDER BY c.created_at DESC' 
		);

		// Get current page
		$current_page = $this->get_pagenum();
		
		// Get total items
		$total_items = count( $data );
		
		
		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page);
		
		
		
		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;
		
		
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  	// Calculate the total number of items
			'per_page'    => $per_page,                     	// Determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   // Calculate the total number of pages
		) );
	}


}

/**
 * Render chat logs list
 *
 * @return void
*/
function cx_render_chat_logs() {
	global $wpdb;

	// Create logs table
	$logs = new CX_Logs_List();

	// Prepare logs
	$logs->prepare_items();


	// 
	// Display logs list
	// 
	if( empty( $_GET['action'] ) ) : ?>
	
	

	<div class="wrap">
		<h2><?php _e( 'Chat Logs', 'cx' ); ?></h2>
		<form id="movies-filter" method="get">

			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />

			<?php $logs->display() ?>
		</form>
	</div>

	<?php

	// Show logs
	elseif( $_GET['action'] == 'edit' ):

		$cnv_id = $_GET['cnv_id'];

		// Get current conversation
		$cnv = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT c.cnv_id, u.* FROM ' . CX_PX . 'conversations c
					LEFT JOIN ' . CX_PX . 'users u ON u.user_id = c.user_id
					WHERE c.cnv_id = %s
					GROUP BY c.cnv_id 
					LIMIT 1',
				$cnv_id
			)
		);
		
		// Get current chat logs
		$chat_logs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . CX_PX . 'chat_logs WHERE cnv_id = %s ORDER BY `time`',
				$cnv_id
			)
		); ?>

		<div class="wrap">
			<a href="<?php echo admin_url( 'admin.php?page=cx_chat_logs' ); ?>" class="button">&laquo; <?php _e( 'Chat Logs', 'cx' ); ?></a>
			<h1><?php echo $cnv->name; ?></h1>
			<p style="color:gray">
				<?php echo $cnv->type; ?> &nbsp; &bull; &nbsp; <?php echo long2ip( $cnv->ip ); ?> &nbsp; &bull; &nbsp; <?php echo $cnv->email; ?> 
			</p>
	
			<hr>

			<?php foreach( $chat_logs as $log ): ?>
				
				<p class="cx-msg">
					<span style="color:gray;"><?php 
						$timestamp = ( $log->time / 1000 ) + $logs->gmt_offset * 3600;
						echo date_i18n( 'd/m/Y H:i', $timestamp ); 
						?></span>
					<span style="display:inline-block;margin-left:9px;font-weight:bold;"><?php echo $log->name; ?></span style="display:inline-block;">
					<span style="background:#ddd;border-radius:4px;display:inline-block; padding:2px 7px; margin-left: 4px;"><?php echo stripslashes( $log->msg ); ?></span>
				</p>

			<?php endforeach; ?>
		</div>

	<?php
	elseif( $_GET['action'] == 'delete' ):

		if( !empty( $_GET['cnv_id'] ) ) {
			$logs->delete_item( $_GET['cnv_id'] );
		}

		echo '<div class="updated">' . __( 'Chat log(s) has been deleted', 'cx' ) . '</div>';

		echo '<p><a href="' . admin_url( 'admin.php?page=cx_chat_logs' ) . '" class="button">&laquo; ' . __( 'Chat Logs', 'cx' ) . '</a></p>';

	endif;
}