<?php
/**
 * Table Definition for oid_nonces
 */
require_once 'DB/DataObject.php';

class Oid_nonces extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'oid_nonces';                      // table name
    public $server_url;                      // string(2047)  multiple_key binary
    public $timestamp;                       // int(11)  
    public $salt;                            // string(40)  binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Oid_nonces',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
