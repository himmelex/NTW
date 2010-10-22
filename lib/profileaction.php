<?php

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/profileminilist.php';

class ProfileAction extends Action
{
	var $left_section = true;
    var $right_section = null;
    var $page    = null;
    var $profile = null;
    var $tag     = null;

    function prepare($args)
    {
        parent::prepare($args);

        $nickname_arg = $this->arg('nickname');
        $nickname     = common_canonical_nickname($nickname_arg);

        // Permanent redirect on non-canonical nickname

        if ($nickname_arg != $nickname) {
            $args = array('nickname' => $nickname);
            if ($this->arg('page') && $this->arg('page') != 1) {
                $args['page'] = $this->arg['page'];
            }
            common_redirect(common_local_url($this->trimmed('action'), $args), 301);
            return false;
        }

        $this->user = User::staticGet('nickname', $nickname);

        if (!$this->user) {
            $this->clientError(_('用户不存在'), 404);
            return false;
        }

        $this->profile = $this->user->getProfile();

        if (!$this->profile) {
            $this->serverError(_('无该用户资料'));
            return false;
        }

        $this->tag = $this->trimmed('tag');
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;
        common_set_returnto($this->selfUrl());
        return true;
    }

    function showLeftSections()
    {
    	$this->showProfile();
    	$this->showStatistics();
        $this->showSubscriptions();     
        $cur = common_current_user();
        if (!empty($cur) && $cur->id == $this->user->id) {
        	$this->showSubscribers();
        }
    }
    
    function showProfile()
    {
        $profile = new UserProfile($this, $this->user, $this->profile);
        $profile->show();
    }

    function showSubscriptions()
    {
        $profile = $this->user->getSubscriptions(0, PROFILES_PER_MINILIST + 1);

        $this->elementStart('div', array('id' => 'entity_subscriptions',
                                         'class' => 'section'));
        if (Event::handle('StartShowSubscriptionsMiniList', array($this))) {
            $this->element('h2', null, _('关注的用户'));

            $cnt = 0;

            if (!empty($profile)) {
                $pml = new ProfileMiniList($profile, $this);
                $cnt = $pml->show();
                if ($cnt == 0) {
                    $this->element('p', null, _('(无)'));
                }
            }

            if ($cnt > PROFILES_PER_MINILIST) {
                $this->elementStart('p');
                $this->element('a', array('href' => common_local_url('subscriptions',
                                                                     array('nickname' => $this->profile->nickname)),
                                          'class' => 'more'),
                               _('查看全部'));
                $this->elementEnd('p');
            }

            Event::handle('EndShowSubscriptionsMiniList', array($this));
        }
        $this->elementEnd('div');
    }

    function showSubscribers()
    {
        $profile = $this->user->getSubscribers(0, PROFILES_PER_MINILIST + 1);

        $this->elementStart('div', array('id' => 'entity_subscribers',
                                         'class' => 'section'));

        if (Event::handle('StartShowSubscribersMiniList', array($this))) {

            $this->element('h2', null, _('谁在关注我'));

            $cnt = 0;

            if (!empty($profile)) {
                $sml = new SubscribersMiniList($profile, $this);
                $cnt = $sml->show();
                if ($cnt == 0) {
                    $this->element('p', null, _('(无)'));
                }
            }

            if ($cnt > PROFILES_PER_MINILIST) {
                $this->elementStart('p');
                $this->element('a', array('href' => common_local_url('subscribers',
                                                                     array('nickname' => $this->profile->nickname)),
                                          'class' => 'more'),
                               _('查看全部'));
                $this->elementEnd('p');
            }

            Event::handle('EndShowSubscribersMiniList', array($this));
        }

        $this->elementEnd('div');
    }

    function showStatistics()
    {
        $subs_count   = $this->profile->subscriptionCount();
        $subbed_count = $this->profile->subscriberCount();
        $notice_count = $this->profile->noticeCount();

        $this->elementStart('div', array('id' => 'entity_statistics',
                                         'class' => 'section'));
        $this->element('h2', null, _('基本信息'));
        $this->elementEnd('div');
    }
}

class SubscribersMiniList extends ProfileMiniList
{
    function newListItem($profile)
    {
        return new SubscribersMiniListItem($profile, $this->action);
    }
}

class SubscribersMiniListItem extends ProfileMiniListItem
{
    function linkAttributes()
    {
        $aAttrs = parent::linkAttributes();
        if (common_config('nofollow', 'subscribers')) {
            $aAttrs['rel'] .= ' nofollow';
        }
        return $aAttrs;
    }
}

