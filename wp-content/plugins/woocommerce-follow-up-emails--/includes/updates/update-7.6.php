<?php

/**
 * Update Data to 7.6
 * Reset the roles to attach more capabilities to the fue_manager role
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$installer = include( FUE_INC_DIR .'/class-fue-install.php' );
$installer->remove_roles();
$installer->create_role();