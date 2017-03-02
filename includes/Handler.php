<?php

namespace ACFAdvancedSearch;


if (!class_exists('Handler')){

    class Handler
    {
        protected $loader;

        protected $plugin_slug;

        protected $version;

        protected $textdomain;

        public function __construct()
        {

            $this->plugin_slug = 'acf-advanced-search';
            $this->version = '1.1.0';

            $this->loadDependencies();
            $this->defineBackEndHooks();
            $this->defineFrontEndHooks();

        }

        private function loadDependencies()
        {
            require_once 'SearchFilters.php';
            require_once 'SearchResults.php';
            require_once 'HooksLoader.php';
            require_once 'SearchWidget.php';
            $this->loader = new HooksLoader();

        }


        private function defineBackEndHooks()
        {
            $search_widget = new SearchWidget($this->getVersion());
            $this->loader->addAction('widgets_init',$search_widget,'registerACFSearchWidget');


        }

        private function defineFrontEndHooks()
        {

            $search_results = new SearchFilters();
            $this->loader->addFilter('posts_where', $search_results, 'makeSearchWhere');
            $this->loader->addAction('init',$search_results, 'getAvailableMetaKeysForFilters');
            $this->loader->addFilter('posts_join', $search_results, 'makeSearchJoin');
            $this->loader->addFilter('posts_distinct', $search_results, 'makeSearchDistinct');


        }

        public function run()
        {
            $this->loader->run();
        }

        public function getVersion()
        {
            return $this->version;
        }


    }
}
