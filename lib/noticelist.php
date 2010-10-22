<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * widget for displaying a list of notices
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
 * @category  UI
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('NEWTYPE') && !defined('DWORKS')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/favorform.php';
require_once INSTALLDIR.'/lib/disfavorform.php';
require_once INSTALLDIR.'/lib/attachmentlist.php';


/**
 * widget for displaying a list of notices
 *
 * There are a number of actions that display a list of notices, in
 * reverse chronological order. This widget abstracts out most of the
 * code for UI for notice lists. It's overridden to hide some
 * data for e.g. the profile page.
 *
 * @category UI
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @see      Notice
 * @see      NoticeListItem
 * @see      ProfileNoticeList
 */

class NoticeList extends Widget
{
    /** the current stream of notices being displayed. */

    var $notice = null;

    /**
     * constructor
     *
     * @param Notice $notice stream of notices from DB_DataObject
     */

    function __construct($notice, $out=null)
    {
        parent::__construct($out);
        $this->notice = $notice;
    }

    /**
     * show the list of notices
     *
     * "Uses up" the stream by looping through it. So, probably can't
     * be called twice on the same list.
     *
     * @return int count of notices listed.
     */

    function show()
    {
        $this->out->elementStart('div', array('id' =>'notices_primary'));
        $this->out->elementStart('ol', array('class' => 'notices xoxo'));

        $cnt = 0;

        while ($this->notice->fetch() && $cnt <= NOTICES_PER_PAGE) {
            $cnt++;

            if ($cnt > NOTICES_PER_PAGE) {
                break;
            }

            $item = $this->newListItem($this->notice);
            $item->show();
        }        
        $this->out->elementEnd('ol');
        $this->out->elementEnd('div');
        return $cnt;
    }

    /**
     * returns a new list item for the current notice
     *
     * Recipe (factory?) method; overridden by sub-classes to give
     * a different list item class.
     *
     * @param Notice $notice the current notice
     *
     * @return NoticeListItem a list item for displaying the notice
     */

    function newListItem($notice)
    {
        return new NoticeListItem($notice, $this->out);
    }
}

/**
 * widget for displaying a single notice
 *
 * This widget has the core smarts for showing a single notice: what to display,
 * where, and under which circumstances. Its key method is show(); this is a recipe
 * that calls all the other show*() methods to build up a single notice. The
 * ProfileNoticeListItem subclass, for example, overrides showAuthor() to skip
 * author info (since that's implicit by the data in the page).
 *
 * @category UI
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @see      NoticeList
 * @see      ProfileNoticeListItem
 */

class NoticeListItem extends Widget
{
    /** The notice this item will show. */

    var $notice = null;

    /** The notice that was repeated. */

    var $repeat = null;

    /** The profile of the author of the notice, extracted once for convenience. */

    var $profile = null;

    /**
     * constructor
     *
     * Also initializes the profile attribute.
     *
     * @param Notice $notice The notice we'll display
     */

    function __construct($notice, $out=null)
    {
        parent::__construct($out);
        if (!empty($notice->repeat_of)) {
            $original = Notice::staticGet('id', $notice->repeat_of);
            if (empty($original)) { // could have been deleted
                $this->notice = $notice;
            } else {
                $this->notice = $original;
                $this->repeat = $notice;
            }
        } else {
            $this->notice  = $notice;
        }
        $this->profile = $this->notice->getProfile();
    }

    /**
     * recipe function for displaying a single notice.
     *
     * This uses all the other methods to correctly display a notice. Override
     * it or one of the others to fine-tune the output.
     *
     * @return void
     */

    function show()
    {
        if (empty($this->notice)) {
            common_log(LOG_WARNING, "Trying to show missing notice; skipping.");
            return;
        } else if (empty($this->profile)) {
            common_log(LOG_WARNING, "Trying to show missing profile (" . $this->notice->profile_id . "); skipping.");
            return;
        }

        $this->showStart();
        if (Event::handle('StartShowNoticeItem', array($this))) {
            $this->showNotice();
            Event::handle('EndShowNoticeItem', array($this));
        }
        $this->showEnd();
    }

