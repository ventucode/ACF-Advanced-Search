<?php
?>
<form role="search" method="get" id="searchform" action="<?php echo home_url('/') ?>">
    <label class="screen-reader-text" for="s">Search </label>
    <input type="text" placeholder="Search ..." value="<?php echo get_search_query() ?>" name="s" id="s"/>
    <br/>
    <?php
    $aas_obj = new AASsearch();
    $aas_obj->get_filters();
    ?>
    <br/><input type="submit" id="searchsubmit" value="Search"/>
<!--    <input type="hidden" name="lang" value="--><?php //echo(ICL_LANGUAGE_CODE); ?><!--"/>-->
</form>

