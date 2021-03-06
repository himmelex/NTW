<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Class for building an in-memory Atom feed for a particular group's
 * timeline.
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
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
 *
 * @category  Feed
 * @package   StatusNet
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('NEWTYPE'))
{
    exit(1);
}

/**
 * Class for group notice feeds.  May contains a reference to the group.
 *
 * @category Feed
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class AtomGroupNoticeFeed extends AtomNoticeFeed
{
    private $group;

    /**
     * Constructor
     *
     * @param Group   $group   the group for the feed
     * @param boolean $indent  flag to turn indenting on or off
     *
     * @return void
     */
    function __construct($group, $indent = true) {
        parent::__construct($indent);
        $this->group = $group;

        $title      = sprintf(_("%s timeline"), $group->nickname);
        $this->setTitle($title);

        $sitename   = common_config('site', 'name');
        $subtitle   = sprintf(
            _('Updates from %1$s on %2$s!'),
            $group->nickname,
            $sitename
        );
        $this->setSubtitle($subtitle);

        $avatar = $group->homepage_logo;
        $logo = ($avatar) ? $avatar : User_group::defaultLogo(AVATAR_PROFILE_SIZE);
        $this->setLogo($logo);

        $this->setUpdated('now');

        $self = common_local_url('ApiTimelineGroup',
                                 array('id' => $group->id,
                                       'format' => 'atom'));
        $this->setId($self);
        $this->setSelfLink($self);

        $this->addAuthorRaw($group->asAtomAuthor());
        $this->setActivitySubject($group->asActivitySubject());

        $this->addLink($group->homeUrl());
    }

    function getGroup()
    {
        return $this->group;
    }

}
