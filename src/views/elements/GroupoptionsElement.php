<?php
/**
 * SeekQuarry/Yioop --
 * Open Source Pure PHP Search Engine, Crawler, and Indexer
 *
 * Copyright (C) 2009 - 2019  Chris Pollett chris@pollett.org
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * END LICENSE
 *
 * @author Chris Pollett chris@pollett.org
 * @license https://www.gnu.org/licenses/ GPL3
 * @link https://www.seekquarry.com/
 * @copyright 2009 - 2019
 * @filesource
 */
namespace seekquarry\yioop\views\elements;

use seekquarry\yioop as B;
use seekquarry\yioop\configs as C;
use seekquarry\yioop\library as L;
use seekquarry\yioop\library\CrawlConstants;
/**
 * Element responsible for drawing the options side bar for group and
 * wiki pages. These options include recently viewed wiki pages, groups, and
 * threads
 *
 * @author Chris Pollett
 */
class GroupoptionsElement extends Element implements CrawlConstants
{
    /**
     * Method responsible for drawing the options side bar  for group and
     * wiki pages. These options include recently viewed wiki pages, groups, and
     * threads
     *
     * @param array $data needed to populate the links on page
     */
    public function render($data)
    {
        $logged_in = !empty($data["ADMIN"]);
        $is_wiki = isset($data['ELEMENT']) && $data['ELEMENT'] == 'wiki';
        $token_string = ($logged_in && isset($data[C\CSRF_TOKEN])) ?
            C\CSRF_TOKEN . "=" . $data[C\CSRF_TOKEN] : "";
        $base_query = ($is_wiki) ? htmlentities(B\wikiUrl("", true,
            $data['CONTROLLER'], $data["GROUP"]["GROUP_ID"])) : B\feedsUrl("",
                "", true, $data['CONTROLLER']) . $token_string;
        $admin_url = htmlentities(B\controllerUrl('admin', true));
        $register_url = htmlentities(B\controllerUrl('register', true));
        $can_edit = $logged_in && isset($data["CAN_EDIT"]) && $data["CAN_EDIT"];
        $logo = C\LOGO_MEDIUM;
        if ($_SERVER["MOBILE"]) {
            $logo = C\LOGO_SMALL;
        }
        ?>
        <div id='more-options-background'
            onclick='javascript:toggleOptions()'>
        </div>
        <nav id="more-options" class="more-options">
        <h2><a href="<?=C\BASE_URL ?><?php
            if ($logged_in) {
                e("?$token_string");
            }
            ?>"><img src="<?= C\BASE_URL . $logo ?>" alt="<?=
            $this->view->logo_alt_text ?>" /></a></h2>
        <h2 class="option-heading"><?php
            if ($logged_in) {
                e(tl('groupoptions_element_welcome_user',
                    $_SESSION['USER_NAME']));
            } else {
                e(tl('groupoptions_element_welcome'));
            }
            ?></h2>
            <ul class='square-list'>
            <?php
            if (!$logged_in) {
                ?><li><a href="<?= B\controllerUrl('admin') ?>"><?=
                tl('groupoptions_element_signin') ?></a></li><?php
            } else {
                ?><li><a href="<?=$admin_url . $token_string ?>"><?=
                tl('groupoptions_element_manage_account') ?></a></li>
                <li><a href="<?=$admin_url . $token_string .
                    '&a=manageGroups' ?>"><?=
                tl('groupoptions_element_manage_groups') ?></a></li>
                <li><a href="<?=C\BASE_URL ?>?a=signout"><?=
                    tl('groupoptions_element_signout') ?></a></li><?php
            }
            if ((!$logged_in) &&
                in_array(C\REGISTRATION_TYPE, ['no_activation',
                'email_registration', 'admin_activation'])) { ?>
                <li><a href="<?=rtrim($register_url . "a=createAccount&amp;" .
                    $token_string, "?&amp;") ?>"><?=
                    tl('groupoptions_element_create_account')
                    ?></a></li>
                <?php
            }?>
            </ul>
            <?php
            if (isset($data['ELEMENT']) && $data['ELEMENT'] == 'wiki') {
                $human_page_name = str_replace("_", " ", $data['PAGE_NAME']);
                if (!empty($data['SUB_PATH'])) {
                    $full_human_page_name = $human_page_name ."/".
                        $data['SUB_PATH'];
                } else {
                    $full_human_page_name = $human_page_name;
                }
                $wiki_base_url = htmlentities(B\wikiUrl(
                        $data['PAGE_NAME'], true, $data['CONTROLLER'],
                        $data["GROUP"]["GROUP_ID"])) . $token_string;
                $options = [ tl('groupoptions_element_page_places',
                    $full_human_page_name, $data['GROUP']['GROUP_NAME']) => ""];
                if ($data["MODE"] != 'pages') {
                    if ($can_edit) {
                        $modes = [
                            "read" => tl('groupoptions_element_read_page',
                                $full_human_page_name,
                                $data['GROUP']['GROUP_NAME']),
                            "edit" => tl('groupoptions_element_edit_page',
                                $full_human_page_name),
                            "history" => tl('groupoptions_element_page_history',
                                $human_page_name),
                        ];
                    } else {
                        $modes = [
                            "read" => tl('groupoptions_element_read_page',
                                $full_human_page_name,
                                $data['GROUP']['GROUP_NAME']),
                            "source" => tl('groupoptions_element_page_source',
                                $full_human_page_name),
                            "history" => tl('groupoptions_element_page_history',
                                $human_page_name),
                        ];
                    }
                    if (!empty($data['PAGE_HAS_RELATIONSHIPS']) ) {
                        $relationship_mode = [
                            "relationships" =>
                                tl('groupoptions_element_page_relationships',
                                $human_page_name),
                        ];
                        $modes = array_merge($modes, $relationship_mode);
                    }
                    if (!empty($data['DISCUSS_THREAD'])) {
                        $modes[htmlentities(B\feedsUrl("thread",
                            $data['DISCUSS_THREAD'], true,
                            "group")) .
                            $token_string] =
                                tl('groupoptions_element_page_discuss',
                                $human_page_name);
                    }
                }
                $modes["pages"] = tl('groupoptions_element_page_list',
                    $data['GROUP']['GROUP_NAME']);
                $modes[htmlentities(
                    B\feedsUrl("group", $data["GROUP"]["GROUP_ID"],
                    true, 'group')) . $token_string] =
                    tl('groupoptions_element_group_feed',
                    $data['GROUP']['GROUP_NAME']);
                $selected_url = "";
                foreach ($modes as $name => $translation) {
                    $append = "";
                    $page_name = ($name == 'pages') ?
                        'pages' : $data['PAGE_NAME'];
                    $arg = '&amp;arg=' . $name ;
                    if (in_array($name, ['history', 'relationships'])) {
                        $page_id = (empty($data['PAGE_ID'])) ? "" :
                            $data['PAGE_ID'];
                        $append .= "&amp;page_id=" . $page_id;
                    }
                    if (!empty($data['SUB_PATH'])) {
                        $append .= "&amp;sf=".urlencode($data['SUB_PATH']);
                    }
                    if (isset($_REQUEST['noredirect'])) {
                        $append .= '&amp;noredirect=true';
                    }
                    if (isset($data['OTHER_BACK_URL'])) {
                        $append .= $data['OTHER_BACK_URL'];
                    }
                    if (substr($name, 0, 4) != "http") {
                        $url = htmlentities(B\wikiUrl(
                            $page_name, true, $data['CONTROLLER'],
                            $data["GROUP"]["GROUP_ID"])) . $token_string .
                            $arg . $append;
                    } else {
                        $url = $name . $arg . $append;
                    }
                    if ($data["MODE"] == $name) {
                        $selected_url = $url;
                    }
                    $options[$url] = $translation;
                }
                $sub_path = $this->view->element("wiki")->renderPath(
                    'page-path', $data,
                    $options, $selected_url, "",
                    "just_groups_and_pages", true);
            } else if (isset($data['JUST_THREAD'])) {
                if (isset($data['WIKI_PAGE_NAME'])) {
                    $wiki_url = htmlentities(B\wikiUrl(
                        $data['WIKI_PAGE_NAME'], true,
                        $data['CONTROLLER'],$data['PAGES'][0]["GROUP_ID"])).
                        $token_string;
                    $group_base_query = $base_query . $token_string;
                    $group_name = $data['PAGES'][0][self::SOURCE_NAME];
                    $paths = [$group_base_query =>
                        tl('groupoptions_element_page_thread',
                        $data['WIKI_PAGE_NAME'], $group_name),
                        $group_base_query . "&amp;f=rss" =>
                        tl('groupoptions_element_page_thread_rss',
                        $data['WIKI_PAGE_NAME'], $group_name),
                        $wiki_url => tl('groupoptions_element_wiki_page',
                        $data['WIKI_PAGE_NAME'], $group_name)
                        ];
                    $groupsfeed_url = htmlentities(B\feedsUrl("group",
                        "", false, $data['CONTROLLER'])).
                        $token_string;
                    $this->view->element("groupfeed")->renderPath($data, $paths,
                         "", $groupsfeed_url,
                         $data['PAGES'][0][self::SOURCE_NAME], "", true);
                } else {
                    $groupfeed_url = htmlentities(B\feedsUrl("group",
                        $data['PAGES'][0]["GROUP_ID"], false,
                        $data['CONTROLLER'])). $token_string;
                    $groupfeed_group_url = htmlentities(B\feedsUrl("group",
                        $data['PAGES'][0]["GROUP_ID"], true, "group")).
                        $token_string;
                    $groupwiki_url = htmlentities(B\wikiUrl("Main", true,
                        $data['CONTROLLER'],$data['PAGES'][0]["GROUP_ID"])).
                        $token_string;
                    $group_base_query = B\feedsUrl("", "", true,
                        $data['CONTROLLER']) . $token_string;
                    $paths = [$base_query => $data['SUBTITLE'],
                        $base_query . "&amp;f=rss" =>
                        tl("groupoptions_element_rss", $data['SUBTITLE']),
                        $groupfeed_url =>
                            $data['PAGES'][0][self::SOURCE_NAME],
                        $groupfeed_group_url . "&amp;f=rss" =>
                            tl("groupoptions_element_rss",
                            $data['PAGES'][0][self::SOURCE_NAME])
                        ];
                    $this->view->element('groupfeed')->renderPath($data, $paths,
                         $groupwiki_url, $group_base_query,
                         $data['PAGES'][0][self::SOURCE_NAME], "", true);
                }
            } else if (isset($data['JUST_GROUP_ID'])){
                $groupfeed_url = htmlentities(B\feedsUrl("group",
                    $data['JUST_GROUP_ID'], false, $data['CONTROLLER'])).
                    $token_string;
                $groupfeed_group_url = htmlentities(B\feedsUrl("group",
                    $data['JUST_GROUP_ID'], true, "group")).
                    $token_string;
                $groupwiki_url = htmlentities(B\wikiUrl("Main", true,
                    $data['CONTROLLER'], $data['JUST_GROUP_ID'])).
                    $token_string;
                $group_base_query = B\feedsUrl("", "", true,
                    $data['CONTROLLER']) . $token_string;
                $paths = [
                    $groupfeed_url => tl("groupoptions_element_groupfeed",
                        $data['SUBTITLE']),
                    $groupfeed_group_url."&amp;f=rss" =>
                        tl("groupoptions_element_rss", $data['SUBTITLE'])];
                $this->view->element('groupfeed')->renderPath($data, $paths,
                    $groupwiki_url, $group_base_query, $data['SUBTITLE']
                    , "", true);
            } else if (isset($data['JUST_USER_ID'])) {
                if (empty($data['PAGES'][0]["USER_NAME"])) {
                    e(tl("groupoptions_element_no_path_info"));
                } else {
                    $viewed_user_name = $data['PAGES'][0]["USER_NAME"];
                    $userfeed_url = htmlentities(B\feedsUrl("user",
                        $data['JUST_USER_ID'], true, $data['CONTROLLER'])).
                        $token_string;
                    $group_base_all = B\feedsUrl("", "", true,
                        $data['CONTROLLER']) . $token_string;
                    $paths = [
                        $userfeed_url => tl("groupoptions_element_userfeed",
                            $viewed_user_name),
                        $userfeed_url."&amp;f=rss" =>
                            tl("groupoptions_element_userrss",
                            $viewed_user_name)];
                    $this->view->element('groupfeed')->renderPath($data, $paths,
                         $group_base_all, $userfeed_url, $viewed_user_name,
                         "user", true);
                }
            } else {
                $paths = [];
                $this->view->element('groupfeed')->renderPath($data, $paths, "",
                    $base_query, tl('groupoptions_element_myfeeds'),
                    "just_group_and_thread", true);
            }
            ?>
        </nav>
        <?php
    }
}
