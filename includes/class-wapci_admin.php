<?php
/**
 * WAPCI_Admin Class.
 *
 * @class       WAPCI_Admin
 * @version		1.0
 * @author lafif <lafif@astahdziq.in>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WAPCI_Admin class.
 */
class WAPCI_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();
		add_action('admin_bar_menu', array($this, 'populate_cat_images_toolbar'), 999, 1);

		add_action( 'admin_print_styles-edit-tags.php', array($this, 'load_scripts') );

		add_action( 'wp_ajax_wapci_get_product_cats', array($this, 'get_product_cats') );
		add_action( 'wp_ajax_wapci_populate', array($this, 'populate_cat_images') );
	}

	public function populate_cat_images_toolbar($wp_admin_bar){
		global $current_screen;

		// just need to show on edit product cat page
		if($current_screen->id != 'edit-product_cat')
			return;

		if(isset($_GET['tag_ID']))
			return;

		$args = array(
			'id' => 'wapci-populate',
			'title' => __('Populate Category Images', 'wapci'), 
			'href' => '#', 
			'meta' => array(
				'class' => 'wapci-populate', 
				'title' => __('Click to Populate Category Images', 'wapci')
				)
		);
		$wp_admin_bar->add_node($args);
	}

	public function load_scripts(){
		global $current_screen;

	    // just need to show on edit product cat page
	    if( 'edit-product_cat' != $current_screen->id )
	        return;

	    // Debug
	    // echo "<script>alert('$current_screen->id')</script>";

	    // wp_enqueue_style( 'wapci-admin' );
	    wp_enqueue_script( 'wapci-admin' );
	    wp_localize_script( 'wapci-admin', 'WAPCI', array(
	    	'ajax_url' => admin_url( 'admin-ajax.php' ),
	    	'nonce' => wp_create_nonce( 'wapci-populate' )
	    ) );
	}

	public function get_product_cats(){

		check_ajax_referer( 'wapci-populate', 'nonce' );

		// default
		$result = array(
			'status' => 'failed',
			'message' => __('No item found,', 'wapci'),
			'items' => array()
		);

		$items = get_terms( array(
		    'taxonomy' => 'product_cat',
		    'hide_empty' => false,
		    'fields' => 'ids'
		) );

		if(!empty($items)){
			$result = array(
				'status' => 'success',
				'message' => sprintf(__('Found %s product categories', 'wapci'), count($items)),
				'items' => $items
			);
		}

		die( json_encode( $result ) );
	}

	public function populate_cat_images(){
		global $wpdb;

		check_ajax_referer( 'wapci-populate', 'nonce' );

		$result = array(
			'status' => 'failed',
			'message' => __('Item failed to process', 'wapci'),
			'log' => array()
		);

		$log = array();

		$term_id = $_REQUEST['id'];

		$name = $wpdb->get_var("SELECT name FROM {$wpdb->terms} WHERE term_id = '{$term_id}'");
		$term_name = (is_null($name)) ? $term_id : $name;

		$log[] = "Processing category ".$name." with id ( ".$term_id." )";

		$merge_with_childs = $this->merge_with_childs($term_id);
		$thumb = $this->get_product_thumb_from_term_ids($merge_with_childs);

		$log[] = "Looking for product image on categories " . $merge_with_childs;
		
		if(!is_null($thumb)){

			update_woocommerce_term_meta( $term_id, 'thumbnail_id', absint( $thumb ) );
			
			$log[] = "Image found with id : " . $thumb;

			$result = array( 
		    	'status' => 'success',
				'message' => sprintf( __('Product category %s processed', 'wapci'), $term_name ),
				'log' => $log
			);
		} else {
			$log[] = "Image not found";
			$result['message'] = sprintf( __('No image found for product cat %s', 'wapci'), $term_name );
			$result['log'] = $log;
		}

		die( json_encode( $result ) );
	}

	private function merge_with_childs($term_id){
		$args = array(
		    'child_of' => $term_id,
		    'fields' => 'ids',
		    'hide_empty' => 0,
		    'hierarchical' => true,
		    );
		$childs = get_terms( 'product_cat', $args );
		$ids = array_merge( array($term_id), $childs);

		return implode(',', $ids);
	}

	private function get_product_thumb_from_term_ids($term_id){
		global $wpdb;

		$query = "SELECT pm.image FROM {$wpdb->posts} p
		LEFT JOIN 
		( SELECT  
		  post_id, 
		  GROUP_CONCAT(if(meta_key = '_thumbnail_id', meta_value, NULL)) AS 'image'
		  FROM {$wpdb->postmeta}
		  GROUP BY post_id ) pm
		ON pm.post_id = p.ID
		# taxonomy
		LEFT JOIN {$wpdb->term_relationships} as rel ON p.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} as tax ON rel.term_taxonomy_id = tax.term_taxonomy_id
		LEFT JOIN {$wpdb->terms} as term ON tax.term_id = term.term_id
		# Where
		WHERE p.post_status = 'publish'
		AND p.post_type = 'product'
		AND tax.taxonomy = 'product_cat' 
		AND term.term_id IN ({$term_id})
		AND pm.image IS NOT NULL
		ORDER BY RAND() LIMIT 1";

		$r = $wpdb->get_var($query);

		return $r;
	}

	public function includes(){
		
	}

}

$GLOBALS['wapci_admin'] = new WAPCI_Admin();