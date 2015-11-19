<?php
define('WP_MEMORY_LIMIT', '128M');
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'onwaysto_heytix');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');


//define('WP_MEMORY_LIMIT', '50200M');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'nS~CplX7!z-Z4THn{Q`M,BwopKd=q~lp#8+vf +;vBmcwmi&&z~lvnw?^kKN$=AR');
define('SECURE_AUTH_KEY',  'kv)|EmpADW m0zb3.;Tj_2,2iVt:8xuv_e^N(])Qh irZYp0mMq>vmIcrYbd|2UR');
define('LOGGED_IN_KEY',    'YYG=0S SwH!K=>Of?,kSzz4Y7~,HI vk7J.x|NLH{za$}%|xQ4<<M`0bo+QVfKK{');
define('NONCE_KEY',        ',0@Ine#rY|>(].4-2`.},Kqv+G/>,-KpLt:<F>n}Y1?W+ [IlL %)$X-]T-3V1@P');
define('AUTH_SALT',        '*/jH9-|e+fuZ$$BA:2ieO<.-m@aZsiq+^acR;|pAD.}G:fuqtlr9kFGv=+rv@F}n');
define('SECURE_AUTH_SALT', '&qGyAmcDB%}-aode1B$a/[D(&vtAWxBXIYEDN:d`2V=v3&CJQ#krd$^x+?gbBW+Z');
define('LOGGED_IN_SALT',   'v8ip5[_wdQhO<;qj~L+q[W)<s.HR`++Ie4Nr2wfNBzJ!_*S2nU[OgTY>JW[Ib* d');
define('NONCE_SALT',       'hq1~IQ4)he!l-]#W29R&bB}m~Up&XD9w$GCmQA:tBdEsQv<NJw{|**k2`U{]=^`W');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');