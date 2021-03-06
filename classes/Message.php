<?php
/**
 * Table Definition for message
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Message extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'message';                         // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $uri;                             // string(255)  unique_key
    public $from_profile;                    // int(11)  not_null multiple_key
    public $to_profile;                      // int(11)  not_null multiple_key
    public $content;                         // blob(65535)  blob
    public $rendered;                        // blob(65535)  blob
    public $url;                             // string(255)  
    public $created;                         // datetime(19)  not_null multiple_key binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp
    public $source;                          // string(32)  

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Message',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function getFrom()
    {
        return Profile::staticGet('id', $this->from_profile);
    }

    function getTo()
    {
        return Profile::staticGet('id', $this->to_profile);
    }

    static function saveNew($from, $to, $content, $source) {

        $sender = Profile::staticGet('id', $from);

        if (!$sender->hasRight(Right::NEWMESSAGE)) {
            throw new ClientException(_('You are banned from sending direct messages.'));
        }

        $msg = new Message();

        $msg->from_profile = $from;
        $msg->to_profile = $to;
        $msg->content = common_shorten_links($content);
        $msg->rendered = common_render_text($content);
        $msg->created = common_sql_now();
        $msg->source = $source;

        $result = $msg->insert();

        if (!$result) {
            common_log_db_error($msg, 'INSERT', __FILE__);
            return _('Could not insert message.');
        }

        $orig = clone($msg);
        $msg->uri = common_local_url('showmessage', array('message' => $msg->id));

        $result = $msg->update($orig);

        if (!$result) {
            common_log_db_error($msg, 'UPDATE', __FILE__);
            return _('Could not update message with new URI.');
        }

        return $msg;
    }

    static function maxContent()
    {
        $desclimit = common_config('message', 'contentlimit');
        // null => use global limit (distinct from 0!)
        if (is_null($desclimit)) {
            $desclimit = common_config('site', 'textlimit');
        }
        return $desclimit;
    }

    static function contentTooLong($content)
    {
        $contentlimit = self::maxContent();
        return ($contentlimit > 0 && !empty($content) && (mb_strlen($content) > $contentlimit));
    }

    function notify()
    {
        $from = User::staticGet('id', $this->from_profile);
        $to   = User::staticGet('id', $this->to_profile);

        mail_notify_message($this, $from, $to);
    }
}
