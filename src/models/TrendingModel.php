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
 * @author Timothy Chow
 * @license https://www.gnu.org/licenses/ GPL3
 * @link https://www.seekquarry.com/
 * @copyright 2009 - 2019
 * @filesource
 */
namespace seekquarry\yioop\models;

use seekquarry\yioop as B;
use seekquarry\yioop\configs as C;
use seekquarry\yioop\library as L;
use seekquarry\yioop\library\MediaConstants;
use seekquarry\yioop\library\VersionManager;
use seekquarry\yioop\library\WikiParser;
use seekquarry\yioop\library\processors\ImageProcessor;
use seekquarry\yioop\models\ImpressionModel;

/**
 * This is class is used to handle
 * db results related to Group Administration. Groups are collections of
 * users who might access a common blog/news feed and set of pages. This
 * method also controls adding and deleting entries to a group feed and
 * does limited access control checks of these operations.
 *
 * @author Timothy Chow
 */
class TrendingModel extends Model implements MediaConstants
{
    /**
     * Computes an array of the top NUM_TRENDING terms from feed items
     * for the update periods listed as an array. If no periods are
     * provided, then the period hour, day, week are used.
     *
     * @param string $locale_tag language to get top terms for
     * @param array $update_periods as array of update periods.
     *      Items in this array she be from intervals pre-defined in
     *      Config.php such as ONE_HOUR, ONE_DAY, or ONE_WEEK.
     * @param string $category used to compute the trending terms with
     *      respect to
     * @return array consists of one array/update period, each of these
     *      arrays consist of a rank => [term, occurrences] for the
     *      top NUM_TRENDING results for that period.
     */
    public function topTermsForUpdatePeriods($locale_tag, $update_periods = [],
        $category = "")
    {
        $db = $this->db;
        if (empty($update_periods)) {
            $update_periods = [C\ONE_HOUR, C\ONE_DAY, C\ONE_WEEK];
        }
        $sql = "SELECT TERM, OCCURRENCES FROM TRENDING_TERM ".
            "WHERE LANGUAGE= ? AND UPDATE_PERIOD = ? AND CATEGORY =? AND ".
            "TIMESTAMP = (SELECT MAX(TIMESTAMP) " .
            "FROM TRENDING_TERM WHERE LANGUAGE= ? AND UPDATE_PERIOD = ?) " .
            "ORDER BY OCCURRENCES DESC ".
            $db->limitOffset(C\NUM_TRENDING);
        $top_results = [];
        foreach($update_periods as $period) {
            $result = $db->execute($sql, [$locale_tag, $period, $category,
                $locale_tag, $period]);
            $term_occurrences = [];
            if($result) {
                while($term_occurrence = $db->fetchArray($result)) {
                    $term_occurrences[] = $term_occurrence;
                }
            }
            $top_results[$period] = $term_occurrences;
        }
        return $top_results;
    }
    /**
     * Returns a list of $num_term many random tending terms from the
     * sets of trending terms across all tracked time periods with respect
     * to category supplied.
     *
     * @param string $locale_tag language to get random trending terms for
     * @return array terms which are trending
     */
    public function randomTrends($locale_tag, $category = 'news', $num_terms =
        C\NUM_RESULTS_PER_PAGE)
    {
        $trend_dir = C\WORK_DIRECTORY . "/cache/trends";
        $random_trends_file = "$trend_dir/random_"
            .  "{$category}_{$locale_tag}.txt";
        if (file_exists($random_trends_file) &&
            filemtime($random_trends_file) + (5 * C\ONE_MINUTE) >
                time()) {
            return unserialize(file_get_contents($random_trends_file));
        }
        $trend_data = $this->topTermsForUpdatePeriods($locale_tag, [],
            $category);
        $trending = [];
        foreach ($trend_data as $period => $trend_data) {
            $trending = array_merge($trending, $trend_data);
        }
        usort($trending, function($a, $b) {
            if ($a['OCCURRENCES'] == $b['OCCURRENCES']) {
                return 0;
            }
            return ($a['OCCURRENCES'] > $b['OCCURRENCES']) ? -1 : 1;
        });
        $total = array_sum(array_column($trending, "OCCURRENCES"));
        if (count($trending) > $num_terms && $total >= 1) {
            $trending_terms = [];
            while (count($trending_terms) < $num_terms) {
                $random_pick = mt_rand(0, intval($total) * 1000)/1000;
                $prob = 0;
                foreach ($trending as $trend) {
                    $next_prob = $prob + $trend['OCCURRENCES'];
                    if ($next_prob >= $random_pick &&
                        empty($trending_terms[$trend['TERM']])) {
                        $trending_terms[$trend['TERM']] = $trend['TERM'];
                        break;
                    }
                    $prob = $next_prob;
                }
            }
            $trending_terms = array_values($trending_terms);
        } else {
            $trending_terms = array_column($trending, "TERM");
        }
        if (!file_exists($trend_dir)) {
            mkdir($trend_dir);
            chmod($trend_dir, 0777);
        }
        file_put_contents($random_trends_file, serialize($trending_terms));
        set_error_handler(null);
        @chmod($random_trends_file, 0777);
        set_error_handler(C\NS_CONFIGS . "yioop_error_handler");
        return $trending_terms;
    }
    /**
     * Return trending scores for a term with respect to a locale
     * over the appropriate sub-period for the last period. For example,
     * Donald Trump in en-US locale, for the last day. Here the subinterval
     * would be hourly.
     *
     * @param string $term to find time => score data for
     * @param int $period time period to get subinterval data for
     * @param string $locale_tag which locale to get trending data for
     * @param bool $raw whether to keep as linux timestamps or to format into
     *      appropriate date units for subinterval
     * @return array associative array of time => scores
     */
    public function termScoresForPeriod($term, $period, $locale_tag,
        $raw = false)
    {
        $db = $this->db;
        $subintervals = [ C\ONE_HOUR => C\ONE_MINUTE,
            C\ONE_DAY => C\ONE_HOUR,  C\ONE_WEEK => C\ONE_DAY];
        $dates = [ C\ONE_HOUR => 'i', C\ONE_DAY => 'H', C\ONE_WEEK => 'D'];
        $sub_period = (empty($subintervals[$period])) ? C\ONE_HOUR :
            $subintervals[$period];
        $num_results = (empty($subintervals[$period])) ?
            C\ONE_HOUR/C\ONE_MINUTE : ceil($period / $sub_period);
        $date_format = (empty($dates[$period])) ? 'r' : $dates[$period];
        $sql = "SELECT OCCURRENCES, TIMESTAMP FROM TRENDING_TERM ".
            "WHERE TERM = ? AND UPDATE_PERIOD = ? AND LANGUAGE= ? " .
            "ORDER BY TIMESTAMP DESC ".
            $db->limitOffset($num_results);
        $result = $db->execute($sql, [$term, $sub_period, $locale_tag]);
        $pre_occurrences = [];
        if($result) {
            $i = 0;
            while($row = $db->fetchArray($result)) {
                $pre_occurrences[$row['TIMESTAMP']] = $row['OCCURRENCES'];
            }
        }
        ksort($pre_occurrences);
        if ($raw) {
            return $pre_occurrences;
        }
        $timestamp_occurrences = [];
        foreach ($pre_occurrences as $timestamp => $occurrences) {
            $date = date($date_format, $timestamp);
            $timestamp_occurrences[$date] = $occurrences;
        }
        return $timestamp_occurrences;
    }
}
