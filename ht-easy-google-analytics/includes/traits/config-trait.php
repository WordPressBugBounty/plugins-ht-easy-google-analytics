<?php

namespace Ht_Easy_Ga4\Traits;

trait Config_Trait{
	protected $config; // Default config

	protected function set_config( $config = [] ){
        if( !empty($config) ){
            $this->config = $config;
        }

        // Check if has value in wp_options
        if( empty($this->config) ){
            $this->config = get_option('ht_easy_ga4_options', array());
        }

        return $this->config;
	}

    /**
     * Get a configuration value
     * 
     * @param string $option_name The option name
     * @param mixed $default The default value
     * @return mixed The configuration value
     */
	public function config($option_name, $default = null){
		return isset($this->config[$option_name]) ? $this->config[$option_name] : $default;
	}
}