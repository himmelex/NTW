<?php  
require_once 'extlib/DB/DataObject/Generator.php';
$options = &PEAR::getStaticProperty('DB_DataObject','options');  
$options = array(
    'database'          => 'mysql://himmel:irvine@127.0.0.1/MB',
    'schema_location'   => '/Library/WebServer/Documents/sn/t',
    'class_location'    => '/Library/WebServer/Documents/sn/t',
    'require_prefix'    => '/Memcached_DataObject.php',
    'quote_identifiers' => true,
	'extends'			=> 'Memcached_DataObject'
);
set_time_limit(0);  
DB_DataObject::debugLevel(1);  
$generator = new DB_DataObject_Generator;  
$generator->start();  
?>