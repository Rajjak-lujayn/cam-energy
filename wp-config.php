<?php


define( 'WP_CACHE', true /* Modified by NitroPack */ );
/**
* The base configuration for WordPress
*
* The wp-config.php creation script uses this file during the installation.
* You don't have to use the web site, you can copy this file to "wp-config.php"
* and fill in the values.
*
* This file contains the following configurations:
*
* * Database settings
* * Secret keys
* * Database table prefix
* * ABSPATH
*
* @link https://wordpress.org/documentation/article/editing-wp-config-php/
*
* @package WordPress
*/

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'camenerg_energy_live');

/** Database username */
define('DB_USER', 'camenerg_energy_live');

/** Database password */
define('DB_PASSWORD', 'J^%hog6TcU3O');

/** Database hostname */
define('DB_HOST', '127.0.0.1:3306');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
* Authentication unique keys and salts.
*
* Change these to different unique phrases! You can generate these using
* the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
*
* You can change these at any point in time to invalidate all existing cookies.
* This will force all users to have to log in again.
*
* @since 2.6.0
*/
define('AUTH_KEY', 'OCDJDWLtoXbkSX4XnWrrQfPRbRIjQIzTnjSR3AGa9pyLdT0Q3KKsm+2r+M+UFV96');
define('SECURE_AUTH_KEY', 'ZIBX2mpxUevypRRXWaGgGXw+pI7l2c6bb8cCJWi/zGEDunUuaY3yA0ZILQO2cqcG');
define('LOGGED_IN_KEY', 'mDHtjjxlCaXS3EC8GaYtWfQgXSJAoGO9gjtAIVb0pG+zfaa1aupInJdpF0g2yS3k');
define('NONCE_KEY', 'xdeh76ejaw3yX7kbVWNFcck1emv00hSetr+5tHOeTCZBdZ1llKb/apUmd+ODPy51');
define('AUTH_SALT', 'gG/EXExeZ4foh17F9Nx3gF7moB2tMVoU/AtkGHhcAZyMDap/B5nviOOrQotyN1Wb');
define('SECURE_AUTH_SALT', '5YQIvAxaAjs8zpxRp9EuxfFeNO+o56icD0SZaJkTxh6jp5jp5tkYtqLJuPn8jlK6');
define('LOGGED_IN_SALT', 'mp3E+K2Bf0zQ+wcrpT/cpbBxiegNPui1RtmbgHDAJj+71O+8KZFmiVJZER2pJ1eD');
define('NONCE_SALT', 'g/eJrvGUnhNAOjq4YJRmnUo6mrw2tDRIHHHkhi8DN1dJq3fQwsAcrVO5BLsA4jVJ');

/**#@-*/

/**
* WordPress database table prefix.
*
* You can have multiple installations in one database if you give each
* a unique prefix. Only numbers, letters, and underscores please!
*/
$table_prefix = 'wp_';

/**
* For developers: WordPress debugging mode.
*
* Change this to true to enable the display of notices during development.
* It is strongly recommended that plugin and theme developers use WP_DEBUG
* in their development environments.
*
* For information on other constants that can be used for debugging,
* visit the documentation.
*
* @link https://wordpress.org/documentation/article/debugging-in-wordpress/
*/
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
