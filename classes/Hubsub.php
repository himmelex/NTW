<?php
/**
 * Table Definition for hubsub
 */
require_once 'DB/DataObject.php';

class Hubsub extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'hubsub';                          // table name
    public $hashkey;                         // string(40)  not_null primary_key
    public $topic;                           // string(255)  not_null multiple_key
    public $callback;                        // string(255)  not_null
    public $secret;                          // blob(65535)  blob
    public $lease;                           // int(11)  
    public $sub_start;                       // datetime(19)  binary
    public $sub_end;                         // datetime(19)  binary
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Hubsub',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
