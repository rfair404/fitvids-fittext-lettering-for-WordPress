<?php

/*
* Plugin Name: FitVids, FitText and Lettering (Oh My!)
* Description: Adds FitVids.js, FitText.js and Lettering.js to (almost) any WordPress site.
* Author: Russell Fair
* Version: 1.0
*/

/*set up our constants*/
define('JS3N1_VER', '10');
define('JS3N1_DIR', WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));
define('JS3N1_URL', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));
define('JS3N1_LIB_DIR', JS3N1_DIR.'lib');
define('JS3N1_JS_URL', JS3N1_URL.'lib/js');

// translation support
load_plugin_textdomain( 'js3n1', false, JS3N1_DIR. '/languages/' );

register_activation_hook( __FILE__, 'js3n1_activate' );

function js3n1_activate() {
	if ( !get_option('js3n1_options' ) ) {

		$defaults = JS3N1_Start::_get_default_settings();
		update_option( ' js3n1_options' , $defaults );//sets everything to the defaults on activation
	
	}
}

class JS3N1_Start {

	/**
	 * The JS3N1 class, does everything
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function JS3N1_Start()
	{
		$this->__construct();
	} // end JS3N1_Start
	
	/**
	 * The consctuctor, hooks into WordPress
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function __construct()
	{
		//register the settings and the setting fields
		add_action( 'admin_init', array( &$this, '_admin_init' ) );
		//add the settings page to the admin menu system
		add_action( 'admin_menu', array( &$this, '_admin_menu' ) );
		//register the three js files
		add_action( 'init', array( &$this, '_register_js' ) );
		//adds .fitvids to the post class so that we can use it if needed
		add_filter( 'post_class', array( &$this, '_fitvids_post_class' ) );
		//filters the final jquery function so that the output can be controlled (and added to)
		add_filter( 'js3n1_js', array( &$this, '_final_js_filter' ), 5 );
		//enqueues the required scripts (conditionally)
        add_action( 'wp_print_scripts', array( &$this, '_enqueue_js' ) );
        //executes the jquery to spply the required scripts to the DOM elements as wet by the user
		add_action( 'wp_footer', array( &$this, '_do_user_js' ) );

	} // end __construct
	
	/**
	 * The admin_init function registers the js3n1_options setting
	 * it also and registeres the additional setting groups and fields
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _admin_init()
	{
		register_setting('js3n1_options', 'js3n1_options', array(&$this, '_validate_options') );  
		 
		add_settings_section(  
		        'js3n1_main',         // ID 
		        'JS 3-in-1 Main Options', // Title 
		        array(&$this, '_main_options_display'), // Callback 
		        'js3n1' // Where  
		);

		add_settings_field(  
		    'js3n1_simple_options',		// ID 
		    __('Which components do you wish to use', 'js3n1'),	// label 
		    array( &$this, '_options_simple_callback'),   // callback
		    'js3n1', 		// The page
		    'js3n1_main',   // Section 
		    array(   		// Args.  
		        'Activate this setting to display the header.'  
		    )  
		);

		add_settings_field(  
		    'js3n1_fitvids_options',		// ID 
		    __('Apply FitVids on the following', 'js3n1'),		// label 
		    array( &$this, '_fitvids_options_callback'),   // callback
		    'js3n1', 		// The page
		    'js3n1_main',   // Section
		    array(   		// Args
		        'Configure FitVids.js.'  
		    )  
		);

		add_settings_field(  
		    'js3n1_fittext_options',		// ID  
		    __('Apply FitText on the following', 'js3n1'),		// label
		    array( &$this, '_fittext_options_callback'),   // callback 
		    'js3n1', 		// The page 
		    'js3n1_main',   // Section  
		    array(   		// Args
		        'Configure FitText.js'  
		    )  
		);

		add_settings_field(  
		    'js3n1_lettering_options',		// ID 
		    __('Apply Lettering on the following', 'js3n1'),		// label 
		    array( &$this, '_lettering_options_callback'),   // callback
		    'js3n1', 		// The page 
		    'js3n1_main',   // Section
		    array(   		// Args
		        'Configure Lettering.js'  
		    )  
		);


	} // end JS3N1_admin_init
	
	/**
	 * The admin_menu function creates the plugin setting page and puts the link in the admin navigation
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _admin_menu()
	{
			add_submenu_page(  'options-general.php',
        		'FitVids, FitText and Lettering',           // The page title 
        		'FitVids, FitText and Lettering (Oh My)',           // Menu Item Text 
        		'manage_options',            // minimum role required  
        		'js3n1_plugin_options',   // slug 
        		array(&$this, '_js3n1_plugin_display' )   // callback  
    		);  
  
	} // end JS3N1_admin_init	

	/**
	 * The simple options callback
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _options_simple_callback( $args = array() ){
		$options = get_option('js3n1_options');

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids'] , true, false ), __('Enable FitVids.js', 'js3n1') );		

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fittext]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fittext'] , true, false ), __('Enable FitText.js', 'js3n1') );
				
		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering'] , true, false ), __('Enable Lettering.js', 'js3n1') );
	
	}

	/**
	 * The fitvids options callback
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _fitvids_options_callback( $args = array() ){
		$options = get_option('js3n1_options');

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_single_posts]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids_single_posts'] , true, false ), __('Videos in single posts', 'js3n1') );		

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_home_posts]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids_home_posts'] , true, false ), __('Videos on the home page', 'js3n1') );		

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_archive_posts]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids_archive_posts'] , true, false ), __('Videos in archives', 'js3n1') );		

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_widget]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids_widget'] , true, false ), __('Videos in sidebar(s)', 'js3n1') );
				
		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_custom]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_fitvids_custom'] , true, false ), __('Custom classes', 'js3n1') );

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_fitvids_custom_classes]" size="40" type="text" value="%s" />', $options["use_fitvids_custom_classes"] );

		printf ('<p class="description">%s</span>',  __('Enter the classes that you wish to run FitVids on, seperated by commas', 'js3n1') );
		//echo "<input id='js3n1_simple_options' name='js3n1_options[use_fitvids]' size='40' type='text' value='{$options['use_fitvids']}' />";
	
	}
	
	/**
	 * The fittext options callback
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _fittext_options_callback( $args = array() ){
		$options = get_option('js3n1_options');

		echo '<table>';
		echo '<tr>';

			printf('<td width="%s"><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_title]" type="checkbox" value="1" %s><span>%s</span></td>', '40%', checked( $options['use_fittext_site_title'] , true, false ), __('Site title', 'js3n1') );
			
			printf('<td width="%s"><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_title_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', '20%', __('Compression', 'js3n1'), esc_attr($options["use_fittext_site_title_compression"]) );
			
			printf('<td width="%s"><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_title_min]" min="1" size="5" type="number" value="%s" class="small-text" /></td>', '20%', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_site_title_min"]) );

			printf('<td width="%s"><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_title_max]" min="1" size="5" type="number" value="%s" class="small-text" /></td>', '20%', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_site_title_max"]) );

		echo '</tr><tr>';

			printf('<td><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_description]" type="checkbox" value="1" %s><span>%s</span></td>', checked( $options['use_fittext_site_description'] , true, false ), __('Site description', 'js3n1') );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_description_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', __('Compression', 'js3n1'), esc_attr($options["use_fittext_site_description_compression"]) );
			
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_description_min]" min="1" size="5" type="number" value="%s" class="small-text" /></td>', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_site_description_min"]) );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_site_description_max]" min="1" size="5" type="number" value="%s" class="small-text" /></td>', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_site_description_max"]) );

		echo '</tr><tr>';

			printf('<td><input id="js3n1_simple_options" name="js3n1_options[use_fittext_single_post_title]" type="checkbox" value="1" %s><span>%s</span></td>', checked( $options['use_fittext_single_post_title'] , true, false ), __('Single post titles', 'js3n1') );		

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_single_title_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', __('Compression', 'js3n1'), esc_attr($options["use_fittext_single_title_compression"]) );
			
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_single_title_min]" size="5" type="number" value="%s" class="small-text" /></td>', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_single_title_min"]) );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_single_title_max]" size="5" type="number" value="%s" class="small-text" /></td>', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_single_title_max"]) );

		echo '</tr><tr>';
					
			printf('<td><input id="js3n1_simple_options" name="js3n1_options[use_fittext_home_post_title]" type="checkbox" value="1" %s><span>%s</span></td>', checked( $options['use_fittext_home_post_title'] , true, false ), __('Homepage post titles', 'js3n1') );		

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_home_title_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', __('Compression', 'js3n1'), esc_attr($options["use_fittext_home_title_compression"]) );
			
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_home_title_min]" size="5" type="number" value="%s" class="small-text" /></td>', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_home_title_min"]) );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_home_title_max]" size="5" type="number" value="%s" class="small-text" /></td>', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_home_title_max"]) );

		echo '</tr><tr>';

			printf('<td><input id="js3n1_simple_options" name="js3n1_options[use_fittext_archive_post_title]" type="checkbox" value="1" %s><span>%s</span></td>', checked( $options['use_fittext_archive_post_title'] , true, false ), __('Archive post titles', 'js3n1') );
				
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_archive_title_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', __('Compression', 'js3n1'), esc_attr($options["use_fittext_archive_title_compression"]) );
			
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_archive_title_min]" size="5" type="number" value="%s" class="small-text" /></td>', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_archive_title_min"]) );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_archive_title_max]" size="5" type="number" value="%s" class="small-text" /></td>', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_archive_title_max"]) );

		echo '</tr><tr>';

			printf('<td><input id="js3n1_simple_options" name="js3n1_options[use_fittext_custom]" type="checkbox" value="1" %s><span>%s</span>', checked( $options['use_fittext_custom'] , true, false ), __('Custom classes', 'js3n1') );
			
			printf('<input id="js3n1_simple_options" name="js3n1_options[use_fittext_custom_classes]" size="40" type="text" value="%s" />', $options["use_fittext_custom_classes"] );
			
			printf ('<p class="description">%s</span>',  __('Enter the classes that you wish to run FitText on, seperated by commas. Using FitText on large amounts of text is highly discouraged and will cause problems.</td>', 'js3n1') );
		

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_custom_compression]" min="0.1" max="5" step="0.1" size="5" type="number" value="%s" class="small-text" /></td>', __('Compression', 'js3n1'), esc_attr($options["use_fittext_custom_compression"]) );
			
			//echo "<input id='js3n1_simple_options' name='js3n1_options[use_fitvids]' size='40' type='text' value='{$options['use_fitvids']}' />";
			
			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_custom_min]" size="5" type="number" value="%s" class="small-text" /></td>', __('Minimum', 'js3n1'), esc_attr($options["use_fittext_archive_title_min"]) );

			printf('<td><span>%s</span><input id="js3n1_simple_options" name="js3n1_options[use_fittext_custom_max]" size="5" type="number" value="%s" class="small-text" /></td>', __('Maximum', 'js3n1'), esc_attr($options["use_fittext_archive_title_max"]) );

		
		echo '</tr>';
		echo '</table>';
	}

	/**
	 * The lettering options callback
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _lettering_options_callback( $args = array() ){
		$options = get_option('js3n1_options');
		
		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_site_title]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering_site_title'] , true, false ), __('Site title', 'js3n1') );

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_site_description]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering_site_description'] , true, false ), __('Site description', 'js3n1') );
			
		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_single_post_title]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering_single_post_title'] , true, false ), __('Single post titles', 'js3n1') );		

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_archive_post_title]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering_archive_post_title'] , true, false ), __('Archive post titles', 'js3n1') );
				
		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_custom]" type="checkbox" value="1" %s><span>%s</span><br />', checked( $options['use_lettering_custom'] , true, false ), __('Custom classes', 'js3n1') );

		printf('<input id="js3n1_simple_options" name="js3n1_options[use_lettering_custom_classes]" size="40" type="text" value="%s" />', $options["use_lettering_custom_classes"] );

		printf ('<p class="description">%s</span>',  __('Enter the classes that you wish to run Lettering on, seperated by commas. Using Lettering on large amounts of text is highly discouraged and will cause problems.', 'js3n1') );
		//echo "<input id='js3n1_simple_options' name='js3n1_options[use_fitvids]' size='40' type='text' value='{$options['use_fitvids']}' />";
	
	}
	
	/**
	 * Adds class fitvids to the post class
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _fitvids_post_class($classes) {
	 	global $post;

	 	if ( in_the_loop() )
			$classes[] = 'fitvideo';
	    
	    return $classes;
	}
		
	/**
	 * Adds class fitvids to the body class
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _fitvids_body_class($classes) {
	 	global $post;

		$classes[] = 'fitvideo';
	    
	    return $classes;
	}
	
	/**
	 * Outputs the plugin settings page
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _js3n1_plugin_display() { 

		$options = get_option('js3n1_options'); ?>
  		<div class="wrap">
   		<h2><?php _e('FitVids, FitText and Letting Plugin Options', 'js3n1');?></h2>
   		<p class="preamble"><?php _e('This plugin uses three javascripts FitVids, FitText and Lettering. These jQuery plugins are written and maintained by the fine fellows at Parvavel.'); ?>
   			<php _e('The raw jquery plugins can be found here:'); ?>
   				<table>
   					<tr>
   						<td><?php _e('FitVids', 'js3n1'); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'http://fitvidsjs.com/', __('Fitvidsjs.com', 'js3n1'), __('Fitvidsjs.com', 'js3n1') ); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'https://github.com/davatron5000/FitVids.js', __('Fitvids.js on GitHub', 'js3n1'), __('Fitvids.js on GitHub', 'js3n1') ); ?></td>
   					</tr>
   					<tr>
   						<td><?php _e('FitText', 'js3n1'); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'http://fittextjs.com/', __('Fittextjs.com', 'js3n1'), __('Fittextjs.com', 'js3n1') ); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'https://github.com/davatron5000/FitText.js', __('Fittext.js on GitHub', 'js3n1'), __('Fittext.js on GitHub', 'js3n1') ); ?></td>
   					</tr>
   					<tr>
   						<td><?php _e('Lettering', 'js3n1'); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'http://letteringjs.com/', __('Letteringjs.com', 'js3n1'), __('Letteringjs.com', 'js3n1') ); ?></td>
   						<td><?php printf('<a href="%s" title="%s">%s</a>', 'https://github.com/davatron5000/Lettering.js', __('Lettering.js on GitHub', 'js3n1'), __('Lettering.js on GitHub', 'js3n1') ); ?></td>
   					</tr>
   				</table></p>
  		<p class="description"><?php _e('Select the components you wish to use, then tweak the settings the way you prefer.', 'js3n1'); ?></p>
		        <?php settings_errors(); ?>  
        <form method="post" action="options.php"> 
            <?php settings_fields( 'js3n1_options' ); ?> 
            <?php do_settings_sections( 'js3n1' ); ?> 
            <?php submit_button(); ?>  
        </form>     		
		</div>
		<?php 
  
	} // end js3n1_plugin_display  
	
	/**
	 * Holds the default settings
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 * @return $defaults array
	 */
	public static function _get_default_settings() {
		//set everything to OFF for now, I'm sure there is a better way but for now, why not use it. 
		$defaults = array(
			'use_fitvids'			=> '0', 
			'use_fittext' 			=> '0', 
			'use_lettering'			=> '0', 
			
			'use_fitvids_single_posts'				=> '0',
			'use_fitvids_home_posts'				=> '0',
			'use_fitvids_archive_posts'				=> '0',
			'use_fitvids_widget'					=> '0', 
			'use_fitvids_custom'					=> '0',
			
			'use_fittext_single_post_title'				=> '0',
			'use_fittext_single_title_compression' 		=> '1',
			'use_fittext_single_title_min'				=> '10', 
			'use_fittext_single_title_max' 				=> '20',

			'use_fittext_home_post_title'				=> '0',
			'use_fittext_home_title_compression' 		=> '1', 
			'use_fittext_home_title_min'				=> '10', 
			'use_fittext_home_title_max' 				=> '20',

			'use_fittext_archive_post_title'			=> '0', 
			'use_fittext_archive_title_compression' 	=> '1',
			'use_fittext_archive_title_min'				=> '10', 
			'use_fittext_archive_title_max' 			=> '20',

			'use_fittext_site_title'					=> '0',
			'use_fittext_site_title_compression' 		=> '1',
			'use_fittext_site_title_min'				=> '10', 
			'use_fittext_site_title_max' 				=> '20',

			'use_fittext_site_description'				=> '0',
			'use_fittext_site_description_compression' 	=> '1',
			'use_fittext_site_description_min'			=> '10', 
			'use_fittext_site_description_max' 			=> '20',

			'use_fittext_custom'						=> '0',
			'use_fittext_custom_compression' 			=> '1',
			'use_fittext_single_min'					=> '10', 
			'use_fittext_single_max' 					=> '20',

			'use_lettering_single_post_title'	=> '0',
			'use_lettering_archive_post_title'	=> '0', 
			'use_lettering_site_title'			=> '0',
			'use_lettering_site_description'	=> '0',
			'use_lettering_custom'				=> '0',
			
			'use_fitvids_custom_classes' 		=> '',
			'use_fittext_custom_classes' 		=> '',
			'use_lettering_custom_classes' 		=> '',
			);
		return $defaults;
	}

