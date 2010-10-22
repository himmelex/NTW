<?php
/**
 * Table Definition for oid_associations
 */
require_once 'DB/DataObject.php';

class Oid_associations extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'oid_associations';                // table name
    public $server_url;                      // blob(65535)  not_null primary_key blob binary
    public $handle;                          // string(255)  not_null primary_key
    public $secret;                          // blob(65535)  blob binary
    public $issued;                          // int(11)  
    public $lifetime;                        // int(11)  
    public $assoc_type;                      // string(64)  binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Oid_associations',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
