<?php

namespace ACFAdvancedSearch;

class SearchFilters
{
    private $post_type;

    public function __construct()
    {
        //TODO Add option in admin panel for selecting post type
        $this->post_type = 'post';
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

    public static function getKeysOfAllACFFields()
    {
        global $wpdb;
        $kes_of_acf_fields = array();

        $query = 'SELECT DISTINCT m.meta_key
            FROM ' . $wpdb->posts . ' p INNER JOIN ' . $wpdb->postmeta . ' m ON p.ID = m.post_id
            WHERE p.post_status = "publish"
            AND p.post_type = "acf"
            AND LEFT (m.meta_key,6) = "field_"
            ORDER BY meta_key';
        $results = $wpdb->get_results($query, ARRAY_N);

        foreach ($results as $result) {
            $kes_of_acf_fields[] = $result[0];
        }

        return $kes_of_acf_fields;
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
            $fields = self::getObjectsOfACFFields($id);
            if ($fields) {
                foreach ($fields as $field) {
                        array_push($available_meta_keys_for_search, $field['name']);
                    }
                }
            }

        return $available_meta_keys_for_search;
    }

    /**
     * @param $groups_key
     * @return array|bool|mixed|void
     */

    public static function getObjectsOfACFFields($groups_key)
    {
        $objects_of_acf_fields = array();

         if (function_exists('get_field_object')) {
            $acf_fields_keys = self::getKeysOfAllACFFields();

            foreach ($acf_fields_keys as $acf_field_key) {
                $objects_of_acf_fields[] = get_field_object($acf_field_key);
            }

        }
        return $objects_of_acf_fields;
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

            foreach ($this->getAvailableMetaKeysForFilters() as $meta_key) {
                foreach ($_GET as $key => $value) {
                    if (strcasecmp($key, $meta_key) == 0 && !empty($value)) {

                        $join .= " LEFT JOIN $wpdb->postmeta as $meta_key ON $wpdb->posts.ID = $meta_key.post_id";

                    } elseif (preg_match('/' . $meta_key . '/', $key)) {
                        $join .= " LEFT JOIN $wpdb->postmeta as $key ON $wpdb->posts.ID = $key.post_id";

                    }
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

            foreach ($this->getAvailableMetaKeysForFilters() as $meta_key) {
                $multi_select_values = array();
                foreach ($_GET as $key => $value) {
                    if (strcasecmp($key, $meta_key) == 0 && !empty($value)) {

                        $where .= " AND $key.meta_key='" . $meta_key . "'";
                        $where .= " AND $key.meta_value LIKE '%" . strip_tags($value) . "%'";

                    } elseif (preg_match('/' . $meta_key . '/', $key)) {

                        $s_where = "( $key.meta_key='" . $meta_key . "' AND $key.meta_value LIKE '%" . strip_tags($value) . "%')";
                        array_push($multi_select_values, $s_where);

                    }


                }
                if (!empty($multi_select_values)) {

                    $where .= " AND (";
                    $where .= implode(" OR ", $multi_select_values);
                    $where .= ")";

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

    /**
     * Display filters in ACF Search widget
     *
     */

    public static function displayFilters()
    {
        $selected = '';
        $arr = array();
        $results = self::getIDsOfAllACFGroups();
        foreach ($results as $result) {
            array_push($arr, $result[0]);
        }

        foreach ($arr as $id) {
            $fields = self::getObjectsOfACFFields($id);
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


                    } elseif ($field['type'] == 'radio') {


                        /* Display Radio Button in Advanced Search Widget */
                        echo '<br/>';
                        echo $field['label'];
                        echo '<br/>';

                        {
                            echo '<div>';

                            foreach ($field['choices'] as $v) {
                                $checked = '';
                                if (isset($_GET[$field['name']])) {
                                    if (!empty($_GET[$field['name']])) {
                                        $checked = ($v == $_GET[$field['name']]) ? 'checked' : '';
                                    }
                                }
                                echo '<label><input type="radio" name="' . $field['name'] . '" ' . $checked . '
                                value="' . $v . '">' . $v . '</label><br/>';

                            }
                            echo '</div>';
                        }
                    }
                }
            }
        }
    }
}