<?php

/**
 * Description: This class is used to add featured posts.
 * Version: 1.0
 * Author: VLThemes
 * Author URI: http://themeforest.net/user/vlthemes
 */

class VLThemesFeaturedPost {

	/**
	 * The single class instance.
	 * @var $_instance
	 */
	private static $_instance = null;

	/**
	 * Main Instance
	 * Ensures only one instance of this class exists in memory at any one time.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::$_instance->init_hooks();
		}
		return self::$_instance;
	}

	public function __construct() {
		/**
		 * We do nothing here!
		 */
	}

	/**
	 * init hooks
	 */
	public function init_hooks() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_ajax_toggle-featured-post', array( $this, 'admin_ajax' ) );
	}

	/**
	 * Admin Init
	 */
	public function admin_init() {
		add_filter( 'display_post_states', array( $this, 'featured_post_states' ) );
		add_action( 'new_to_publish', array( $this, 'set_not_featured' ), 1, 2 );
		add_action( 'draft_to_publish', array( $this, 'set_not_featured' ), 1, 2 );
		add_action( 'pending_to_publish', array( $this, 'set_not_featured' ), 1, 2 );
		add_action( 'admin_head-edit.php', array( $this, 'admin_head_script' ) );
		add_filter( 'pre_get_posts', array( $this, 'admin_pre_get_posts' ), 1 );
		add_filter( 'manage_edit-post_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'edit_screen_featured_ui' ) );
		add_action( 'save_post', array( $this, 'edit_screen_featured_save' ) );
	}


	public function featured_post_states( $states ) {
		global $post;
		if ( get_post_meta( $post->ID, '_is_featured', true ) === 'yes' ) {
			$states[] = esc_html__( 'Featured', 'ramsay' );
		}
		return $states;
	}


	public function manage_posts_columns( $columns ) {
		$columns['featured'] = esc_html__( 'Featured', 'ramsay' );
		return $columns;
	}

	public function manage_posts_custom_column( $column_name, $postID ) {
		if ( $column_name == 'featured' ) {
			$is_featured = get_post_meta( $postID, '_is_featured', true );
			$class = 'dashicons';
			if ( $is_featured == 'yes' ) {
			   $class .= ' dashicons-star-filled';
			} else {
			   $class .= ' dashicons-star-empty';
			}
			echo '<a href="#" class="featured-post-toggle '.$class.'" data-post-id="'.$postID.'"></a>';
		}
	}

	public function admin_head_script() {
		echo '<script type="text/javascript">
		jQuery(document).ready(function($){
			$(\'.featured-post-toggle\').on("click",function(e){
			   e.preventDefault();
			   var _el=$(this);
			   var postID=$(this).attr(\'data-post-id\');
			   var data={action:\'toggle-featured-post\',postID:postID};
			   $.ajax({url:ajaxurl,data:data,type:\'post\',
				   dataType:\'json\',
				   success:function(data){
				   _el.removeClass(\'dashicons-star-filled\').removeClass(\'dashicons-star-empty\');
				   if(data.new_status=="yes"){
					   _el.addClass(\'dashicons-star-filled\');
				   }else{
					   _el.addClass(\'dashicons-star-empty\');
				   }
				   }
			   });
			});
		});
		</script>';
	}

	public function admin_ajax() {
		header( 'Content-Type: application/json' );
		$postID = $_POST['postID'];
		$is_featured = get_post_meta( $postID, '_is_featured', true );
		$newStatus   = $is_featured == 'yes' ? 'no' : 'yes';
		delete_post_meta( $postID, '_is_featured' );
		add_post_meta( $postID, '_is_featured', $newStatus );
		echo json_encode( array(
			'ID' => $postID,
			'new_status' => $newStatus
		) );
		die();
	}

	public function set_not_featured( $postID ) {
		add_post_meta( $postID, '_is_featured', 'no' );
	}

	public function admin_pre_get_posts( $query ) {
		global $wp_query;
		if ( is_admin() && isset( $_GET['post_status'] ) && $_GET['post_status'] == 'featured' ) {
			$query->set( 'meta_key', '_is_featured' );
			$query->set( 'meta_value', 'yes' );
		}
		return $query;
	}

	public function query_vars( $public_query_vars ) {
		$public_query_vars[] = 'featured';
		return $public_query_vars;
	}

	public function pre_get_posts( $query ) {
		if ( !is_admin() ) {
			if ( $query->get( 'featured' ) == 'yes' ) {
			   $query->set( 'meta_key', '_is_featured' );
			   $query->set( 'meta_value', 'yes' );
			}
		}
		return $query;
	}

	public function edit_screen_featured_ui() {
		if ( is_admin() ) {
			global $post;
	    	if ( get_post_type( $post->ID ) != 'post' ) {
		        return;
		    }
			echo '<div class="misc-pub-section"><span style="color:#82878c; margin-top: -2px; margin-left:-1px; padding-right: 3px;" class="dashicons dashicons-star-filled"></span>';
			echo '<label for="featured" title="' . esc_attr__( 'If checked, this is marked as featured.', 'ramsay' ) . '">' . "\n";
			echo esc_html__( 'Featured?', 'ramsay' ) . ' <input id="featured"" type="checkbox" value="yes" ' . checked( get_post_meta( $post->ID, '_is_featured', true ), 'yes', false ) . ' name="featured" /></label></div>' . "\n";
		}
	}

	public function edit_screen_featured_save( $postID ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( !current_user_can( 'edit_post', $postID ) ) {
			return;
		}
	    if ( isset( $_POST['featured'] ) ) {
	        update_post_meta( $postID, '_is_featured', $_POST['featured'] );
	    } else {
	        delete_post_meta( $postID, '_is_featured' );
	    }

	}
}
VLThemesFeaturedPost::instance();