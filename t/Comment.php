<?php
/**
 * Table Definition for comment
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Comment extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'comment';                         // table name
    public $id;                              // int(20)  not_null primary_key unsigned auto_increment
    public $image_id;                        // int(20)  unsigned
    public $author;                          // int(20)  unsigned
    public $author_email;                    // string(255)  
    public $author_ip;                       // string(100)  
    public $created;                         // datetime(19)  binary
    public $content;                         // blob(65535)  blob

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Comment',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
