<?php
/**
 * Table Definition for local_group
 */

class Local_group extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'local_group';                     // table name
    public $group_id;                        // int(11)  not_null primary_key
    public $nickname;                        // string(64)  unique_key binary
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Local_group',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function sequenceKey()
    {
        return array(false, false, false);
    }

    function setNickname($nickname)
    {
        $this->decache();
        $qry = 'UPDATE local_group set nickname = "'.$nickname.'" where group_id = ' . $this->group_id;

        $result = $this->query($qry);

        if ($result) {
            $this->nickname = $nickname;
            $this->fixupTimestamps();
            $this->encache();
        } else {
            common_log_db_error($local, 'UPDATE', __FILE__);
            throw new ServerException(_('Could not update local group.'));
        }

        return $result;
    }
}
