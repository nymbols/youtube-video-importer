<?php

if( !class_exists('YVII_Plugin_Upgrade') ){
	class YVII_Plugin_Upgrade{
		
		private $frequency = 24; // in hours
		private $args = array();
		
		/**
		 * Constructor
		 * @param array $args - see $defaults
		 */
		public function __construct( $args = array() ){
			
			$defaults = array(
				'plugin'				=> null, // plugin base name
				'code' 					=> null, // purchase code or any other code needed
				'product'				=> null,
				'remote_url' 			=> false, // remote repo location ( http://domain.com/updates.php )
				'changelog_url'			=> false, // remote plugin information
				'current_version' 		=> false, // version of installed plugin
				'extra_request_vars'	=> array(), // other variables set as VAR_NAME => 'VAR VALUE'
			);	
			$this->args = wp_parse_args($args, $defaults);
			
			if( !$this->args['plugin'] || !$this->args['remote_url'] || !$this->args['code'] || !$this->args['product'] ){
				return;
			}		
			$this->args['slug'] = plugin_basename( $this->args['plugin'] );
			$this->frequency *= 3600; // transform frequency in seconds
			
			// override plugins api to display our changelog		
			add_filter( 'plugins_api', array($this, 'plugins_filter'), 10, 3);
			
			if( !$this->should_check() ){
				return;
			}			
			// process requests
			add_filter( 'http_request_args', array($this, 'request_vars_filter'), 5, 2 );
			
			$this->check_update();		
		}
		
		/**
		 * Override details request for our plugin
		 * @param bool $o
		 * @param string $action
		 * @param object $args
		 */
		public function plugins_filter($o, $action, $args){
			if( 'plugin_information' == $action && isset( $args->slug ) && $this->args['slug'] == $args->slug ){
				add_filter('plugins_api_result', array($this, 'plugin_info'), 10, 3);
				$res = array(
					'action' => $action,
					'args' => $args
				);
				return (object)$res;			
			}		
			return $o;
		}
		
		/**
		 * Display page information
		 * @param unknown_type $res
		 * @param unknown_type $action
		 * @param unknown_type $args
		 */
		public function plugin_info( $res, $action, $args ){
			$options['body'] = $this->request_vars();
			$request = wp_remote_post( $this->args['changelog_url'], $options );
			
			$values = array(
				'name' 		=> '',
				'slug' 		=> '',
				'version' 	=> '',
				'author' 	=> '',
				'requires' 	=> '', // minimum version required
				'tested' 	=> '',// compatible up to
				'sections' => array(
					'description' 	=> '',
					'installation' 	=> '',
					'changelog' 	=> ''
				),
				'download_link' => '',
				'tags' 			=> ''
			);
			
			if( 200 == $request['response']['code'] ){
				$data = maybe_unserialize( wp_remote_retrieve_body( $request ) );
				// will return error in case of something going wrong
				if( isset( $data['error'] ) ){
					$values['sections']['description'] = $data['error'];
					$values['sections']['installation'] = $data['error'];
					$values['sections']['changelog'] = $data['error'];
					return (object)$values;
				}
				
				foreach ( $values as $key => $value ){
					if( is_array( $value ) ){
						foreach( $value as $k=>$v ){
							if( isset($data[$key][$k]) ){
								$values[$key][$k] = $data[$key][$k];
							}	
						}
						continue;
					}
					if( isset( $data[$key] ) ){
						$values[$key] = $data[$key];
					}
				}		
			}
			
			return (object)$values;
		}
		
		/**
		 * Request filtering
		 * @param array $r
		 * @param string $url
		 */
		public function request_vars_filter( $r, $url ){
			if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) ){
				return $r;
			}
			
			$plugins = unserialize( $r['body']['plugins'] );	
			unset( $plugins->plugins[ $this->args['slug'] ] );
			unset( $plugins->active[ array_search( $this->args['slug'], $plugins->active ) ] );
			
			$r['body']['plugins'] = serialize( $plugins );		
			return $r;
		}
		
		/**
		 * Check for updates
		 */
		public function check_update(){
			if ( defined( 'WP_INSTALLING' ) ) {
				return false;
			}
			
			$options['body'] = $this->request_vars();
			$response = wp_remote_post( $this->args['remote_url'], $options );
			
			if( !is_wp_error( $response ) && 200 == $response['response']['code'] ){
				$data = maybe_unserialize( $response['body'] );
				
				// will return error in case of something going wrong
				if( isset( $data['error'] ) ){
					return $data['error'];
				}
				
				if( !isset( $data['version'] ) ){
					return;
				}
				
				if( version_compare( $this->args['current_version'] ,  $data['version'], '<' )  ){
					
					$plugin_transient = get_site_transient( 'update_plugins' );
					$plugin_details = array(
						'slug' 			=> $this->args['slug'],
						'new_version' 	=> $data['version'],
						'url' 			=> '',
						'package' 		=> $data['source_files']
					);
					
					$plugin_transient->response[ $this->args['slug'] ] = (object)$plugin_details;
					set_site_transient('update_plugins', $plugin_transient);
				}			
			}
		}
		
		/**
		 * Request vars
		 */
		private function request_vars(){
			$request_vars 			= (array)$this->args['extra_request_vars'];
			$request_vars['code'] 	= $this->args['code'];
			$request_vars['product']= $this->args['product'];
			$request_vars['plugin'] = $this->args['plugin'];
			$request_vars['slug']	= $this->args['slug'];
			$request_vars['version']= $this->args['current_version'];
			return $request_vars;
		}
		
		/**
		 * Check the scheduled interval
		 */
		private function should_check(){
			$transient = '__uc_'.$this->args['slug'];
			$trans = get_transient($transient);
			
			if( (24*3600) != $this->frequency ){
				$this->frequency = 24*3600;
			}
			
			if( !$trans ){
				set_transient($transient, true, $this->frequency);
				return true;
			}	
			return false;	
		}
		
	}
}