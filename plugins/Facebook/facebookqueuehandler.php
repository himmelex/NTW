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

require_once INSTALLDIR . '/plugins/Facebook/facebookutil.php';

class FacebookQueueHandler extends QueueHandler
{
    function transport()
    {
        return 'facebook';
    }

    function handle($notice)
    {
        if ($this->_isLocal($notice)) {
            return facebookBroadcastNotice($notice);
        }
        return true;
    }

    /**
     * Determine whether the notice was locally created
     *
     * @param Notice $notice the notice
     *
     * @return boolean locality
     */
    function _isLocal($notice)
    {
        return ($notice->is_local == Notice::LOCAL_PUBLIC ||
                $notice->is_local == Notice::LOCAL_NONPUBLIC);
    }
}
