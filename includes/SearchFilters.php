<?php

namespace ACFAdvancedSearch;
/**
 * Created by PhpStorm.
 * User: AdminLPi
 * Date: 01.03.2017
 * Time: 17:00
 */
class SearchFilters
{
    private $post_type;

    public function __construct()
    {
        $this->post_type = 'partners';
    }

    public static function getIDsOfAllACFGroups()
    {
        global $wpdb;

        $query = 'SELECT DISTINCT ID
            FROM ' . $wpdb->posts . '
            WHERE post_type = "acf-field-group"
            AND post_status = "publish"
            ORDER BY ID';
        $results = $wpdb->get_results($query, ARRAY_N);

        return $results;
    }

    public static function getAvailableMetaKeysForFilters()
    {

        $groups_list = array();
        $available_meta_keys_for_search = array();
        $results = self::getIDsOfAllACFGroups();

        if (is_user_logged_in()) {
            $roles = wp_get_current_user()->roles[0];
        } else $roles = null;


        foreach ($results as $result) {
            if (!in_array($result, $groups_list, true)) {
                array_push($groups_list, $result[0]);
            }
        }


        foreach ($groups_list as $id) {
            $fields = acf_get_fields_by_id($id);
            foreach ($fields as $field) {
                if ((strpos($field['wrapper']['id'], $roles) !== false) || empty($field['wrapper']['id'])) {
                    array_push($available_meta_keys_for_search, $field['name']);
                }
            }
        }
        return $available_meta_keys_for_search;
    }

    /**
     * Join posts and postmeta tables
     *
     * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
     */
    public function makeSearchJoin($join)
    {
        global $wpdb;

        if (is_search()) {

            $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';

            foreach ($this->getAvailableMetaKeysForFilters() as $values) {
                if (isset($_GET[$values]) && !empty($_GET[$values])) {
                    $join .= " LEFT JOIN $wpdb->postmeta as $values ON $wpdb->posts.ID = $values.post_id";

                }
            }

        }

        return $join;
    }


    /**
     * Modify the search query with posts_where
     *
     * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
     */
    public function makeSearchWhere($where)
    {
        global $wpdb;

        if (is_search()) {
            $where = preg_replace(
                "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where);

            $where .= " AND ($wpdb->posts.post_type = '" . $this->post_type . "') ";

            if (!is_user_logged_in()) {
                $where .= 'NOT IN ( 
                ( ' . $wpdb->postmeta . '.meta_key = "title" ) 
                OR ( ' . $wpdb->postmeta . '.meta_key = "country" )
                ) ';
            }

            //Filters
            foreach ($this->getAvailableMetaKeysForFilters() as $values) {
                if (isset($_GET[$values]) && !empty($_GET[$values])) {
                    $where .= " AND $values.meta_key='" . $values . "'";
                    $where .= " AND $values.meta_value LIKE '%" . strip_tags($_GET[$values]) . "%'";
                }
            }
        }
        return $where;
    }


    /**
     * Prevent duplicates
     *
     * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
     */
    public function makeSearchDistinct($where)
    {

        if (is_search()) {
            return "DISTINCT";
        }

        return $where;
    }

    public static function displayFilters()
    {
        $selected = '';
        $arr = array();
        $results = self::getIDsOfAllACFGroups();
        foreach ($results as $result) {
            array_push($arr, $result[0]);
        }

        foreach ($arr as $id) {
            $fields = acf_get_fields_by_id($id);
            if ($fields) {
                foreach ($fields as $field) {
                    if (($field['type'] == 'select') && (in_array($field['name'], self::getAvailableMetaKeysForFilters()))) {
                        echo '<br/>';
                        echo $field['label'];
                        echo '<br/>';
                        echo '<select name="' . $field['name'] . '">';
                        echo '<option value="">' . '--' . __('Select', 'search') . '--' . '</option>';

                        if (array_keys($field['choices'])) {
                            foreach ($field['choices'] as $v) {
                                if (isset($_GET[$field['name']])) {
                                    if (!empty($_GET[$field['name']])) {
                                        $selected = (array_search($v, $field['choices']) == $_GET[$field['name']]) ? 'selected' : '';
                                    } else {
                                        $selected = '';
                                    }
                                }
                                echo '<option value="' . array_search($v, $field['choices']) . '"' . $selected . '>' . $v . '</option>';
                            }
                        } else {
                            foreach ($field['choices'] as $v) {
                                if (isset($_GET[$field['name']])) {
                                    if (!empty($_GET[$field['name']])) {
                                        $selected = ($v == $_GET[$field['name']]) ? 'selected' : '';
                                    } else {
                                        $selected = '';
                                    }
                                }
                                echo '<option value="' . $v . '"' . $selected . '>' . $v . '</option>';
                            }

                        }
                        echo '</select><br/>';


                    } elseif ($field['type'] == 'checkbox') {


                        /* Display Checkboxes in Search Widget */

                        /*
                        {
                            echo '<div>';
//                                echo '<form enctype="application/json">';

                            $counter = 1;
                            foreach ($field['choices'] as $k => $v) {
                                $checked = '';
                                if (isset($_GET[$field['name'] . $counter]) && !empty($_GET[$field['name'] . $counter])) {
                                    $checked = 'checked';
                                }
                                echo '<label><input type="checkbox" name="' . $field['name'] . $counter . '" ' . $checked . '
                                value="' . $v . '">' . $v . '</label><br/>';
                                $counter++;
                            }
                            echo '</div>';
//                                echo '</form>';
                        }
                        */
                    }
                }
            }
        }
    }
}