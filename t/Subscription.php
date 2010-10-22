<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) { exit(1); }

/**
 * Table Definition for subscription
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Subscription extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'subscription';                    // table name
    public $subscriber;                      // int(11)  not_null primary_key multiple_key
    public $subscribed;                      // int(11)  not_null primary_key multiple_key
    public $jabber;                          // int(4)  
    public $sms;                             // int(4)  
    public $token;                           // string(255)  multiple_key binary
    public $secret;                          // string(255)  binary
    public $created;                         // datetime(19)  not_null binary
    public $modified;                        // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Subscription',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Subscription', $kv);
    }

    /**
     * Make a new subscription
     *
     * @param Profile $subscriber party to receive new notices
     * @param Profile $other      party sending notices; publisher
     *
     * @return Subscription new subscription
     */

    static function start($subscriber, $other)
    {
        // @fixme should we enforce this as profiles in callers instead?
        if ($subscriber instanceof User) {
            $subscriber = $subscriber->getProfile();
        }
        if ($other instanceof User) {
            $other = $other->getProfile();
        }

        if (!$subscriber->hasRight(Right::SUBSCRIBE)) {
            throw new Exception(_('You have been banned from subscribing.'));
        }

        if (self::exists($subscriber, $other)) {
            throw new Exception(_('Already subscribed!'));
        }

        if ($other->hasBlocked($subscriber)) {
            throw new Exception(_('User has blocked you.'));
        }

        if (Event::handle('StartSubscribe', array($subscriber, $other))) {
            $sub = self::saveNew($subscriber->id, $other->id);
            $sub->notify();

            self::blow('user:notices_with_friends:%d', $subscriber->id);

            $subscriber->blowSubscriptionsCount();
            $other->blowSubscribersCount();

            $otherUser = User::staticGet('id', $other->id);

            if (!empty($otherUser) &&
                $otherUser->autosubscribe &&
                !self::exists($other, $subscriber) &&
                !$subscriber->hasBlocked($other)) {

                try {
                    self::start($other, $subscriber);
                } catch (Exception $e) {
                    common_log(LOG_ERR, "Exception during autosubscribe of {$other->nickname} to profile {$subscriber->id}: {$e->getMessage()}");
                }
            }

            Event::handle('EndSubscribe', array($subscriber, $other));
        }

        return true;
    }

    /**
     * Low-level subscription save.
     * Outside callers should use Subscription::start()
     */
    protected function saveNew($subscriber_id, $other_id)
    {
        $sub = new Subscription();

        $sub->subscriber = $subscriber_id;
        $sub->subscribed = $other_id;
        $sub->jabber     = 1;
        $sub->sms        = 1;
        $sub->created    = common_sql_now();

        $result = $sub->insert();

        if (!$result) {
            common_log_db_error($sub, 'INSERT', __FILE__);
            throw new Exception(_('Could not save subscription.'));
        }

        return $sub;
    }

    function notify()
    {
        # XXX: add other notifications (Jabber, SMS) here
        # XXX: queue this and handle it offline
        # XXX: Whatever happens, do it in Twitter-like API, too

        $this->notifyEmail();
    }

    function notifyEmail()
    {
        $subscribedUser = User::staticGet('id', $this->subscribed);

        if (!empty($subscribedUser)) {

            $subscriber = Profile::staticGet('id', $this->subscriber);

            mail_subscribe_notify_profile($subscribedUser, $subscriber);
        }
    }

    /**
     * Cancel a subscription
     *
     */

    function cancel($subscriber, $other)
    {
        if (!self::exists($subscriber, $other)) {
            throw new Exception(_('Not subscribed!'));
        }

        // Don't allow deleting self subs

        if ($subscriber->id == $other->id) {
            throw new Exception(_('Couldn\'t delete self-subscription.'));
        }

        if (Event::handle('StartUnsubscribe', array($subscriber, $other))) {

            $sub = Subscription::pkeyGet(array('subscriber' => $subscriber->id,
                                               'subscribed' => $other->id));

            // note we checked for existence above

            assert(!empty($sub));

            // @todo: move this block to EndSubscribe handler for
            // OMB plugin when it exists.

            if (!empty($sub->token)) {

                $token = new Token();

                $token->tok    = $sub->token;

                if ($token->find(true)) {

                    $result = $token->delete();

                    if (!$result) {
                        common_log_db_error($token, 'DELETE', __FILE__);
                        throw new Exception(_('Couldn\'t delete subscription OMB token.'));
                    }
                } else {
                    common_log(LOG_ERR, "Couldn't find credentials with token {$token->tok}");
                }
            }

            $result = $sub->delete();

            if (!$result) {
                common_log_db_error($sub, 'DELETE', __FILE__);
                throw new Exception(_('Couldn\'t delete subscription.'));
            }

            self::blow('user:notices_with_friends:%d', $subscriber->id);

            $subscriber->blowSubscriptionsCount();
            $other->blowSubscribersCount();

            Event::handle('EndUnsubscribe', array($subscriber, $other));
        }

        return;
    }

    function exists($subscriber, $other)
    {
        $sub = Subscription::pkeyGet(array('subscriber' => $subscriber->id,
                                           'subscribed' => $other->id));
        return (empty($sub)) ? false : true;
    }
}
