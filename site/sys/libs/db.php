<?php
defined('BASEPATH') OR exit('No direct script access allowed');

define('Mc_Sqlserver', reading_env('MYSQL_HOST', 'db'));
define('Mc_Sqlport', reading_env('MYSQL_PORT', '3306'));
define('Mc_Sqlname', reading_env('MYSQL_DATABASE', 'reading_app'));
define('Mc_SqlPrefix', 'mc_');
define('Mc_Sqluid', reading_env('MYSQL_USER', 'reader'));
define('Mc_Sqlpwd', reading_required_env('MYSQL_PASSWORD'));
define('Mc_Dbdriver', 'mysqli');
define('Mc_Sqlcharset', 'utf8mb4');
define('Mc_Encryption_Key', reading_required_env('APP_ENCRYPTION_KEY'));
define('Mc_Cookie_Prefix', 'reading_');
define('Mc_Cookie_Domain', '');
define('Mc_Cookie_Ttl', 10800);
define('Mc_Book_Key', reading_required_env('APP_BOOK_KEY'));
