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
 * @author Xianghong Sun sxh19911230@gmail.com
 * @license https://www.gnu.org/licenses/ GPL3
 * @link https://www.seekquarry.com/
 * @copyright 2009 - 2019
 * @filesource
 */
namespace seekquarry\yioop\library;

use seekquarry\yioop\library\Trie;
use seekquarry\yioop\configs as C;
/**
 * A Stochastic Finite-State Word-Segmenter.
 * This class contains necessary tools to segment terms
 * from sentences.
 *
 * Currently only supports Chinese.
 * Instruction to add a new language:
 * Add a switch case in the constructor.
 * Define the following variable:
 * $non_char_preg
 * Define the following function:
 * isExceptionImpl
 * See the class function 'isException' for more information
 *
 * Chinese example is provided in the constructor
 *
 * @author Xianghong Sun
 */
class StochasticTermSegmenter
{
    /**
     * The language currently being used  e.g. zh_CN, ja
     * @var string
     */
    public $lang;
    /**
     * regular expression to determine if the non of the char in this
     * term is in current language
     * Recommanded expression for:
     * Chinese:  \p{Han}
     * Japanese: \x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}
     * Korean:   \x{3130}-\x{318F}\x{AC00}-\x{D7AF}
     * @var string
     */
    public $non_char_preg;
    /**
     * Default score for any unknown term
     * @var float
     */
    public $unknown_term_score;
    /**
     * A dictionary file that contains the statistic infomation of
     * the terms
     * @var array
     */
    public $dictionary_file;
    /**
     * Construct an instance of this class used for segmenting string with
     * respect to words in a locale using a probabilistic approach to evaluate
     * segmentation possibilities.
     * @param string $lang is a string to indicate the language
     */
    function __construct($lang)
    {
        $this->lang = $lang;
        /* Add different attribute for different languages
         * Currently only Chinese
         */
        switch($lang)
        {
            case "zh_CN":
                $this->non_char_preg = "/^[^\p{Han}]+$/u";
                $this->num_dict =
                   "1234567890○零一二三四五六七八九十百千万亿".
                   "０１２３４５６７８９壹贰叁肆伍陆柒捌玖拾廿卅卌佰仟萬億";
                $this->dot = "\.．";
                $this->num_end = "％%";
                $this->punctuation =
                "\x{3000}-\x{303F}\x{FF00}-\x{FF0F}\x{FF1A}-\x{FF20}" .
                "\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}\x{FFE0}-\x{FFEE}" .
                "\x{21}-\x{2F}\x{21}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}";
                /**
                 * Check if the term passed in is a Cardinal Number
                 */
                $this->isCardinalNumber = function($term) {
                    return preg_match("/^[" . $this->num_dict .
                        $this->dot . "]+[" . $this->num_end .
                        "]?[余餘]?[百千万亿佰仟萬億]?$/u", $term);
                };
                /*
                 * Check if the term passed in is a Ordinal Number
                 */
                $this->isOrdinalNumber = function($term) {
                    return preg_match("/^第[" . $this->num_dict .
                    "]*$/u", $term);
                };
                /*
                 * Check if the term passed in is a date
                 */
                $this->isDate = function($term) {
                    return preg_match("/^[" . $this->num_dict .
                    "]+(年|年代|月|日|时|小时|時|小時|" .
                    "点|点钟|點|點鐘|分|分鐘|秒|秒鐘)$/u",$term);
                };
                /*
                 * Check if the term passed in is an exception term
                 */
                $this->isExceptionImpl = function($term) {
                    return $this->isCardinalNumber($term)
                    || $this->isOrdinalNumber($term)
                    || $this->isDate($term);
                };
                /*
                 * Check if the term passed in is a punctuation
                 */
                $this->isPunctuation = function($term)
                {
                    return preg_match("/^[" . $this->punctuation .
                        "]$/u", $term);
                };
                break;
            case "ja":
                $this->non_char_preg =
                    "/^[^\x{4E00}-\x{9FBF}\x{3040}-\x{309F}".
                    "\x{30A0}-\x{30FF}]+$/u";
                break;
            case "ko":
                $this->non_char_preg = "/^[^\x{3130}-\x{318F}".
                    "\x{AC00}-\x{D7AF}]+$/u";
                break;
            default:
                $this->non_char_preg = "/^[^.]+$/u";
        }
    }
    /**
     * __call  for calling dynamic methods
     * @param string $method method of this class to call
     * @param array $args arguments to pass to method
     * @return mixed result of method calculation
     */
    public function __call($method, $args)
    {
        return call_user_func_array($this->$method, $args);
    }
    /**
     *  __get  for getting dynamic variables
     * @param string $var_name variable to retrieve
     * @return mixed result of retrieval
     */
    public function __get($var_name)
    {
        return $this->$var_name;
    }
    /**
     *  __set  for assigning dynamic variables
     * @param string $var_name variable to assign
     * @param  mixed $value value to assign to it
     */
    public function __set($var_name, $value)
    {
        $this->$var_name = $value;
    }
    /**
     * Check if the term passed in is an exception term
     * Not all valid terms should be indexed.
     * e.g. there are infinite combinations of numbers in the world.
     * isExceptionImpl should be defined in constructor if needed
     * @param $term is a string that to be checked
     * @return true if $term is an exception term, false otherwise
     */
    public function isException($term)
    {
        if (isset($this->isExceptionImpl))
            return $this->isExceptionImpl($term);
        return false;
    }
    /**
     * Check if all the chars in the term is NOT current language
     * @param $term is a string that to be checked
     * @return bool true if all the chars in $term is NOT current language
     *         false otherwise
     */
    public function notCurrentLang($term)
    {
        return preg_match($this->non_char_preg, $term);
    }
    /**
     * Generate a term dictionary file for later segmentation
     * @param mixed $text_files is a string name or an array of files
     *  that to be trained; words in the files need to be segmented by space
     * @return bool true if success
     */
    public function train($text_files)
    {
        $out_file = C\LOCALE_DIR .
            "/{$this->lang}/resources/term_weight.txt.gz";
        echo "saving file to: $out_file\n";
        $dictionary = array();
        $N = 0;
        if (is_string($text_files)) {
            $text_files = [$text_files];
        }
        foreach($text_files as $text_file) {
            if (file_exists($text_file)) {
                $fh = fopen($text_file,"r");
                while(!feof($fh))  {
                    $line = fgets($fh);
                    $words = preg_split("/[\s　]+/u", $line);
                    foreach ($words as $word) {
                        if ($word != "" && !$this->isException($word)
                            && !$this->notCurrentLang($word)) {
                            if (array_key_exists($word,$dictionary)) {
                                $dictionary[$word]++;
                            } else {
                                $dictionary[$word] = 1;
                            }
                        }
                    }
                }
                fclose($fh);
            }
        }
        $trie = new Trie('$');
        foreach ($dictionary as $key => $value) {
            $trie->add($key . " " . $value, false);
            $N++;
        }
        $this->dictionary_file = [];
        $this->dictionary_file["N"] = $N;
        $this->dictionary_file["dic"] = $trie->trie_array;
        $this->unknown_term_score = $this->getScore(1);
        file_put_contents($out_file,
            gzencode(json_encode($this->dictionary_file), 9));
        return true;
    }

