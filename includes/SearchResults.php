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
        if (is_single()) {
            if (is_user_logged_in()) {
                $roles = wp_get_current_user()->roles[0];
            } else $roles = null;

            if ($fields = get_field_objects()) {
                ?>
                <div class='data row'>
                    <?php
                    foreach ($fields as $field) {
                        if ((strpos($field['wrapper']['id'], $roles) !== false) || empty($field['wrapper']['id']) && !empty($field['value'])) : ?>
                            <div class='col-sm-6 col-md-4 col-lg-3'>
                                <div class='term'>
                                    <h3><?php echo $field['label']; ?></h3>
                                </div>
                                <div class='value'>
                                    <?php

                                    if (isset($field['choices'])) {
                                        if (is_array($field['value'])) {
                                            $array = array();
                                            foreach ($field['value'] as $value) {
                                                $array[] = $field['choices'][$value];
                                            }
                                            echo implode('<br/>', $array);
                                        } else {
                                            echo $field['choices'][$field['value']];
                                        }
                                    } else {
                                        echo $field['value'];
                                    }
                                    if (isset($field['append'])) echo ' ' . $field['append'];
                                    ?>
                                </div>
                            </div>
                            <?php
                        endif;
                    }
                    ?>

                </div>
                <?php
            }

        }

    }

}
