<?php

namespace DeliciousBrains\WPPostSeries\Admin;

use DeliciousBrains\WPPostSeries\Post as FrontPost;

class Post {

	/**
	 * @var FrontPost
	 */
	protected $post;

	/**
	 * Post constructor.
	 */
	public function __construct( FrontPost $post ) {
		$this->post = $post;
	}

	public function init() {
		add_filter( 'manage_edit-post_columns', array( $this, 'columns' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_action( 'restrict_manage_posts', array( $this, 'posts_in_series' ) );
	}

	/**
	 * Output admin column headers
	 *
	 * @param  array $columns existing columns
	 *
	 * @return array new columns
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$new_columns = array();
		}

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			if ( 'categories' == $key ) {
				$new_columns["post_series"] = __( "Series", "wp_post_series" );
			}
		}

		return $new_columns;
	}

	/**
	 * Output admin column values
	 *
	 * @param  string $column key for the column
	 */
	public function custom_columns( $column ) {
		global $post;

		if ( 'post_series' == $column ) {
			$current_series = $this->post->get_post_series( $post->ID );

			if ( $current_series ) {
				echo '<a href="' . esc_url( admin_url( 'edit.php?post_series=' . $current_series->slug ) ) . '">' . esc_html( $current_series->name ) . '</a>';
			} else {
				_e( 'N/A', 'delicious_brains' );
			}
		}
	}

	/**
	 * Filter posts by a particular series
	 */
	public function posts_in_series() {
		global $typenow, $wp_query;

		if ( $typenow != 'post' ) {
			return;
		}

		$current_series = isset( $_REQUEST['post_series'] ) ? sanitize_text_field( $_REQUEST['post_series'] ) : '';
		$all_series     = get_terms( 'post_series', array( 'hide_empty' => true, 'orderby' => 'name' ) );

		if ( empty( $all_series ) ) {
			return;
		}
		?>
		<select name="post_series">
			<option value=""><?php _e( 'Show all series', 'delicious_brains' ) ?></option>
			<?php foreach ( $all_series as $series ) : ?>
				<option value="<?php echo esc_attr( $series->slug ); ?>" <?php selected( $current_series, $series->slug ); ?>><?php echo esc_html( $series->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Output the list of post series and allow admin to assign to a post. Uses a select box.
	 *
	 * @param  array $post Post being edited
	 */
	public function post_series_meta_box( $post ) {
		// Get the current series for the post if set
		$current_series = $this->post->get_post_series_id( $post->ID );

		// Get list of all series and the taxonomy
		$tax        = get_taxonomy( 'post_series' );
		$all_series = get_terms( 'post_series', array( 'hide_empty' => false, 'orderby' => 'name' ) );

		?>
		<div id="taxonomy-<?php echo $tax->name; ?>" class="categorydiv">
			<label class="screen-reader-text" for="new_post_series_parent">
				<?php echo $tax->labels->parent_item_colon; ?>
			</label>
			<select name="tax_input[post_series]" style="width:100%">
				<option value="0"><?php echo '&mdash; ' . $tax->labels->parent_item . ' &mdash;'; ?></option>
				<?php foreach ( $all_series as $series ) : ?>
					<option value="<?php echo esc_attr( $series->slug ); ?>" <?php selected( $current_series, $series->term_id ); ?>><?php echo esc_html( $series->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}
}