	/**
	 * Validates the options set on the plugin settings page
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 * @return $defaults array
	 */
	function _validate_options( $opts ) {

		$defaults = self::_get_default_settings();
		
		foreach ( $defaults as $default => $default_value) {
			if ( ! isset( $opts[$default] ) ) 
				$opts[$default] = $default_value;
		}

		// //remove spaces from the custom classes
		// $opts['use_fitvids_custom_classes'] 	= str_replace(" ", "", $opts['use_fitvids_custom_classes']);
		// $opts['use_fittext_custom_classes'] 	= str_replace(" ", "", $opts['use_fittext_custom_classes']);
		// $opts['use_lettering_custom_classes'] 	= str_replace(" ", "", $opts['use_lettering_custom_classes']);

		return $opts;
	}
	/**
	 * The Main Options Display Callback function
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 * @return $options array
	 */
	function _main_options_display( $opts ) {
		return $opts;
	}
	
	/**
	 * The theme information 
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 1.0
	 * @todo integrate this with the WP_Theme API
	 * @return $theme array
	 */
	static function _get_theme_info(){
		
		if( function_exists( 'twentytwelve_setup' ) ) //its twentytwelve
			$theme = array(
				'title_container'		=>	'.site-title', 
				'description_container'	=>	'.site-description',
			);
		if( function_exists( 'twentyeleven_setup' ) ) //its twentytwelve
			$theme = array(
				'title_container'		=>	'#site-title', 
				'description_container'	=>	'#site-description',
			);
		else if ( function_exists( 'genesis' ) ) //its genesis
			$theme = array(
				'title_container'		=> 	'#title',
				'description_container'	=>	'#description',
			);

		$theme['entry_title'] 			= '.entry-title';

		return $theme;
	}
	
