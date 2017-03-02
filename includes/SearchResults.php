<?php

namespace ACFAdvancedSearch;

class SearchResults
{

    public function __construct()
    {
        add_shortcode('displayACFfields', array($this, 'displayACFfields'));
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

}
