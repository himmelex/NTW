<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

/**
 * Table Definition for user
 */

require_once INSTALLDIR.'/classes/Memcached_DataObject.php';
require_once 'Validate.php';

class User extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user';                            // table name
    public $id;                              // int(11)  not_null primary_key
    public $type;                            // string(1)  
    public $nickname;                        // string(64)  unique_key
    public $password;                        // string(255)  
    public $email;                           // string(255)  unique_key
    public $incomingemail;                   // string(255)  unique_key
    public $emailnotifysub;                  // int(4)  
    public $emailnotifyfav;                  // int(4)  
    public $emailnotifynudge;                // int(4)  
    public $emailnotifymsg;                  // int(4)  
    public $emailnotifyattn;                 // int(4)  
    public $emailmicroid;                    // int(4)  
    public $language;                        // string(50)  
    public $timezone;                        // string(50)  
    public $emailpost;                       // int(4)  
    public $jabber;                          // string(255)  unique_key
    public $jabbernotify;                    // int(4)  
    public $jabberreplies;                   // int(4)  
    public $jabbermicroid;                   // int(4)  
    public $updatefrompresence;              // int(4)  
    public $sms;                             // string(64)  unique_key
    public $carrier;                         // int(11)  
    public $smsnotify;                       // int(4)  
    public $smsreplies;                      // int(4)  
    public $smsemail;                        // string(255)  multiple_key
    public $uri;                             // string(255)  unique_key
    public $autosubscribe;                   // int(4)  
    public $urlshorteningservice;            // string(50)  
    public $inboxed;                         // int(4)  
    public $design_id;                       // int(11)  
    public $viewdesigns;                     // int(4)  
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function getProfile()
    {
        $profile = Profile::staticGet('id', $this->id);
        if (empty($profile)) {
            throw new UserNoProfileException($this);
        }
        return $profile;
    }

    function isSubscribed($other)
    {
        return Subscription::exists($this->getProfile(), $other);
    }

    // 'update' won't write key columns, so we have to do it ourselves.

    function updateKeys(&$orig)
    {
        $this->_connect();
        $parts = array();
        foreach (array('nickname', 'email', 'jabber', 'incomingemail', 'sms', 'carrier', 'smsemail', 'language', 'timezone') as $k) {
            if (strcmp($this->$k, $orig->$k) != 0) {
                $parts[] = $k . ' = ' . $this->_quote($this->$k);
            }
        }
        if (count($parts) == 0) {
            // No changes
            return true;
        }
        $toupdate = implode(', ', $parts);

        $table = common_database_tablename($this->tableName());
        $qry = 'UPDATE ' . $table . ' SET ' . $toupdate .
          ' WHERE id = ' . $this->id;
        $orig->decache();
        $result = $this->query($qry);
        if ($result) {
            $this->encache();
        }
        return $result;
    }

    static function allowed_nickname($nickname)
    {
        // XXX: should already be validated for size, content, etc.
        $blacklist = common_config('nickname', 'blacklist');

        //all directory and file names should be blacklisted
        $d = dir(INSTALLDIR);
        while (false !== ($entry = $d->read())) {
            $blacklist[]=$entry;
        }
        $d->close();

        //all top level names in the router should be blacklisted
        $router = Router::get();
        foreach(array_keys($router->m->getPaths()) as $path){
            if(preg_match('/^\/(.*?)[\/\?]/',$path,$matches)){
                $blacklist[]=$matches[1];
            }
        }
        return !in_array($nickname, $blacklist);
    }

    /**
     * Get the most recent notice posted by this user, if any.
     *
     * @return mixed Notice or null
     */
    function getCurrentNotice()
    {
        $profile = $this->getProfile();
        return $profile->getCurrentNotice();
    }

    function getCarrier()
    {
        return Sms_carrier::staticGet('id', $this->carrier);
    }

    /**
     * @deprecated use Subscription::start($sub, $other);
     */
    function subscribeTo($other)
    {
        return Subscription::start($this->getProfile(), $other);
    }

    function hasBlocked($other)
    {
        $profile = $this->getProfile();
        return $profile->hasBlocked($other);
    }

    /**
     * Register a new user account and profile and set up default subscriptions.
     * If a new-user welcome message is configured, this will be sent.
     *
     * @param array $fields associative array of optional properties
     *              string 'bio'
     *              string 'email'
     *              bool 'email_confirmed' pass true to mark email as pre-confirmed
     *              string 'fullname'
     *              string 'homepage'
     *              string 'location' informal string description of geolocation
     *              float 'lat' decimal latitude for geolocation
     *              float 'lon' decimal longitude for geolocation
     *              int 'location_id' geoname identifier
     *              int 'location_ns' geoname namespace to interpret location_id
     *              string 'nickname' REQUIRED
     *              string 'password' (may be missing for eg OpenID registrations)
     *              string 'code' invite code
     *              ?string 'uri' permalink to notice; defaults to local notice URL
     * @return mixed User object or false on failure
     */
    static function register($fields) {

        // MAGICALLY put fields into current scope

        extract($fields);

        $profile = new Profile();

        if(!empty($email))
        {
            $email = common_canonical_email($email);
        }

        $nickname = common_canonical_nickname($nickname);
        $profile->nickname = $nickname;
        if(! User::allowed_nickname($nickname)){
            common_log(LOG_WARNING, sprintf("Attempted to register a nickname that is not allowed: %s", $profile->nickname),
                       __FILE__);
            return false;
        }
        $profile->profileurl = common_profile_url($nickname);

        if (!empty($fullname)) {
            $profile->fullname = $fullname;
        }
        if (!empty($homepage)) {
            $profile->homepage = $homepage;
        }
        if (!empty($bio)) {
            $profile->bio = $bio;
        }
        if (!empty($location)) {
            $profile->location = $location;

            $loc = Location::fromName($location);

            if (!empty($loc)) {
                $profile->lat         = $loc->lat;
                $profile->lon         = $loc->lon;
                $profile->location_id = $loc->location_id;
                $profile->location_ns = $loc->location_ns;
            }
        }

        $profile->created = common_sql_now();

        $user = new User();

        $user->nickname = $nickname;
        $user->type = $type;
        // Users who respond to invite email have proven their ownership of that address

        if (!empty($code)) {
            $invite = Invitation::staticGet($code);
            if ($invite && $invite->address && $invite->address_type == 'email' && $invite->address == $email) {
                $user->email = $invite->address;
            }
        }

        if(isset($email_confirmed) && $email_confirmed) {
            $user->email = $email;
        }

        // This flag is ignored but still set to 1

        $user->inboxed = 1;

        $user->created = common_sql_now();

        if (Event::handle('StartUserRegister', array(&$user, &$profile))) {

            $profile->query('BEGIN');

            $id = $profile->insert();

            if (empty($id)) {
                common_log_db_error($profile, 'INSERT', __FILE__);
                return false;
            }

            $user->id = $id;
            $user->uri = common_user_uri($user);
            if (!empty($password)) { // may not have a password for OpenID users
                $user->password = common_munge_password($password, $id);
            }

            $result = $user->insert();

            if (!$result) {
                common_log_db_error($user, 'INSERT', __FILE__);
                return false;
            }

            // Everyone gets an inbox

            $inbox = new Inbox();

            $inbox->user_id = $user->id;
            $inbox->notice_ids = '';

            $result = $inbox->insert();

            if (!$result) {
                common_log_db_error($inbox, 'INSERT', __FILE__);
                return false;
            }
            
            //creat default portfolio
            $portfolio = new Portfolio();
	        $portfolio->name = _('uncategorized');
	        $portfolio->owner = $user->id;;
	        $portfolio->created = common_sql_now();
	        $portfolio->modified = common_sql_now();
	        
	        $result = $portfolio->insert();
            
            if (!$result) {
                common_log_db_error($portfolio, 'INSERT', __FILE__);
                return false;
            }
	        
            // Everyone is subscribed to themself

            $subscription = new Subscription();
            $subscription->subscriber = $user->id;
            $subscription->subscribed = $user->id;
            $subscription->created = $user->created;

            $result = $subscription->insert();

            if (!$result) {
                common_log_db_error($subscription, 'INSERT', __FILE__);
                return false;
            }

            if (!empty($email) && !$user->email) {

                $confirm = new Confirm_address();
                $confirm->code = common_confirmation_code(128);
                $confirm->user_id = $user->id;
                $confirm->address = $email;
                $confirm->address_type = 'email';

                $result = $confirm->insert();

                if (!$result) {
                    common_log_db_error($confirm, 'INSERT', __FILE__);
                    return false;
                }
            }

            if (!empty($code) && $user->email) {
                $user->emailChanged();
            }

            // Default system subscription

            $defnick = common_config('newuser', 'default');

            if (!empty($defnick)) {
                $defuser = User::staticGet('nickname', $defnick);
                if (empty($defuser)) {
                    common_log(LOG_WARNING, sprintf("Default user %s does not exist.", $defnick),
                               __FILE__);
                } else {
                    Subscription::start($user, $defuser);
                }
            }

            $profile->query('COMMIT');

            if (!empty($email) && !$user->email) {
                mail_confirm_address($user, $confirm->code, $profile->nickname, $email);
            }

            // Welcome message

            $welcome = common_config('newuser', 'welcome');

            if (!empty($welcome)) {
                $welcomeuser = User::staticGet('nickname', $welcome);
                if (empty($welcomeuser)) {
                    common_log(LOG_WARNING, sprintf("Welcome user %s does not exist.", $defnick),
                               __FILE__);
                } else {
                    $notice = Notice::saveNew($welcomeuser->id,
                                              sprintf(_('Welcome to %1$s, @%2$s!'),
                                                      common_config('site', 'name'),
                                                      $user->nickname),
                                              'system');

                }
            }

            Event::handle('EndUserRegister', array(&$profile, &$user));
        }

        return $user;
    }

    // Things we do when the email changes

    function emailChanged()
    {

        $invites = new Invitation();
        $invites->address = $this->email;
        $invites->address_type = 'email';

        if ($invites->find()) {
            while ($invites->fetch()) {
                $other = User::staticGet($invites->user_id);
                subs_subscribe_to($other, $this);
            }
        }
    }

    function hasFave($notice)
    {
        $cache = common_memcache();

        // XXX: Kind of a hack.

        if ($cache) {
            // This is the stream of favorite notices, in rev chron
            // order. This forces it into cache.

            $ids = Fave::stream($this->id, 0, NOTICE_CACHE_WINDOW);

            // If it's in the list, then it's a fave

            if (in_array($notice->id, $ids)) {
                return true;
            }

            // If we're not past the end of the cache window,
            // then the cache has all available faves, so this one
            // is not a fave.

            if (count($ids) < NOTICE_CACHE_WINDOW) {
                return false;
            }

            // Otherwise, cache doesn't have all faves;
            // fall through to the default
        }

        $fave = Fave::pkeyGet(array('user_id' => $this->id,
                                    'notice_id' => $notice->id));
        return ((is_null($fave)) ? false : true);
    }

    function mutuallySubscribed($other)
    {
        return $this->isSubscribed($other) &&
          $other->isSubscribed($this);
    }

    function mutuallySubscribedUsers()
    {
        // 3-way join; probably should get cached
        $UT = common_config('db','type')=='pgsql'?'"user"':'user';
        $qry = "SELECT $UT.* " .
          "FROM subscription sub1 JOIN $UT ON sub1.subscribed = $UT.id " .
          "JOIN subscription sub2 ON $UT.id = sub2.subscriber " .
          'WHERE sub1.subscriber = %d and sub2.subscribed = %d ' .
          "ORDER BY $UT.nickname";
        $user = new User();
        $user->query(sprintf($qry, $this->id, $this->id));

        return $user;
    }

    function getReplies($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        $ids = Reply::stream($this->id, $offset, $limit, $since_id, $before_id);
        return Notice::getStreamByIds($ids);
    }

    function getTaggedNotices($tag, $offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0) {
        $profile = $this->getProfile();
        return $profile->getTaggedNotices($tag, $offset, $limit, $since_id, $before_id);
    }

    function getNotices($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        $profile = $this->getProfile();
        return $profile->getNotices($offset, $limit, $since_id, $before_id);
    }

    function favoriteNotices($offset=0, $limit=NOTICES_PER_PAGE, $own=false)
    {
        $ids = Fave::stream($this->id, $offset, $limit, $own);
        return Notice::getStreamByIds($ids);
    }

    function noticesWithFriends($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return Inbox::streamNotices($this->id, $offset, $limit, $since_id, $before_id, false);
    }

    function noticeInbox($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return Inbox::streamNotices($this->id, $offset, $limit, $since_id, $before_id, true);
    }

    function friendsTimeline($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return Inbox::streamNotices($this->id, $offset, $limit, $since_id, $before_id, false);
    }

    function ownFriendsTimeline($offset=0, $limit=NOTICES_PER_PAGE, $since_id=0, $before_id=0)
    {
        return Inbox::streamNotices($this->id, $offset, $limit, $since_id, $before_id, true);
    }

    function blowFavesCache()
    {
        $cache = common_memcache();
        if ($cache) {
            // Faves don't happen chronologically, so we need to blow
            // ;last cache, too
            $cache->delete(common_cache_key('fave:ids_by_user:'.$this->id));
            $cache->delete(common_cache_key('fave:ids_by_user:'.$this->id.';last'));
            $cache->delete(common_cache_key('fave:ids_by_user_own:'.$this->id));
            $cache->delete(common_cache_key('fave:ids_by_user_own:'.$this->id.';last'));
        }
        $profile = $this->getProfile();
        $profile->blowFaveCount();
    }

    function getSelfTags()
    {
        return Profile_tag::getTags($this->id, $this->id);
    }

    function setSelfTags($newtags)
    {
        return Profile_tag::setTags($this->id, $this->id, $newtags);
    }

    function block($other)
    {
        // Add a new block record

        // no blocking (and thus unsubbing from) yourself

        if ($this->id == $other->id) {
            common_log(LOG_WARNING,
                sprintf(
                    "Profile ID %d (%s) tried to block his or herself.",
                    $this->id,
                    $this->nickname
                )
            );
            return false;
        }

        $block = new Profile_block();

        // Begin a transaction

        $block->query('BEGIN');

        $block->blocker = $this->id;
        $block->blocked = $other->id;

        $result = $block->insert();

        if (!$result) {
            common_log_db_error($block, 'INSERT', __FILE__);
            return false;
        }

        $self = $this->getProfile();
        if (Subscription::exists($other, $self)) {
            Subscription::cancel($other, $self);
        }

        $block->query('COMMIT');

        return true;
    }

    function unblock($other)
    {
        // Get the block record

        $block = Profile_block::get($this->id, $other->id);

        if (!$block) {
            return false;
        }

        $result = $block->delete();

        if (!$result) {
            common_log_db_error($block, 'DELETE', __FILE__);
            return false;
        }

        return true;
    }

    function isMember($group)
    {
        $profile = $this->getProfile();
        return $profile->isMember($group);
    }

    function isAdmin($group)
    {
        $profile = $this->getProfile();
        return $profile->isAdmin($group);
    }

    function getGroups($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getGroups($offset, $limit);
    }

    function getSubscriptions($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getSubscriptions($offset, $limit);
    }

    function getSubscribers($offset=0, $limit=null)
    {
        $profile = $this->getProfile();
        return $profile->getSubscribers($offset, $limit);
    }

    function getTaggedSubscribers($tag, $offset=0, $limit=null)
    {
        $qry =
          'SELECT profile.* ' .
          'FROM profile JOIN subscription ' .
          'ON profile.id = subscription.subscriber ' .
          'JOIN profile_tag ON (profile_tag.tagged = subscription.subscriber ' .
          'AND profile_tag.tagger = subscription.subscribed) ' .
          'WHERE subscription.subscribed = %d ' .
          "AND profile_tag.tag = '%s' " .
          'AND subscription.subscribed != subscription.subscriber ' .
          'ORDER BY subscription.created DESC ';

        if ($offset) {
            $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        $profile = new Profile();

        $cnt = $profile->query(sprintf($qry, $this->id, $tag));

        return $profile;
    }

    function getTaggedSubscriptions($tag, $offset=0, $limit=null)
    {
        $qry =
          'SELECT profile.* ' .
          'FROM profile JOIN subscription ' .
          'ON profile.id = subscription.subscribed ' .
          'JOIN profile_tag on (profile_tag.tagged = subscription.subscribed ' .
          'AND profile_tag.tagger = subscription.subscriber) ' .
          'WHERE subscription.subscriber = %d ' .
          "AND profile_tag.tag = '%s' " .
          'AND subscription.subscribed != subscription.subscriber ' .
          'ORDER BY subscription.created DESC ';

        $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        $profile = new Profile();

        $profile->query(sprintf($qry, $this->id, $tag));

        return $profile;
    }

    function getDesign()
    {
        return Design::staticGet('id', $this->design_id);
    }

    function hasRight($right)
    {
        $profile = $this->getProfile();
        return $profile->hasRight($right);
    }

    function delete()
    {
        try {
            $profile = $this->getProfile();
            $profile->delete();
        } catch (UserNoProfileException $unp) {
            common_log(LOG_INFO, "User {$this->nickname} has no profile; continuing deletion.");
        }

        $related = array('Fave',
                         'Confirm_address',
                         'Remember_me',
                         'Foreign_link',
                         'Invitation',
                         );

        Event::handle('UserDeleteRelated', array($this, &$related));

        foreach ($related as $cls) {
            $inst = new $cls();
            $inst->user_id = $this->id;
            $inst->delete();
        }

        $this->_deleteTags();
        $this->_deleteBlocks();

        parent::delete();
    }

    function _deleteTags()
    {
        $tag = new Profile_tag();
        $tag->tagger = $this->id;
        $tag->delete();
    }

    function _deleteBlocks()
    {
        $block = new Profile_block();
        $block->blocker = $this->id;
        $block->delete();
        // XXX delete group block? Reset blocker?
    }

    function hasRole($name)
    {
        $profile = $this->getProfile();
        return $profile->hasRole($name);
    }

    function grantRole($name)
    {
        $profile = $this->getProfile();
        return $profile->grantRole($name);
    }

    function revokeRole($name)
    {
        $profile = $this->getProfile();
        return $profile->revokeRole($name);
    }

    function isSandboxed()
    {
        $profile = $this->getProfile();
        return $profile->isSandboxed();
    }

    function isSilenced()
    {
        $profile = $this->getProfile();
        return $profile->isSilenced();
    }

    function repeatedByMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        $ids = Notice::stream(array($this, '_repeatedByMeDirect'),
                              array(),
                              'user:repeated_by_me:'.$this->id,
                              $offset, $limit, $since_id, $max_id, null);

        return Notice::getStreamByIds($ids);
    }

    function _repeatedByMeDirect($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd(); // clears it
        $notice->selectAdd('id');

        $notice->profile_id = $this->id;
        $notice->whereAdd('repeat_of IS NOT NULL');

        $notice->orderBy('id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        if ($since_id != 0) {
            $notice->whereAdd('id > ' . $since_id);
        }

        if ($max_id != 0) {
            $notice->whereAdd('id <= ' . $max_id);
        }

        $ids = array();

        if ($notice->find()) {
            while ($notice->fetch()) {
                $ids[] = $notice->id;
            }
        }

        $notice->free();
        $notice = NULL;

        return $ids;
    }

    function repeatsOfMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        $ids = Notice::stream(array($this, '_repeatsOfMeDirect'),
                              array(),
                              'user:repeats_of_me:'.$this->id,
                              $offset, $limit, $since_id, $max_id);

        return Notice::getStreamByIds($ids);
    }

    function _repeatsOfMeDirect($offset, $limit, $since_id, $max_id)
    {
        $qry =
          'SELECT DISTINCT original.id AS id ' .
          'FROM notice original JOIN notice rept ON original.id = rept.repeat_of ' .
          'WHERE original.profile_id = ' . $this->id . ' ';

        if ($since_id != 0) {
            $qry .= 'AND original.id > ' . $since_id . ' ';
        }

        if ($max_id != 0) {
            $qry .= 'AND original.id <= ' . $max_id . ' ';
        }

        // NOTE: we sort by fave time, not by notice time!

        $qry .= 'ORDER BY original.id DESC ';

        if (!is_null($offset)) {
            $qry .= "LIMIT $limit OFFSET $offset";
        }

        $ids = array();

        $notice = new Notice();

        $notice->query($qry);

        while ($notice->fetch()) {
            $ids[] = $notice->id;
        }

        $notice->free();
        $notice = NULL;

        return $ids;
    }

    function repeatedToMe($offset=0, $limit=20, $since_id=null, $max_id=null)
    {
        throw new Exception("Not implemented since inbox change.");
    }

    function shareLocation()
    {
        $cfg = common_config('location', 'share');

        if ($cfg == 'always') {
            return true;
        } else if ($cfg == 'never') {
            return false;
        } else { // user
            $share = true;

            $prefs = User_location_prefs::staticGet('user_id', $this->id);

            if (empty($prefs)) {
                $share = common_config('location', 'sharedefault');
            } else {
                $share = $prefs->share_location;
                $prefs->free();
            }

            return $share;
        }
    }

    static function siteOwner()
    {
        $owner = self::cacheGet('user:site_owner');

        if ($owner === false) { // cache miss

            $pr = new Profile_role();

            $pr->role = Profile_role::OWNER;

            $pr->orderBy('created');

            $pr->limit(1);

            if ($pr->find(true)) {
                $owner = User::staticGet('id', $pr->profile_id);
            } else {
                $owner = null;
            }

            self::cacheSet('user:site_owner', $owner);
        }

        return $owner;
    }
}
