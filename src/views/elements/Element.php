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

use seekquarry\yioop\configs as C;

/** For tl, getLocaleTag and Yioop constants */
require_once __DIR__.'/../../library/LocaleFunctions.php';

/**
 * Translate the supplied arguments into the current locale.
 *
 * This function is a convenience copy of the same function
 * @see seekquarry\yioop\library\tl() to this subnamespace
 *
 * @param string string_identifier  identifier to be translated
 * @param mixed additional_args  used for interpolation in translated string
 * @return string  translated string
 */
function tl()
{
    return call_user_func_array(C\NS_LIB . "tl", func_get_args());
}
/**
 * shorthand for echo
 *
 * @param string $text string to send to the current output
 */
function e($text)
{
    echo $text;
}
/**
 * Base Element Class.
 * Elements are classes are used to render portions of
 * a web page which might be common to several views
 * like a view there is supposed to minimal php code
 * in an element
 *
 * @author Chris Pollett
 */
abstract class Element
{
    /**
     * The View on which this Element is drawn
     * @var object
     */
    public $view;
    /**
     * constructor stores a reference to the view this element will reside on
     *
     * @param object $view   object this element will reside on
     */
    public function __construct($view = null)
    {
        $this->view = $view;
    }
    /**
     * This method is responsible for actually drawing the view.
     * It should be implemented in subclasses.
     *
     * @param $data - contains all external data from the controller
     * that should be used in drawing the view
     */
    public abstract function render($data);
}