    function showNotice()
    {
        $this->showAuthor();
        $this->showContent();
    }

    function showNoticeInfo()
    {
        $this->out->elementStart('div', 'entry-info');
        $this->showNoticeLink();
        $this->out->elementEnd('div');
        $this->showNoticeOptions();
    }

    function showNoticeOptions()
    {
        $user = common_current_user();
        if ($user) {
            $this->out->elementStart('div', 'notice-options');
            $this->showDeleteLink();
            $this->out->elementEnd('div');
        }
    }

    /**
     * start a single notice.
     *
     * @return void
     */

    function showStart()
    {
        // XXX: RDFa
        // TODO: add notice_type class e.g., notice_video, notice_image
        $id = (empty($this->repeat)) ? $this->notice->id : $this->repeat->id;
        $this->out->elementStart('li', array('class' => 'hentry notice',
                                             'id' => 'notice-' . $id));
    }

    /**
     * show the "favorite" form
     *
     * @return void
     */

    function showFaveForm()
    {
        $user = common_current_user();
        if ($user) {
            if ($user->hasFave($this->notice)) {
                $disfavor = new DisfavorForm($this->out, $this->notice);
                $disfavor->show();
            } else {
                $favor = new FavorForm($this->out, $this->notice);
                $favor->show();
            }
        }
    }

    /**
     * show the author of a notice
     *
     * By default, this shows the avatar and (linked) nickname of the author.
     *
     * @return void
     */

    function showAuthor()
    {
        $this->out->elementStart('span', 'profile_author');
        $attrs = array('href' => $this->profile->profileurl,
                       'class' => 'url');
        if (!empty($this->profile->fullname)) {
            $attrs['title'] = $this->profile->fullname . ' (' . $this->profile->nickname . ')';
        }
        $this->out->elementStart('a', $attrs);
        $this->showAvatar();
        $this->out->text(' ');
        $this->showNickname();
        $this->out->elementEnd('a');
        $this->out->elementEnd('span');
    }

    /**
     * show the avatar of the notice's author
     *
     * This will use the default avatar if no avatar is assigned for the author.
     * It makes a link to the author's profile.
     *
     * @return void
     */

    function showAvatar()
    {
        $avatar_size = AVATAR_STREAM_SIZE;
        $avatar = $this->profile->getAvatar($avatar_size);

        $this->out->element('img', array('src' => ($avatar) ?
                                         $avatar->displayUrl() :
                                         Avatar::defaultImage($avatar_size),
                                         'class' => 'avatar photo',
                                         'width' => $avatar_size,
                                         'height' => $avatar_size,
                                         'alt' =>
                                         ($this->profile->fullname) ?
                                         $this->profile->fullname :
                                         $this->profile->nickname));
    }

    /**
     * show the nickname of the author
     *
     * Links to the author's profile page
     *
     * @return void
     */

    function showNickname()
    {
        $this->out->raw('<span class="nickname fn">' .
                        htmlspecialchars($this->profile->nickname) .
                        '</span>');
    }

    /**
     * show the content of the notice
     *
     * Shows the content of the notice. This is pre-rendered for efficiency
     * at save time. Some very old notices might not be pre-rendered, so
     * they're rendered on the spot.
     *
     * @return void
     */

    function showContent()
    {
        // FIXME: URL, image, video, audio
        $this->out->elementStart('div', array('class' => 'notice-entry-content'));
        if ($this->notice->rendered) {
            $this->out->raw($this->notice->rendered);
        } else {
            // XXX: may be some uncooked notices in the DB,
            // we cook them right now. This should probably disappear in future
            // versions (>> 0.4.x)
            $this->out->raw(common_render_content($this->notice->content, $this->notice));
        }
        $this->showNoticeInfo();
        $this->out->elementEnd('div');
    }

    /**
     * show the link to the main page for the notice
     *
     * Displays a link to the page for a notice, with "relative" time. Tries to
     * get remote notice URLs correct, but doesn't always succeed.
     *
     * @return void
     */

