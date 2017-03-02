<?php

namespace ACFAdvancedSearch;

class HooksLoader
{

    protected $actions;

    protected $filters;

    protected $register_hooks;


    public function __construct() {

        $this->actions = array();
        $this->filters = array();
        $this->register_hooks = array();

    }

    public function addAction( $hook, $component, $callback ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback );
    }

    public function addFilter( $hook, $component, $callback ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback );
    }

    public function registerActivationHook($hook, $component, $callback){
        $this->register_hooks = $this->add( $this->register_hooks, $hook, $component, $callback );
    }


    private function add( $hooks, $hook, $component, $callback ) {

        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback
        );

        return $hooks;

    }

    public function run() {

        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }

        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }

        foreach ( $this->register_hooks as $hook ) {
            register_activation_hook( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }


    }


}