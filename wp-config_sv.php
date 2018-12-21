<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
$connectstr_dbhost = '';
$connectstr_dbname = '';
$connectstr_dbusername = '';
$connectstr_dbpassword = '';

foreach ($_SERVER as $key => $value) {
    if (strpos($key, "MYSQLCONNSTR_") !== 0) {
        continue;
    }

    $connectstr_dbhost = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
    $connectstr_dbname = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
    $connectstr_dbusername = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
    $connectstr_dbpassword = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
}

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', $connectstr_dbname);

/** MySQL database username */
define('DB_USER', $connectstr_dbusername);

/** MySQL database password */
define('DB_PASSWORD', $connectstr_dbpassword);

/** MySQL hostname */
define('DB_HOST', $connectstr_dbhost);

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'cf+SFyVoHK4zcEC4grk2Dz8zv3kcKC9uikm/0sq+UIVnmibEMfHn4wqJEJRQ77eNAGC9lon0Z9UQ1wTwnHx/fg==');
define('SECURE_AUTH_KEY',  'LFnkaFAyWPFI6wlJi49M1BbknSWR/VuelkIw/Lz0J8OhFxI2ZaP9mRkfnPbU9hZ5LR5gFfX/KHRgKxOpOQcnDg==');
define('LOGGED_IN_KEY',    'dLH1XkPRpmy4YE07RLmf4PdMX4fN4oAUOvBkofZ2QtVQ4HAas9OvkVIBWlRFkUqYT/xdQZHs3a7TdvFUkpKLww==');
define('NONCE_KEY',        'lPjoejbUl2++ESF5H6fX/A8r4quUOFgR4u1OGxCK76Ga1MCITn2InJanSRogVW6YROmMrMz+J3YcVyvUysIw6g==');
define('AUTH_SALT',        'NlwQO0An2Qcj7pm8qbyzs1w8MxjjWfHDfJIhnq+MPRe1Ov+DKxghOhz18j9VdRo55HdReNckfoDyOdLzXOJhqA==');
define('SECURE_AUTH_SALT', '8+LveBJZDoFukn6+0Qs1QQGCw0AiLbe1DSoPYiIobQON6vGKeh3qFWvHttVtIjVjnZMCNmeyFI7BuiWiVUfJpw==');
define('LOGGED_IN_SALT',   'WMn6IwLbqI1TyflZ5candDS1W5OR1f1AQxdrrqwIgCX/4MiqwihL2oJEDMfZTdavBiKETzjuupSqgEMPgZYiAw==');
define('NONCE_SALT',       '/ZFjhClSb7Ldhgf7YAGZAZbpgaiHfT8DFO7RZC2O5N9fpifGZL8Zyi1qTQRnDMt8JhmYyowO8VsfU/fqylsusg==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