    function showNoticeLink()
    {
        $noticeurl = $this->notice->bestUrl();

        // above should always return an URL
        
        assert(!empty($noticeurl));
        
        $dt = common_date_iso8601($this->notice->created);
        $this->out->element('div', array('class' => 'published',
                                          'title' => $dt),
                            common_date_string($this->notice->created));
    }

    /**
     * show the notice location
     *
     * shows the notice location in the correct language.
     *
     * If an URL is available, makes a link. Otherwise, just a span.
     *
     * @return void
     */

    function showNoticeLocation()
    {
        $id = $this->notice->id;

        $location = $this->notice->getLocation();

        if (empty($location)) {
            return;
        }

        $name = $location->getName();

        $lat = $this->notice->lat;
        $lon = $this->notice->lon;
        $latlon = (!empty($lat) && !empty($lon)) ? $lat.';'.$lon : '';

        if (empty($name)) {
            $latdms = $this->decimalDegreesToDMS(abs($lat));
            $londms = $this->decimalDegreesToDMS(abs($lon));
            $name = sprintf(
                _('%1$u°%2$u\'%3$u"%4$s %5$u°%6$u\'%7$u"%8$s'),
                $latdms['deg'],$latdms['min'], $latdms['sec'],($lat>0?_('N'):_('S')),
                $londms['deg'],$londms['min'], $londms['sec'],($lon>0?_('E'):_('W')));
        }

        $url  = $location->getUrl();

        $this->out->text(' ');
        $this->out->elementStart('span', array('class' => 'location'));
        $this->out->text(_('at'));
        $this->out->text(' ');
        if (empty($url)) {
            $this->out->element('abbr', array('class' => 'geo',
                                              'title' => $latlon),
                                $name);
        } else {
            $xstr = new XMLStringer(false);
            $xstr->elementStart('a', array('href' => $url,
                                           'rel' => 'external'));
            $xstr->element('abbr', array('class' => 'geo',
                                         'title' => $latlon),
                           $name);
            $xstr->elementEnd('a');
            $this->out->raw($xstr->getString());
        }
        $this->out->elementEnd('span');
    }

    function decimalDegreesToDMS($dec)
    {

        $vars = explode(".",$dec);
        $deg = $vars[0];
        $tempma = "0.".$vars[1];

        $tempma = $tempma * 3600;
        $min = floor($tempma / 60);
        $sec = $tempma - ($min*60);

        return array("deg"=>$deg,"min"=>$min,"sec"=>$sec);
    }

    /**
     * Show the source of the notice
     *
     * Either the name (and link) of the API client that posted the notice,
     * or one of other other channels.
     *
     * @return void
     */

    function showNoticeSource()
    {
        if ($this->notice->source) {
            $this->out->text(' ');
            $this->out->elementStart('span', 'source');
            $this->out->text(_('from'));
            $source_name = _($this->notice->source);
            $this->out->text(' ');
            switch ($this->notice->source) {
             case 'web':
             case 'xmpp':
             case 'mail':
             case 'omb':
             case 'system':
             case 'api':
                $this->out->element('span', 'device', $source_name);
                break;
             default:

                $name = $source_name;
                $url  = null;

                if (Event::handle('StartNoticeSourceLink', array($this->notice, &$name, &$url, &$title))) {
                    $ns = Notice_source::staticGet($this->notice->source);

                    if ($ns) {
                        $name = $ns->name;
                        $url  = $ns->url;
                    } else {
                        $app = Oauth_application::staticGet('name', $this->notice->source);
                        if ($app) {
                            $name = $app->name;
                            $url  = $app->source_url;
                        }
                    }
                }
                Event::handle('EndNoticeSourceLink', array($this->notice, &$name, &$url, &$title));

                if (!empty($name) && !empty($url)) {
                    $this->out->elementStart('span', 'device');
                    $this->out->element('a', array('href' => $url,
                                                   'rel' => 'external',
                                                   'title' => $title),
                                        $name);
                    $this->out->elementEnd('span');
                } else {
                    $this->out->element('span', 'device', $name);
                }
                break;
            }
            $this->out->elementEnd('span');
        }
    }

