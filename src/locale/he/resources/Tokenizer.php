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
 * @author Chris Pollett chris@pollett.org
 * @license https://www.gnu.org/licenses/ GPL3
 * @link https://www.seekquarry.com/
 * @copyright 2009 - 2019
 * @filesource
 */
namespace seekquarry\yioop\locale\he\resources;

/**
 * Hebrew specific tokenization code. Typically, tokenizer.php
 * either contains a stemmer for the language in question or
 * it specifies how many characters in a char gram
 *
 * @author Chris Pollett
 */
class Tokenizer
{
    /**
     * A list of frequently occurring terms for this locale which should
     * be excluded from certain kinds of queries. This is also used
     * for language detection
     * @array
     */
    public static $stop_words = ['כמו', 'אני', 'שלו', 'ש', 'הוא',
         'היה', 'עבור', 'על', 'הם', 'עם', 'הם',
         'להיות', 'ב', 'אחד', 'יש לי', 'זה', 'מ', 'על ידי',
         'חם', 'מילה', 'אבל', 'מה', 'כמה', 'הוא', 'זה',
         'אתה', 'או', 'היה לי', 'עבור', 'של', 'אל',
         'ו', 'זמן', 'ב', 'אנחנו', 'יכול',
         'את', 'אחר', 'היו', 'ש', 'לעשות',
         'שלהם', 'זמן', 'אם', 'יהיה', 'איך',
         'אמר', 'בית', 'כל', 'לספר', 'עושה',
         'סט', 'שלוש', 'רוצה', 'אוויר', 'גם',
         'גם', 'לשחק', 'קטן',
         'סוף', 'לשים', 'בית', 'לקרוא', 'יד', 'נמל', 'גדול',
         'לאיית', 'להוסיף', 'אפילו', 'ארץ',
         'כאן', 'חייב', 'גדול', 'גבוה',
         'כזה', 'מעקב', 'מעשה', 'מדוע',
         'שואל', 'אנשים', 'לשנות', 'הלכתי',
         'אור', 'סוג', 'את', 'צריך',
         'בית', 'תמונה', 'לנסות', 'שלנו',
         'שוב', 'חיה', 'נקודה',
         'אמא', 'עולם',
         'ליד', 'לבנות', 'עצמי', 'כדור הארץ', 'אב'];
    /**
     * How many characters in a char gram for this locale
     * @var int
     */
    public static $char_gram_len = 5;
    /**
     * Removes the stop words from the page (used for Word Cloud generation
     * and language detection)
     *
     * @param mixed $data either a string or an array of string to remove
     *      stop words from
     * @return mixed $data with no stop words
     */
    public static function stopwordsRemover($data)
    {
        static $pattern = "";
        if (empty($pattern)) {
            $pattern = '/\b(' . implode('|', self::$stop_words) . ')\b/u';
        }
        $data = preg_replace($pattern, '', $data);
        return $data;
    }
}
