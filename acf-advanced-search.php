<?php
/*
Plugin Name: ACF restricted search and access
Plugin URI:
Description:  This plugin make ACF fields accessible for WP searching with filters. Also, you can create rules for restricted access to ACF custom fields in Frontend and in the search results. Restricted access depends on users roles.
Author: Victor Demianenko
Version: 1.0.5
Author URI:
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('AASsearch')) {

    class AASsearch
    {

        private $post_type;

        public function __construct()
        {
            $this->post_type = 'partners';
            add_filter('posts_where', array($this, 'acfSearchWhere'));
            add_shortcode('displayACFfields', array($this, 'displayACFfields'));
            add_action('init', array($this, 'getAvailableMetaKeysForFilters'));

        }


        public function getIDsOfAllACFGroups()
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

        public function getAvailableMetaKeysForFilters()
        {

            $groups_list = array();
            $available_meta_keys_for_search = array();
            $results = $this->getIDsOfAllACFGroups();

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


        public function displayACFfields()
        {
            if (is_user_logged_in()) {
                $roles = wp_get_current_user()->roles[0];
            } else $roles = null;

            $fields = get_fields();

            if ($fields) {
                foreach ($fields as $field_name => $value) {
                    // get_field_object( $field_name, $post_id, $options )
                    // - $value has already been loaded for us, no point to load it again in the get_field_object function
                    $field = get_field_object($field_name);
                    if ($field) {
                        if ((strpos($field['wrapper']['id'], $roles) !== false) || empty($field['wrapper']['id'])) {
                            echo '<div>';
                            if (!empty($value)) {
                                echo '<p><h3>' . $field['label'] . ':' . '</h3>';
                            }
                            if ($field['type'] == 'checkbox') {
                                foreach ($field['value'] as $value) {
                                    echo $value;
                                    echo '<br/>';
                                }
                            }
                            if ($field['type'] == 'select' && $field['multiple'] == 1) {
                                foreach ($field['value'] as $value) {
                                    echo $value;
                                    echo '<br/>';
                                }
                            } else {
                                echo $value;
                                echo '</p></div>';
                            }
                        }
                    }
                }
            }
        }


        /**
         * Join posts and postmeta tables
         *
         * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
         */
        public function acfSearchJoin($join)
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
        public function acfSearchWhere($where)
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
        public function acfSearchDistinct($where)
        {

            if (is_search()) {
                return "DISTINCT";
            }

            return $where;
        }


        public function get_filters()
        {
            $selected = '';
            $arr = array();
            $results = $this->getIDsOfAllACFGroups();
            foreach ($results as $result) {
                array_push($arr, $result[0]);
            }

            foreach ($arr as $id) {
                $fields = acf_get_fields_by_id($id);
                if ($fields) {
                    foreach ($fields as $field) {
                        if (($field['type'] == 'select') && (in_array($field['name'], $this->getAvailableMetaKeysForFilters()))) {
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


} // ENF if


$aas_obj = new AASsearch();

add_filter('posts_join', array($aas_obj, 'acfSearchJoin'));
add_filter('posts_distinct', array($aas_obj, 'acfSearchDistinct'));


/**
 * ACF Advanced search widget
 *
 * https://codex.wordpress.org/Widgets_API
 */
class WP_ACF_Advanced_Widget_Search extends WP_Widget
{

    /**
     * Sets up a new Search widget instance.
     *
     * @since 2.8.0
     * @access public
     */
    public function __construct()
    {
        $widget_ops = array(
            'classname' => 'ACF_widget_search',
            'description' => __('Advanced search form for ACF fields.'),
            'customize_selective_refresh' => true,
        );
        parent::__construct('ACF_search', _x('ACF Search', 'ACF Search widget'), $widget_ops);
    }

    /**
     * Outputs the content for the current Search widget instance.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $args Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current Search widget instance.
     */
    public function widget($args, $instance)
    {
        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        ?>

        <?php require_once plugin_dir_path(__FILE__) . 'search-form.php'; ?>

        <?php

        echo $args['after_widget'];
    }

    /**
     * Outputs the settings form for the Search widget.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $instance Current settings.
     */
    public function form($instance)
    {
        $instance = wp_parse_args((array)$instance, array('title' => ''));
        $title = $instance['title'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                       name="<?php echo $this->get_field_name('title'); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>"/>
            </label>
        </p>
        <?php
    }

    /**
     * Handles updating settings for the current Search widget instance.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $new_instance = wp_parse_args((array)$new_instance, array('title' => ''));
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }

}

// register WP_ACF_Advanced_Widget_Search

function register_WP_ACF_Advanced_Widget_Search_widget()
{
    register_widget('WP_ACF_Advanced_Widget_Search');
}

add_action('widgets_init', 'register_WP_ACF_Advanced_Widget_Search_widget');





