<?php
/**
 * Table Definition for fave
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Fave extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'fave';                            // table name
    public $notice_id;                       // int(11)  not_null primary_key multiple_key
    public $user_id;                         // int(11)  not_null primary_key multiple_key
    public $modified;                        // timestamp(19)  not_null multiple_key unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Fave',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    /**
     * Save a favorite record.
     * @fixme post-author notification should be moved here
     *
     * @param Profile $profile the local or remote user who likes
     * @param Notice $notice the notice that is liked
     * @return mixed false on failure, or Fave record on success
     */
    static function addNew(Profile $profile, Notice $notice) {

        $fave = null;

        if (Event::handle('StartFavorNotice', array($profile, $notice, &$fave))) {

            $fave = new Fave();

            $fave->user_id   = $profile->id;
            $fave->notice_id = $notice->id;

            if (!$fave->insert()) {
                common_log_db_error($fave, 'INSERT', __FILE__);
                return false;
            }

            Event::handle('EndFavorNotice', array($profile, $notice));
        }

        return $fave;
    }

    function delete()
    {
        $profile = Profile::staticGet('id', $this->user_id);
        $notice  = Notice::staticGet('id', $this->notice_id);

        $result = null;

        if (Event::handle('StartDisfavorNotice', array($profile, $notice, &$result))) {

            $result = parent::delete();

            if ($result) {
                Event::handle('EndDisfavorNotice', array($profile, $notice));
            }
        }

        return $result;
    }

    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Fave', $kv);
    }

    function stream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $own=false)
    {
        $ids = Notice::stream(array('Fave', '_streamDirect'),
                              array($user_id, $own),
                              ($own) ? 'fave:ids_by_user_own:'.$user_id :
                              'fave:ids_by_user:'.$user_id,
                              $offset, $limit);
        return $ids;
    }

    function _streamDirect($user_id, $own, $offset, $limit, $since_id, $max_id)
    {
        $fav = new Fave();
        $qry = null;

        if ($own) {
            $qry  = 'SELECT fave.* FROM fave ';
            $qry .= 'WHERE fave.user_id = ' . $user_id . ' ';
        } else {
             $qry =  'SELECT fave.* FROM fave ';
             $qry .= 'INNER JOIN notice ON fave.notice_id = notice.id ';
             $qry .= 'WHERE fave.user_id = ' . $user_id . ' ';
             $qry .= 'AND notice.is_local != ' . Notice::GATEWAY . ' ';
        }

        if ($since_id != 0) {
            $qry .= 'AND notice_id > ' . $since_id . ' ';
        }

        if ($max_id != 0) {
            $qry .= 'AND notice_id <= ' . $max_id . ' ';
        }

        // NOTE: we sort by fave time, not by notice time!

        $qry .= 'ORDER BY modified DESC ';

        if (!is_null($offset)) {
            $qry .= "LIMIT $limit OFFSET $offset";
        }

        $fav->query($qry);

        $ids = array();

        while ($fav->fetch()) {
            $ids[] = $fav->notice_id;
        }

        $fav->free();
        unset($fav);

        return $ids;
    }
}
