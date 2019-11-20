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
/**
 * Element used to draw the navigation bar on search pages.
 *
 * @author Chris Pollett
 */
class SearchbarElement extends Element
{
    /**
     * Used to draw the navigation bar on the search page portion
     * of the yioop website
     *
     * @param array $data contains antiCSRF token, as well as data on
     *     used to initialize the search form
     */
    public function render($data)
    {
        $logged_in = isset($data["ADMIN"]) && $data["ADMIN"];
        $query_parts = [];
        if ($logged_in) {
            $query_parts[C\CSRF_TOKEN] = $data[C\CSRF_TOKEN];
        }
        $token_string = ($logged_in && isset($data[C\CSRF_TOKEN])) ?
            C\CSRF_TOKEN . "=".$data[C\CSRF_TOKEN] : "";
        $logo = C\BASE_URL . C\LOGO_MEDIUM;
        if ($_SERVER["MOBILE"]) {
            $logo = C\BASE_URL . C\LOGO_SMALL;
        }
        if (!empty($data['IS_LANDING'])) { ?>
            <div id='nav-bar' class="nav-bar">
            <?php $this->renderSettingsToggle($logged_in); ?>
            </div><?php
            return;
        } ?>
        <div id='nav-bar' class="nav-bar">
            <div class='inner-bar'>
            <?php $this->renderSettingsToggle($logged_in); ?>
            <form id="search-form" method="get" action="<?=C\BASE_URL ?>"
                onsubmit="processSubmit()">
            <a href="<?= C\BASE_URL ?><?php if ($logged_in) {
                e("?".http_build_query($query_parts));
                } ?>"><img src="<?php e($logo); ?>"
                    alt="<?=tl('searchbar_element_title')?>" /></a><?php
            $subsearch_shift = "";
            if (!empty($data['SUBSEARCH'])) {
                $key = array_search($data['SUBSEARCH'],
                    array_column($data["SUBSEARCHES"], 'FOLDER_NAME'));
                if(!empty($key)) {
                    e(" <b id='logo-subsearch' ".
                        " class='logo-subsearch'><a href='" .
                        B\subsearchUrl($data['SUBSEARCH'], $logged_in).
                        $token_string . "'>" .
                        $data["SUBSEARCHES"][$key]['SUBSEARCH_NAME'] .
                        "</a></b>");
                    $subsearch_shift = 'subsearch-shift';
                }
            }
            ?>
            <span class="search-field <?=$subsearch_shift?>">
            <?php if (isset($data["SUBSEARCH"]) && $data["SUBSEARCH"] != "") {
                ?><input type="hidden" name="s" value="<?=
                $data['SUBSEARCH'] ?>" />
            <?php } ?>
            <?php if ($logged_in) { ?>
            <input id="csrf-token" type="hidden" name="<?= C\CSRF_TOKEN ?>"
                value="<?= $data[C\CSRF_TOKEN] ?>" />
            <?php } ?>
            <input id="its-value" type="hidden" name="its" value="<?=
                $data['its'] ?>" />
            <input type="search" <?php if (C\WORD_SUGGEST) { ?>
                autocomplete="off"  onkeyup="onTypeTerm(event, this)"
                <?php } ?>
                title="<?= tl('searchbar_element_input_label') ?>"
                id="query-field" name="q" value="<?php
                if (isset($data['QUERY']) && !isset($data['NO_QUERY'])) {
                    e(urldecode($data['QUERY'])); } ?>"
                placeholder="<?= tl('searchbar_element_input_placeholder') ?>" />
            <button class="button-box" type="submit"><img
                src='<?=C\BASE_URL ?>resources/search-button.png'
                alt='<?= tl('searchbar_element_search') ?>'/></button>
            </span>
            </form>
        </div>
        </div>
        <div id="suggest-dropdown">
            <ul id="suggest-results" class="suggest-list">
            </ul>
        </div>
        <?php
    }
    /**
     * Draws the Hamburger menu icon for additional options on the search
     * portions of the yioop site. The icon has the username in it when the
     * user is logged in
     *
     * @param bool $logged_in whether the user is logged in or not
     */
    public function renderSettingsToggle($logged_in)
    { ?>
        <div class="settings" id="settings-toggle"
            onclick="javascript:toggleOptions()"><?php
        if ($logged_in) {
            $user_name = $_SESSION['USER_NAME'];
            if (mb_strlen($user_name) > 6) {
                $user_name = mb_substr($user_name, 0, 4) . "..";
            }
            ?>
            <div class='top'>[<?=$user_name ?>]
            </div><div class='bottom'>&equiv;</div>
            <?php
        } else {
            e('&equiv;');
        }?></div><?php
    }
}
