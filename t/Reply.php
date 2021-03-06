<?php
/**
 * Table Definition for reply
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Reply extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'reply';                           // table name
    public $notice_id;                       // int(11)  not_null primary_key multiple_key
    public $profile_id;                      // int(11)  not_null primary_key multiple_key
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp
    public $replied_id;                      // int(11)  multiple_key

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Reply',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    /**
     * Wrapper for record insertion to update related caches
     */
    function insert()
    {
        $result = parent::insert();

        if ($result) {
            self::blow('reply:stream:%d', $this->profile_id);
        }

        return $result;
    }

    function stream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $max_id=0)
    {
        $ids = Notice::stream(array('Reply', '_streamDirect'),
                              array($user_id),
                              'reply:stream:' . $user_id,
                              $offset, $limit, $since_id, $max_id);
        return $ids;
    }

    function _streamDirect($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $max_id=0)
    {
        $reply = new Reply();
        $reply->profile_id = $user_id;

        if ($since_id != 0) {
            $reply->whereAdd('notice_id > ' . $since_id);
        }

        if ($max_id != 0) {
            $reply->whereAdd('notice_id < ' . $max_id);
        }

        $reply->orderBy('notice_id DESC');

        if (!is_null($offset)) {
            $reply->limit($offset, $limit);
        }

        $ids = array();

        if ($reply->find()) {
            while ($reply->fetch()) {
                $ids[] = $reply->notice_id;
            }
        }

        return $ids;
    }
}
