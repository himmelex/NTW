<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Personal tag cloud section
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
 * @category  Widget
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

/**
 * Personal tag cloud section
 *
 * @category Widget
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class SubscriptionsPeopleSelfTagCloudSection extends SubPeopleTagCloudSection
{
    function title()
    {
        return _('People Tagcloud as self-tagged');
    }

    function query() {
//        return 'select tag, count(tag) as weight from subscription left join profile_tag on tagger = subscriber where subscribed=%d and subscriber != subscribed and tagger = tagged group by tag order by weight desc';



        return 'select tag, count(tag) as weight from subscription left join profile_tag on tagger = subscribed where subscriber=%d and subscribed != subscriber and tagger = tagged and tag is not null group by tag order by weight desc';

//        return 'select tag, count(tag) as weight from subscription left join profile_tag on tagger = subscriber where subscribed=%d and subscribed != subscriber and tagger = tagged and tag is not null group by tag order by weight desc';
    }
}

