<?php
/**
 * Table Definition for user_openid_trustroot
 */
require_once 'DB/DataObject.php';

class User_openid_trustroot extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_openid_trustroot';           // table name
    public $trustroot;                       // string(255)  not_null primary_key
    public $user_id;                         // int(11)  not_null primary_key
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  unsigned zerofill binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User_openid_trustroot',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
