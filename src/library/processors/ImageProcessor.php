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
namespace seekquarry\yioop\library\processors;

use seekquarry\yioop\configs as C;
use seekquarry\yioop\library as L;

/**
 * Base abstract class common to all processors used to create crawl summary
 * information from images
 *
 * @author Chris Pollett
 */
class ImageProcessor extends PageProcessor
{
    /**
     * Extract summary data from the image provided in $page together the url
     *     in $url where it was downloaded from
     *
     * ImageProcessor class defers a proper implementation of this method to
     *     subclasses
     *
     * @param string $page  the image represented as a character string
     * @param string $url  the url where the image was downloaded from
     * @return array summary information including a thumbnail and a
     *     description (where the description is just the url)
     */
    public function process($page, $url)
    {
        return null;
    }
    /**
     * Given an $image_string determines if possible its width and height
     * then assigns the values into the CrawlConstants:WIDTH,
     *  CrawlConstants:HEIGHT fields of $summary
     *
     * @param arrray &$summary to write the width and height into
     * @param string $image_string  the image represented as a character string
     * @return array summary information including a thumbnail and a
     *     description (where the description is just the url)
     */
    public function addWidthHeightSummary(&$summary, $image_string)
    {
        set_error_handler(null);
        $image_info = @getimagesizefromstring($image_string);
        set_error_handler(C\NS_CONFIGS . "yioop_error_handler");
        if (!empty($image_info[0]) && !empty($image_info[1])) {
            list($summary[self::WIDTH], $summary[self::HEIGHT], ) =
                $image_info;
        }
    }
    /**
     * Given an image try to extract and XMP info from it.
     *
     * @param string $image_string  the image represented as a character string
     * @return array XMP data converted from XML format to an array-like format
     */
    public function getXmpData($image_string)
    {
        $xmp_data = "";
        if (function_exists("simplexml_load_string") &&
            preg_match('/\<x\:xmpmeta.+\<\/x\:xmpmeta\>/s', $image_string,
            $match)) {
            $xml_no_ns = preg_replace("/\<\/\w+\:/", "</", $match[0]);
            $xml_no_ns = preg_replace("/\<\w+\:/", "<", $xml_no_ns);
            $xmp_xml = simplexml_load_string($xml_no_ns);
            $xmp_array = json_decode(json_encode($xmp_xml), true);
            $xmp_data = print_r($xmp_array, true);
        }
        return $xmp_data;
    }
    /**
     * Used to create a thumbnail from an image object
     *
     * @param object $image  image object with image
     *
     */
    public static function createThumb($image)
    {
        $thumb = imagecreatetruecolor(C\THUMB_DIM, C\THUMB_DIM);
        imagesavealpha($thumb, true);
        $trans_colour = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefill($thumb, 0, 0, $trans_colour);
        if (!empty($image)) {
            $size_x = imagesx($image);
            $size_y = imagesy($image);
            set_error_handler(null);
            @imagecopyresampled($thumb,
                $image, 0,0, 0,0, C\THUMB_DIM, C\THUMB_DIM, $size_x, $size_y);
            set_error_handler(C\NS_CONFIGS . "yioop_error_handler");
            imagedestroy($image);
        }
        ob_start();
        imagejpeg($thumb);
        $thumb_string = ob_get_contents();
        ob_end_clean();
        imagedestroy($thumb);
        return $thumb_string;
    }
}
