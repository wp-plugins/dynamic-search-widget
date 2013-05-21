<?php
/*
Plugin Name: Dynamic Search Widget
Description: Dynamic flexible ajax search widget. 
Author: Simeon Ackermann
Version: 0.13.05
Author URI: http://a-simeon.de
*/

/**
 * Adds Widget.
 */
class Dynamic_Search_widget extends WP_Widget {

	protected static $options_default = array(
			'title'				=> 'Search Widget',
			'search_field'		=> '#s',
			'show_own_search'	=> 'on',
			'search_type'		=> 'post',
			'search_cats'		=> 'on',
			'search_tags'		=> 'on',
			'search_post_vals'		=> 'on',
		);

	var $options = array();

	public function __construct() {
		parent::__construct(
	 		'dynsw',
			'Dynamic Search Widget',
			array( 'description' => __( 'Dynamic flexible ajax search widget.', 'dynsw' ), )
		);

		if ( is_active_widget( false, false, $this->id_base ) ) {
	        add_action('wp_enqueue_scripts', array($this, 'initScripts'));        
	        add_action('wp_ajax_dynsw_search_similars', array($this, 'ajax_search_similars') );
	        add_action('wp_ajax_nopriv_dynsw_search_similars', array($this, 'ajax_search_similars') );        
			add_action( 'wp_head', array( $this, 'css' ) );
		}
	}

	function css() {
		?><style type="text/css">
		.dynsw-search {
			width: 60%;
		}
		.dynsw-loader {
			text-align: center;
			margin: auto auto;
			display: none;
		}
		</style><?php
	}

	function initScripts() {
		wp_register_script( 'dynsw_script', plugins_url("/script.js" , __FILE__ ), array('jquery') );
		wp_enqueue_script( 'dynsw_script' );

		$options = get_option($this->option_name);

		$search_fields = array();
		if ( ! empty($options[$this->number]['search_field']) ) $search_fields[] = $options[$this->number]['search_field'];
		if ( isset($options[$this->number]['show_own_search']) ) $search_fields[] = "." . $this->id_base . "-search";
		
		wp_localize_script( 'dynsw_script', 'dynsw_script',
			array('ajaxurl'	=> admin_url('admin-ajax.php'),
				  'search_fields'=> $search_fields,
			));
	}