    /**
     * This function is used to segment a list of files
     * @param $text_files can be a file name or a list of file names
     *        to be segmented
     * @param bool $return_string return segmented string if true,
     *        print to stdout otherwise
     *        user can use > filename to output it to a file
     * @return string segmented words with space or true/false;
     */
    public function segmentFiles($text_files, $return_string = false)
    {
        if ($return_string) {
            $result = "";
        }
        if (is_string($text_files)) {
            $text_files = [$text_files];
        }
        foreach($text_files as $text_file) {
            if (file_exists($text_file)) {
                $fh = fopen($text_file, "r");
                while(! feof($fh))  {
                    $line = fgets($fh);
                    if (mb_strlen($line)) {
                        $t = $this->segmentSentence($line);
                        if ($return_string) {
                            $result .= join( " ", $t) ."\n" ;
                        } else {
                            echo join(" ", $t) . "\n";
                        }
                    }
                }
                fclose($fh);
            }
        }
        if ($return_string) {
            return $result;
        }
        return true;
    }
    /**
     * Segment texts. Words are seperated by space
     * @param string $text  to be segmented
     * @param bool $return_string return segmented string if true,
     *        print otherwise
     * @return string segmented words with space or true/false;
     */
    public function segmentText($text, $return_string = false)
    {
        if ($return_string) {
            $result = "";
        }
        $sentences = explode("\n", $text);
        foreach ($sentences as $line) {
            if (mb_strlen($line)) {
                $t = $this->segmentSentence($line);
                if ($return_string) {
                    $result .= join( " ", $t) . "\n";
                } else {
                    echo join( " ", $t) . "\n";
                }
            }
        }
        if ($return_string) {
            return $result;
        }
        return true;
    }
    /**
     * Segment a sentence into arrays of words.
     * Need NOT contain any new line characters.
     * @param string $sentence is a string without newline to be segmented
     * @return array of segmented words
     */
    public function segmentSentence($sentence)
    {
        if (!$this->dictionary_file) {
            $dic_file = C\LOCALE_DIR .
                "/{$this->lang}/resources/term_weight.txt.gz";
            if (!file_exists($dic_file)) {
                echo "$dic_file does not exist!";
                return null;
            }
            $this->dictionary_file =
                json_decode(gzdecode(file_get_contents($dic_file)),true);
            $this->unknown_term_score = $this->getScore(1);
        }
        preg_match_all('/./u', trim($sentence), $matches);
        $characters = $matches[0];
        if (!count($characters)) {
            return [];
        }
        $score = [];
        $path = [];
        //init base
        $score[-1] = 0;
        for($index = 0; $index < count($characters); $index++) {
            //if not current language
            if ($this->notCurrentLang($characters[$index]) ) {
                $current_char = $characters[$index];
                for($j = $index + 1; $j < count($characters); $j++) {
                    if ($this->notCurrentLang($current_char.$characters[$j])) {
                        $current_char .= $characters[$j];
                    } else {
                        break;
                    }
                }
                if (!isset($score[$j - 1]) ||  $score[$j - 1] >
                    $score[$index - 1] + $this->unknown_term_score) {
                    $score[$j - 1] = $score[$index - 1] +
                        $this->unknown_term_score;
                    $path[$j - 1] = $index - 1;
                }
            }
            //if date or number
            if ($this->isException($characters[$index]) ) {
                $current_char = $characters[$index];
                for($j = $index+1; $j<count($characters); $j++) {
                    if (!$this->isException(
                        $current_char . $characters[$j])) {
                        break;
                    }
                    $current_char .= $characters[$j];
                }
                if (!isset($score[$j - 1]) ||
                    $score[$j - 1] > $score[$index - 1] +
                        $this->unknown_term_score) {
                    $score[$j - 1] = $score[$index - 1] +
                        $this->unknown_term_score;
                    $path[$j - 1] = $index - 1;
                }
            }
            //if is punctuation. Give slitely better score than unknown words
            if ($this->isPunctuation($characters[$index])) {
                if (!isset($score[$index]) ||
                    $score[$index]>$score[$index - 1]
                    + $this->unknown_term_score / 1.1) {
                    $score[$index] =
                        $score[$index - 1] + $this->unknown_term_score / 1.1;
                    $path[$index] = $index - 1;
                }
            }
            /* All case (Even not in current lang because dictionary may
                contains those terms
                check the first char, give score even nothing matches
             */
            if (!isset($score[$index]) ||
                $score[$index-1] + $this->unknown_term_score < $score[$index]) {
                $score[$index] = $score[$index-1] +
                    $this->unknown_term_score;
                $path[$index] = $index - 1;
            }
            $subdic = $this->dictionary_file["dic"];
            for ($j = $index; $j < count($characters); $j++) {
                if (!isset($subdic[$characters[$j]])) {
                    break;
                }
                $subdic = $subdic[$characters[$j]];
                if (isset($subdic['$']) && (!isset($score[$j]) ||
                    $score[$index - 1] + $subdic['$'] < $score[$j])) {
                    $score[$j] = $score[$index - 1] +
                        $this->getScore($subdic['$']);
                    $path[$j] = $index - 1;
                }
            }
        }
        //trace path
        $t = max(array_keys($path));
        $tmp = [];
        while($t != -1) {
            $tmp[] = $t;
            $t = $path[$t];
        }
        $result = [];
        $t = 0;
        foreach(array_reverse($tmp) as $nextnode) {
            $result_word = "";
            while($t <= $nextnode) {
              $result_word .= $characters[$t];
              $t++;
            }
            $result[] = $result_word;
        }
        return $result;
    }
    /**
     * This is the function to calculate scores for each word
     * @param int $frequency is an integer tells the frequency of a word
     * @return float the score of the term.
     */
    private function getScore($frequency)
    {
      return -log($frequency / $this->dictionary_file["N"]);
    }
}
