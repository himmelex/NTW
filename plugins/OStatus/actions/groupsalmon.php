<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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

/**
 * @package OStatusPlugin
 * @author James Walker <james@status.net>
 */

if (!defined('NEWTYPE')) {
    exit(1);
}

class GroupsalmonAction extends SalmonAction
{
    var $group = null;

    function prepare($args)
    {
        parent::prepare($args);

        $id = $this->trimmed('id');

        if (!$id) {
            $this->clientError(_('No ID.'));
        }

        $this->group = User_group::staticGet('id', $id);

        if (empty($this->group)) {
            $this->clientError(_('No such group.'));
        }

        $oprofile = Ostatus_profile::staticGet('group_id', $id);
        if ($oprofile) {
            $this->clientError(_m("Can't accept remote posts for a remote group."));
        }

        return true;
    }

    /**
     * We've gotten a post event on the Salmon backchannel, probably a reply.
     */

    function handlePost()
    {
        // @fixme process all objects?
        switch ($this->act->objects[0]->type) {
        case ActivityObject::ARTICLE:
        case ActivityObject::BLOGENTRY:
        case ActivityObject::NOTE:
        case ActivityObject::STATUS:
        case ActivityObject::COMMENT:
            break;
        default:
            throw new ClientException("Can't handle that kind of post.");
        }

        // Notice must be to the attention of this group

        $context = $this->act->context;

        if (empty($context->attention)) {
            throw new ClientException("Not to the attention of anyone.");
        } else {
            $uri = common_local_url('groupbyid', array('id' => $this->group->id));
            if (!in_array($uri, $context->attention)) {
                throw new ClientException("Not to the attention of this group.");
            }
        }

        $profile = $this->ensureProfile();
        $this->saveNotice();
    }

    /**
     * We've gotten a follow/subscribe notification from a remote user.
     * Save a subscription relationship for them.
     */

    /**
     * Postel's law: consider a "follow" notification as a "join".
     */
    function handleFollow()
    {
        $this->handleJoin();
    }

    /**
     * Postel's law: consider an "unfollow" notification as a "leave".
     */
    function handleUnfollow()
    {
        $this->handleLeave();
    }

    /**
     * A remote user joined our group.
     * @fixme move permission checks and event call into common code,
     *        currently we're doing the main logic in joingroup action
     *        and so have to repeat it here.
     */

    function handleJoin()
    {
        $oprofile = $this->ensureProfile();
        if (!$oprofile) {
            $this->clientError(_m("Can't read profile to set up group membership."));
        }
        if ($oprofile->isGroup()) {
            $this->clientError(_m("Groups can't join groups."));
        }

        common_log(LOG_INFO, "Remote profile {$oprofile->uri} joining local group {$this->group->nickname}");
        $profile = $oprofile->localProfile();

        if ($profile->isMember($this->group)) {
            // Already a member; we'll take it silently to aid in resolving
            // inconsistencies on the other side.
            return true;
        }

        if (Group_block::isBlocked($this->group, $profile)) {
            $this->clientError(_('You have been blocked from that group by the admin.'), 403);
            return false;
        }

        try {
            // @fixme that event currently passes a user from main UI
            // Event should probably move into Group_member::join
            // and take a Profile object.
            //
            //if (Event::handle('StartJoinGroup', array($this->group, $profile))) {
                Group_member::join($this->group->id, $profile->id);
                //Event::handle('EndJoinGroup', array($this->group, $profile));
            //}
        } catch (Exception $e) {
            $this->serverError(sprintf(_m('Could not join remote user %1$s to group %2$s.'),
                                       $oprofile->uri, $this->group->nickname));
        }
    }

    /**
     * A remote user left our group.
     */

    function handleLeave()
    {
        $oprofile = $this->ensureProfile();
        if (!$oprofile) {
            $this->clientError(_m("Can't read profile to cancel group membership."));
        }
        if ($oprofile->isGroup()) {
            $this->clientError(_m("Groups can't join groups."));
        }

        common_log(LOG_INFO, "Remote profile {$oprofile->uri} leaving local group {$this->group->nickname}");
        $profile = $oprofile->localProfile();

        try {
            // @fixme event needs to be refactored as above
            //if (Event::handle('StartLeaveGroup', array($this->group, $profile))) {
                Group_member::leave($this->group->id, $profile->id);
                //Event::handle('EndLeaveGroup', array($this->group, $profile));
            //}
        } catch (Exception $e) {
            $this->serverError(sprintf(_m('Could not remove remote user %1$s from group %2$s.'),
                                       $oprofile->uri, $this->group->nickname));
            return;
        }
    }

}
