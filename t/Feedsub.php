<?php
/**
 * Table Definition for feedsub
 */
require_once 'DB/DataObject.php';

class Feedsub extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'feedsub';                         // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $uri;                             // string(255)  not_null unique_key
    public $huburi;                          // blob(65535)  blob
    public $verify_token;                    // blob(65535)  blob
    public $secret;                          // blob(65535)  blob
    public $sub_state;                       // string(11)  not_null binary enum
    public $sub_start;                       // datetime(19)  binary
    public $sub_end;                         // datetime(19)  binary
    public $last_update;                     // datetime(19)  not_null binary
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Feedsub',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