    /**
     * show link to notice this notice is a reply to
     *
     * If this notice is a reply, show a link to the notice it is replying to. The
     * heavy lifting for figuring out replies happens at save time.
     *
     * @return void
     */

    function showContext()
    {
        if ($this->notice->hasConversation()) {
            $conv = Conversation::staticGet(
                'id',
                $this->notice->conversation
            );
            $convurl = $conv->uri;
            if (!empty($convurl)) {
                $this->out->text(' ');
                $this->out->element(
                    'a',
                    array(
                    'href' => $convurl.'#notice-'.$this->notice->id,
                    'class' => 'response'),
                    _('in context')
                );
            } else {
                $msg = sprintf(
                    "Couldn't find Conversation ID %d to make 'in context'"
                    . "link for Notice ID %d",
                    $this->notice->conversation,
                    $this->notice->id
                );
                common_log(LOG_WARNING, $msg);
            }
        }
    }

    /**
     * show a link to the author of repeat
     *
     * @return void
     */

    function showRepeat()
    {
        if (!empty($this->repeat)) {

            $repeater = Profile::staticGet('id', $this->repeat->profile_id);

            $attrs = array('href' => $repeater->profileurl,
                           'class' => 'url');

            if (!empty($repeater->fullname)) {
                $attrs['title'] = $repeater->fullname . ' (' . $repeater->nickname . ')';
            }

            $this->out->elementStart('span', 'repeat vcard');

            $this->out->raw(_('Repeated by'));

            $this->out->elementStart('a', $attrs);
            $this->out->element('span', 'fn nickname', $repeater->nickname);
            $this->out->elementEnd('a');

            $this->out->elementEnd('span');
        }
    }

    /**
     * show a link to reply to the current notice
     *
     * Should either do the reply in the current notice form (if available), or
     * link out to the notice-posting form. A little flakey, doesn't always work.
     *
     * @return void
     */

    function showReplyLink()
    {
        if (common_logged_in()) {
            $this->out->text(' ');
            $reply_url = common_local_url('newnotice',
                                          array('replyto' => $this->profile->nickname, 'inreplyto' => $this->notice->id));
            $this->out->elementStart('a', array('href' => $reply_url,
                                                'class' => 'notice_reply',
                                                'title' => _('Reply to this notice')));
            $this->out->text(_('Reply'));
            $this->out->text(' ');
            $this->out->element('span', 'notice_id', $this->notice->id);
            $this->out->elementEnd('a');
        }
    }

    /**
     * if the user is the author, let them delete the notice
     *
     * @return void
     */

    function showDeleteLink()
    {
        $user = common_current_user();

        $todel = (empty($this->repeat)) ? $this->notice : $this->repeat;

        if (!empty($user) &&
            ($todel->profile_id == $user->id || $user->hasRight(Right::DELETEOTHERSNOTICE))) {
            $this->out->text(' ');
            $deleteurl = common_local_url('deletenotice',
                                          array('notice' => $todel->id));
            $this->out->element('a', array('href' => $deleteurl,
                                           'class' => 'notice_delete',
                                           'title' => _('Delete this notice')), _('Delete'));
        }
    }

    /**
     * show the form to repeat a notice
     *
     * @return void
     */

    function showRepeatForm()
    {
        $user = common_current_user();
        if ($user && $user->id != $this->notice->profile_id) {
            $this->out->text(' ');
            $profile = $user->getProfile();
            if ($profile->hasRepeated($this->notice->id)) {
                $this->out->element('span', array('class' => 'repeated',
                                                  'title' => _('Notice repeated')),
                                            _('Repeated'));
            } else {
                $rf = new RepeatForm($this->out, $this->notice);
                $rf->show();
            }
        }
    }
    
    
    /**
     * finish the notice
     *
     * Close the last elements in the notice list item
     *
     * @return void
     */

    function showEnd()
    {
        $this->out->elementEnd('li');
    }
}
