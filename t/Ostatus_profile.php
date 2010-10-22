<?php
/**
 * Table Definition for ostatus_profile
 */
require_once 'DB/DataObject.php';

class Ostatus_profile extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'ostatus_profile';                 // table name
    public $uri;                             // string(255)  not_null primary_key
    public $profile_id;                      // int(11)  unique_key
    public $group_id;                        // int(11)  unique_key
    public $feeduri;                         // string(255)  unique_key
    public $salmonuri;                       // blob(65535)  blob
    public $avatar;                          // blob(65535)  blob
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Ostatus_profile',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
