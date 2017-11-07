<?php
/**
 * Guggenheim Event Calendar Demonstration Widget
 * *
 * @package   Guggenheim_Event_Widget
 * @author    Chris Bodenlos chris@ohm.io
 * @license   GPL-2.0+
 * @link      https://github.com/bodenlos
 * @copyright 2017 Chris Bodenlos
 *
 * @wordpress-plugin
 * Plugin Name:       Guggenheim Event Calendar Demonstration
 * Plugin URI:        https://github.com/bodenlos
 * Description:       Guggenheim Event Calendar Demonstration displays upcoming events from the calendar at guggenheim.org.
 * Version:           1.0.0
 * Author:            Chris Bodenlos
 * Author URI:        https://christopher.io
 * Text Domain:       guggenheim-events
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /lang
 * GitHub Plugin URI: https://github.com/bodenlos
 */
 
 // Prevent direct file access
if ( ! defined ( 'ABSPATH' ) ) {
	exit;
}

class Guggenheim_Events extends WP_Widget {

    /**
     * @since    1.0.0
     *
     * @var      string
     */
    protected $widget_slug = 'guggenheim-events';

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	public function __construct() {

		// load plugin text domain
		add_action( 'init', array( $this, 'widget_textdomain' ) );		

		parent::__construct(
			$this->get_widget_slug(),
			__( 'Guggenheim Event Calendar', $this->get_widget_slug() ),
			array(
				'classname'  => $this->get_widget_slug().'-class',
				'description' => __( 'Guggenheim upcoming events widget.', $this->get_widget_slug() )
			)
		);

		// Register styles
		add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_styles' ) );

		// Refresh cached output with each new post
		add_action( 'save_post',    array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // end constructor


    /**
     * Return the widget slug.
     *
     * @since    1.0.0
     *
     * @return    Plugin slug variable.
     */
    public function get_widget_slug() {
        return $this->widget_slug;
    }

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args  The array of form elements
	 * @param array instance The current instance of the widget
	 */
	public function widget( $args, $instance ) {

		// Check for cached output
		$cache = wp_cache_get( $this->get_widget_slug(), 'widget' );

		if ( !is_array( $cache ) ){
			$cache = array();
		}

		if ( ! isset ( $args['widget_id'] ) ){
			$args['widget_id'] = $this->id;
		}

		if ( isset ( $cache[ $args['widget_id'] ] ) ){
			return print $cache[ $args['widget_id'] ];
		}
		
		extract( $args, EXTR_SKIP );

		// Retrieve remote events
		$events = $this->get_remote_events();
		
		$widget_string = $before_widget;

		ob_start();
		
		include( plugin_dir_path( __FILE__ ) . 'views/widget.php' );
		$widget_string .= ob_get_clean();
		$widget_string .= $after_widget;

		$cache[ $args['widget_id'] ] = $widget_string;

		wp_cache_set( $this->get_widget_slug(), $cache, 'widget' );

		print $widget_string;

	} // end widget
	
	
	public function flush_widget_cache() 
	{
    	wp_cache_delete( $this->get_widget_slug(), 'widget' );
	}
	/**
	 * @param array new_instance The new instance of values to be generated via the update.
	 * @param array old_instance The previous instance of values before the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		return $instance;

	} // end update

	/**
	 * Administration panel form
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		$instance = wp_parse_args(
			(array) $instance
		);
		
		// Display the admin form - TODO add customization options: title, days-in-advance, calendar URL, word count
		include( plugin_dir_path(__FILE__) . 'views/admin.php' );

	} // end form

	/*--------------------------------------------------*/
	/* Public Functions
	/*--------------------------------------------------*/

	/**
	* Truncates a string according to specified word count.
	*/
    public function word_count($phrase, $max_words) {
		$phrase_array = explode(' ',$phrase);
		if(count($phrase_array) > $max_words && $max_words > 0)
		   $phrase = implode(' ',array_slice($phrase_array, 0, $max_words)).'&#8230;';
		return $phrase;
	 }

	/**
	* Retrieves event calendar data from guggenheim.org.
	*/
	public function get_remote_events() {
        $events = get_transient( 'calendar_events' );
        
        if( empty( $events ) ) {
			$date = current_time( 'Y-m-d' );
            $response = wp_remote_get( 'https://www.guggenheim.org/wp-json/calendar/v1/events?start_date=' . $date . '&days=7' );

            if( is_wp_error( $response ) ) {
                return array();
            }
    
            $events = json_decode( wp_remote_retrieve_body( $response ) );

            if( empty( $events ) ) {
                return array();
            }
    
            set_transient( 'calendar_events', $events, 12 * HOUR_IN_SECONDS );
        }
    
        return $events;
	} // end get_remote_events
	
	/**
	 * Loads the events calendar's text domain for localization and translation.
	 */
	public function widget_textdomain() {

		load_plugin_textdomain( $this->get_widget_slug(), false, plugin_dir_path( __FILE__ ) . 'lang/' );

	} // end widget_textdomain

	/**
	 * Fired when the event calendar widget is activated.
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
	} // end activate

	/**
	 * Fired when the event calendar widget is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public static function deactivate( $network_wide ) {
	} // end deactivate

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {

		wp_enqueue_style( $this->get_widget_slug().'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );

	} // end register_admin_styles


	/**
	 * Registers and enqueues widget-specific styles.
	 */
	public function register_widget_styles() {

		wp_enqueue_style( $this->get_widget_slug().'-widget-styles', plugins_url( 'css/widget.css', __FILE__ ) );

	} // end register_widget_styles


} // end class

function register_Guggenheim_Events() {
    register_widget("Guggenheim_Events");
}
add_action( 'widgets_init', 'register_Guggenheim_Events' );

// Hooks fired when the Widget is activated and deactivated
register_activation_hook( __FILE__, array( 'Guggenheim_Events', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Guggenheim_Events', 'deactivate' ) );