	/**
	 * Sanitize widget form values as they are saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array_map("strip_tags", $new_instance);
		$instance['show_own_search'] = isset($new_instance['show_own_search']) ? 'on' : 'off';
		$instance['search_cats'] = isset($new_instance['search_cats']) ? 'on' : 'off';
		$instance['search_tags'] = isset($new_instance['search_tags']) ? 'on' : 'off';
		$instance['search_post_vals'] = isset($new_instance['search_post_vals']) ? 'on' : 'off';
		return $instance;
	}

	/**
	 * Back-end widget form.
	 */
	public function form( $instance ) {
		$instance = ($instance !== false) ? array_merge(self::$options_default, $instance) : self::$options_default;
		extract($instance);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id( 'show-own-search' ); ?>"><?php _e( 'Show own Searchfield:', 'dynsw' ); ?></label>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'show-own-search' ); ?>" name="<?php echo $this->get_field_name( 'show_own_search' ); ?>" <?php checked( $show_own_search, "on" ); ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'field-id' ); ?>"><?php _e( 'Alternative Searchfield:', 'dynsw' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'field-id' ); ?>" name="<?php echo $this->get_field_name( 'search_field' ); ?>" type="text" value="<?php echo esc_attr( $search_field ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'search-type' ); ?>"><?php _e( 'Post Type:' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'search-type' ); ?>" name="<?php echo $this->get_field_name( 'search_type' ); ?>">
				<option value="post" <?php selected( $search_type, "post" ); ?>>Post</option>
				<option value="page" <?php selected( $search_type, "page" ); ?>>Page</option>
			</select>				
		</p>
		<p><?php _e( 'Search in following fields:', 'dynsw' ); ?></p>
		<p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'search-cats' ); ?>" name="<?php echo $this->get_field_name( 'search_cats' ); ?>" <?php checked( $search_cats, "on" ); ?> />
			<label for="<?php echo $this->get_field_id( 'search-cats' ); ?>"><?php _e( 'Categories' ); ?></label> 
		</p><p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'search-tags' ); ?>" name="<?php echo $this->get_field_name( 'search_tags' ); ?>" <?php checked( $search_tags, "on" ); ?> />
			<label for="<?php echo $this->get_field_id( 'search-tags' ); ?>"><?php _e( 'Tags' ); ?></label> 
		</p><p>
			<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'search-post-vals' ); ?>" name="<?php echo $this->get_field_name( 'search_post_vals' ); ?>" <?php checked( $search_post_vals, "on" ); ?> />
			<label for="<?php echo $this->get_field_id( 'search-post-vals' ); ?>"><?php _e( 'Title' ); echo ", "; _e( 'Content' ); ?></label> 
		</p>
		<?php 
	}

	/**
	 * Front-end display of widget.
	*/
	public function widget( $args, $instance ) {
		extract( $args );
		extract( $instance );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$show_own_search = isset($show_own_search) ? $show_own_search : "off";
		
		echo $before_widget;
		echo ! empty( $title ) ? $before_title . $title . $after_title : '';		

		if ( $show_own_search == "on" ) { ?>
			<form action="" method="get">
				<input type="text" name="s" class="<?php echo $this->id_base; ?>-search" />
				<input type="submit" value="<?php _e("Search"); ?>" />
			</form>
		<?php } ?>				
		<div class="<?php echo $this->id_base; ?>-results"></div>
		<img src="<?php echo plugins_url('/loader.gif' , __FILE__ ); ?>" alt="loading..." class="<?php echo $this->id_base; ?>-loader" />
		<?php
		echo $after_widget;
	}

	function dynsw_search( $search = "") {
		$option = get_option('widget_dynsw');
		$option = $option[$this->number];		
		extract($option);				
		global $wpdb;
		$select = "SELECT DISTINCT $wpdb->posts.*";
		$from = " FROM $wpdb->posts";
		$where = " WHERE $wpdb->posts.post_status = 'publish' 
			AND $wpdb->posts.post_date < NOW()
			AND $wpdb->posts.post_type = '{$search_type}'";

		$wheresearch = array();
		if ( $search_post_vals == "on" ) {
			$wheresearch[] = "$wpdb->posts.post_title LIKE '%{$search}%'";
			$wheresearch[] = "$wpdb->posts.post_content LIKE '%{$search}%'";
		}
		if ( $search_cats == "on" || $search_tags == "on" ) {
			$from .= " LEFT JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
				LEFT JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
				LEFT JOIN $wpdb->terms ON ($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)";

			$wheresearch[] = "$wpdb->terms.name LIKE '%{$search}%'";

			if ( $search_cats == "on" && $search_tags == "on" ) {
				$where .= " AND ($wpdb->term_taxonomy.taxonomy = 'category' OR $wpdb->term_taxonomy.taxonomy = 'post_tag')";
			}  else {
				$where .= ($search_cats == "on") ? " AND $wpdb->term_taxonomy.taxonomy = 'category'" : " AND $wpdb->term_taxonomy.taxonomy = 'post_tag'";
			}
		}
		if ( ! empty($wheresearch) ) {
			$where .= " AND (" . implode(" OR ", $wheresearch) . ")";
		}

		$querystr = $select . $from . $where . " ORDER BY $wpdb->posts.post_date DESC LIMIT 10";
		$pageposts = $wpdb->get_results($querystr, OBJECT);

		echo "<ul>";
		if ($pageposts) {
			global $post;
			foreach ($pageposts as $post) {
				setup_postdata($post); ?>
				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>				
			<?php }
		} else {
			echo '<li><i>' . __('No results found.' , 'dynsw') . '</i></li>';
		}
		echo "</ul>";
	}

	function ajax_search_similars() {
		$this->dynsw_search($_POST['data']);
		die();	
	}	
} // class Foo_Widge

add_action( 'widgets_init', create_function( '', 'register_widget( "dynamic_search_widget" );' ) );

?>
