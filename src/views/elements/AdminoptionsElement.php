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

/**
 * Element responsible for drawing the options portion of an admin
 * page. This allows the user to signout or select from among allowed admin
 * activities
 *
 * @author Chris Pollett
 */
class AdminoptionsElement extends Element
{
    /**
     * Method responsible for drawing the options portion of an admin
     * page. This allows the user to signout or select from among allowed admin
     * activities
     *
     * @param array $data has info draw acitivity links on page
     */
    public function render($data)
    {
        $logged_in = !empty($data["ADMIN"]);
        $token_string = ($logged_in) ? C\CSRF_TOKEN . "=" . $data[C\CSRF_TOKEN]
            : "";
        $logo = C\LOGO_MEDIUM;
        if ($_SERVER["MOBILE"]) {
            $logo = C\LOGO_SMALL;
        }
        ?>
        <div id='more-options-background'
            onclick='javascript:toggleOptions()'>
        </div>
        <nav id="more-options" class="more-options">
            <?php
            $first = true; ?>
            <h2><a href="<?=C\BASE_URL ?><?php
                if ($logged_in) {
                    e("?$token_string");
                }
                ?>"><img src="<?= C\BASE_URL . $logo ?>" alt="<?=
                $this->view->logo_alt_text ?>" /></a></h2><?php
            foreach ($data['COMPONENT_ACTIVITIES'] as
                $component_name => $activities) {
                $count = count($activities);
                if (!empty($activities[0]['METHOD_NAME']) &&
                    $activities[0]['METHOD_NAME'] == 'manageAccount') {
                    $component_name = tl('adminoptions_element_welcome_user',
                        $_SESSION['USER_NAME']);
                }
                ?>
                <h2 class="option-heading"><?=$component_name ?><?php
                ?></h2>
                <ul class='square-list'><?php
                for ($i = 0 ; $i < $count; $i++) {
                    $method_name = $activities[$i]['METHOD_NAME'];
                    $controller =
                        (in_array($method_name, ['groupFeeds'])) ?
                        'group' : 'admin';
                    e("<li><a href='"
                        . B\controllerUrl($controller, true)
                        . C\CSRF_TOKEN . "=" . $data[C\CSRF_TOKEN]
                        . "&amp;a="
                        . $activities[$i]['METHOD_NAME']."'>"
                        . $activities[$i]['ACTIVITY_NAME'] .
                        "</a></li>");
                }
                if ($first) {
                    $first = false; ?>
                    <li><b><a href="<?=C\BASE_URL ?>?a=signout"><?=
                        tl('adminoptions_element_signout') ?></a></b></li><?php
                }?>
                </ul>
                <?php
            }
            ?>
        </nav>
        <?php
    }
}
