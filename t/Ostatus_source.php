<?php
/**
 * Table Definition for ostatus_source
 */
require_once 'DB/DataObject.php';

class Ostatus_source extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'ostatus_source';                  // table name
    public $notice_id;                       // int(11)  not_null primary_key
    public $profile_uri;                     // string(255)  not_null
    public $method;                          // string(6)  not_null binary enum

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Ostatus_source',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
