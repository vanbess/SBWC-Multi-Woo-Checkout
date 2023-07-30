<?php
class OneCheckoutTemplate
{

	private static $instance;
	protected static $templates;

	// add nav menu custom template
	public static function add_custom_nav_menu()
	{
		register_nav_menus(
			array(
				'mwc-onecheckout-faq-menu' => __('One Checkout FAQ Menu Footer'),
				'mwc-onecheckout-end-menu' => __('One Checkout End Menu Footer')
			)
		);
	}

	// create custom template
	public static function get_instance()
	{

		if (null == self::$instance) {
			self::$instance = new OneCheckoutTemplate();
		}

		return self::$instance;
	}

	private function __construct()
	{

		self::$templates = array();

		// Add a filter to the attributes metabox to inject template into the cache.
		if (version_compare(floatval(get_bloginfo('version')), '4.7', '<')) {

			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array($this, 'register_onecheckout_templates')
			);
		} else {

			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates',
				array($this, 'add_new_template')
			);
		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array($this, 'register_onecheckout_templates')
		);

		// Add a filter to the template include to determine if the page has our 
		// template assigned and return it's path
		add_filter(
			'template_include',
			array($this, 'view_onecheckout_template')
		);


		// Add your templates to this array.
		self::$templates = array(
			'../templates/onecheckout-template-light.php' => 'OnePage - Checkout - Light',
			'../templates/onecheckout-template-dark.php' => 'OnePage - Checkout - Dark',
		);
	}

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public static function add_new_template($posts_templates)
	{
		$posts_templates = array_merge($posts_templates, self::$templates);
		return $posts_templates;
	}

	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public static function register_onecheckout_templates($atts)
	{

		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());

		// Retrieve the cache list. 
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if (empty($templates)) {
			$templates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete($cache_key, 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge($templates, self::$templates);

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		set_transient($cache_key, $templates, 'themes', 1800);

		return $atts;
	}

	/**
	 * Checks if the template is assigned to the page
	 */
	public static function view_onecheckout_template($template)
	{

		// Get global post
		global $post;

		// Return template if post is empty
		if (!$post) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if (!isset(self::$templates[get_post_meta(
			$post->ID,
			'_wp_page_template',
			true
		)])) {
			return $template;
		}

		$file = plugin_dir_path(__FILE__) . get_post_meta(
			$post->ID,
			'_wp_page_template',
			true
		);

		// Just to be safe, we check if the file exist first
		if (file_exists($file)) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;
	}
}

add_action('plugins_loaded', array('OneCheckoutTemplate', 'get_instance'));
add_action('init', array('OneCheckoutTemplate', 'add_custom_nav_menu'));