	/**
	 * the fitvids markup generator 
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.5
	 * @param $container string the DOM element which to apply fitvids
	 * @return string 
	 */
	public static function _apply_fitvids_to( $container ){
		return sprintf('$("%s").fitVids();', esc_js( $container ) ) ;
	}
		
	/**
	 * the fittext markup generator 
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.5
	 * @param $container string the DOM element which to apply fittext
	 * @param $compression string how aggressivly to apply fittext to $container
	 * @param $min string mimimum size to apply fittext to $container
	 * @param $max string maximum size to apply fittext to $container
	 * @return string 
	 */
	public static function _apply_fittext_to( $container, $compression = '1', $min = '10', $max = '40' ){
		return sprintf('$("%s").fitText(%s, { minFontSize: "%spx", maxFontSize: "%spx" });', esc_js( $container ), esc_js( $compression ), esc_js( $min ), esc_js( $max ) );
	}
	
	/**
	 * the lettering markup generator 
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.5
	 * @param $container string the DOM element which to apply lettering
	 * @param $method string the method which to apply lettering to $container
	 * @return string 
	 */
	public static function _apply_lettering_to( $container, $method = 'words' ){
		return sprintf('$("%s").lettering("%s");', esc_js( $container ), esc_js( $method ) );
	}
	
