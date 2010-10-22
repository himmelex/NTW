<?php
/**
 * Table Definition for magicsig
 */
require_once 'DB/DataObject.php';

class Magicsig extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'magicsig';                        // table name
    public $user_id;                         // int(11)  not_null primary_key
    public $keypair;                         // blob(65535)  not_null blob
    public $alg;                             // string(64)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Magicsig',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
