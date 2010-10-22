<?php
/**
 * Table Definition for rsscloud_subscription
 */
require_once 'DB/DataObject.php';

class Rsscloud_subscription extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'rsscloud_subscription';           // table name
    public $subscribed;                      // int(11)  not_null primary_key
    public $url;                             // string(255)  not_null primary_key
    public $failures;                        // int(11)  not_null
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Rsscloud_subscription',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