	/**
	 * filters the final js generated before outputting within jQuery function
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.5
	 * @param $js string the origional javascript 
	 * @return $js string the modified js if applicable 
	 */
	function _final_js_filter( $js ) {
		return $js;
	}
	
	/**
	 * registers the javascript files
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
	function _register_js()
	{
		wp_register_script( 'fittext', JS3N1_JS_URL . '/jquery.fittext.js', array('jquery'), JS3N1_VER , true );
		wp_register_script( 'fitvids', JS3N1_JS_URL . '/jquery.fitvids.js', array('jquery'), JS3N1_VER , true );
		wp_register_script( 'lettering', JS3N1_JS_URL . '/jquery.lettering.js', array('jquery'), JS3N1_VER , true );
	}
	
   	/**
	 * enqueues the js files
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.1
	 */
    function _enqueue_js()
	{
		if ( is_admin() )
			return false;

		$options = get_option('js3n1_options');

		if ( !$options )
			return false;

		if ( $options['use_fitvids'] )
			wp_enqueue_script('fitvids');

		if ( $options['use_fittext'] )
			wp_enqueue_script('fittext');

		if ( $options['use_lettering'] )
            wp_enqueue_script('lettering');
 
	} // end enqueue_js
	
	/**
	 * compiles the user settings into a jQuery function
	 * @author Russell Fair
	 * @package FitVids, FitText and Lettering
	 * @since 0.5
	 * @return string 
	 */
	function _do_user_js() {
		
		$options = get_option('js3n1_options'); 
		if ( !$options )
			return; 

		if ( ! $options['use_fitvids'] && ! $options['use_fittext'] && ! $options['use_lettering'] )
			return;

		//need to access theme api for title and description containers
		$theme = self::_get_theme_info();

		$user_js = '';

		if ( $options['use_fitvids'] ) { 
			
			if ( $options['use_fitvids_single_posts'] ) 
	        	$user_js .= self::_apply_fitvids_to('body.single .fitvideo');

	        if ( $options['use_fitvids_archive_posts'] ) 
	        	$user_js .= self::_apply_fitvids_to('body.archive .fitvideo'); 
	        
	        if ( $options['use_fitvids_home_posts'] )
	        	$user_js .= self::_apply_fitvids_to('body.home .fitvideo');
	        
	        if ( $options['use_fitvids_widget'] ) 
	        	$user_js .= self::_apply_fitvids_to('.widget');
	        
	        //loop through the custom items and apply fitvids
	        if ( $options['use_fitvids_custom'] && $options['use_fitvids_custom_classes'] != '' ) {

	        	$user_customs = explode(',', $options['use_fitvids_custom_classes']);
	        	
	        	foreach ( $user_customs as $custom ) {
	        		$user_js .= self::_apply_fitvids_to( $custom );
	        	}
	        	
	        }
	        	
		} 

		if ( $options['use_fittext'] ) { 

			if ( $options['use_fittext_site_title'] ) 
		       	$user_js .= self::_apply_fittext_to( $theme['title_container'], $options['use_fittext_site_title_compression'], $options['use_fittext_site_title_min'], $options['use_fittext_site_title_max'] );

			if ( $options['use_fittext_site_description'] )	
				$user_js .= self::_apply_fittext_to( $theme['description_container'], $options['use_fittext_site_description_compression'], $options['use_fittext_site_description_min'], $options['use_fittext_site_description_max'] );

			if ( $options['use_fittext_single_post_title'] ) 
				$user_js .= self::_apply_fittext_to( sprintf('body.single %s', $theme['entry_title']), $options['use_fittext_single_title_compression'], $options['use_fittext_single_title_min'], $options['use_fittext_single_title_max'] );

			if ( $options['use_fittext_archive_post_title'] ) 
				$user_js .= self::_apply_fittext_to( sprintf('body.archive %s a', $theme['entry_title']), $options['use_fittext_archive_title_compression'], $options['use_fittext_archive_title_min'], $options['use_fittext_archive_title_max'] );

			if ( $options['use_fittext_home_post_title'] ) 
				$user_js .= self::_apply_fittext_to( sprintf('body.home %s a', $theme['entry_title']), $options['use_fittext_home_title_compression'], $options['use_fittext_home_title_min'], $options['use_fittext_home_title_max'] );

			//loop through the custom items and apply fittext
			if ( $options['use_fittext_custom'] && $options['use_fittext_custom_classes'] != '' ) {

	        	$user_customs = explode(',', $options['use_fittext_custom_classes']);
	        	
	        	foreach ( $user_customs as $custom ) {
	        		$user_js .= self::_apply_fittext_to( $custom, '1', '20', '40' );
	        	}
	        	
	        }
		
		} 

		if ( $options['use_lettering'] ) { 
	        
	        if ( $options['use_lettering_site_title'] ) 
	        	$user_js .= self::_apply_lettering_to( $theme['title_container'], 'words' );

	        if ( $options['use_lettering_site_description'] ) 
				$user_js .= self::_apply_lettering_to( $theme['description_container'] , 'words' );
			
			//loop through the custom items and apply lettering
			if ( $options['use_lettering_custom'] && $options['use_lettering_custom_classes'] != '' ) {

	        	$user_customs = explode(',', $options['use_lettering_custom_classes']);
	        	
	        	foreach ( $user_customs as $custom ) {
	        		$user_js .= self::_apply_lettering_to( $custom );
	        	}
	        	
	        }
		} 

		$final_js = apply_filters( 'js3n1_js' , $user_js );

		echo '<!--start fitvids, fittext and lettering user js-->'; ?>
		<script type="text/javascript">
			//<[CDATA[
			jQuery(document).ready(function($){<?php echo $final_js; ?>});	
			//]]>
		</script>
	<?php 	} //end do_user_js
		
} // end JS3N1_Start

new JS3N1_Start();