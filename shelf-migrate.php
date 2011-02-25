<?php
/*
Plugin Name: Shelf Migrate
Plugin URI: 
Description: 
Version: 0.1
Author: 
Author URI: 
*/

/*  Copyright 2010  StatikPulse  (email : yan@statikpulse.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ShelfMigrate {
	
	function __construct() {
		if( is_admin() ) {
			add_action('admin_menu', array(&$this, 'adminMenu'));
			add_filter( 'plugin_row_meta', array( &$this, 'links' ), 10, 2) ;
		}
	}
	
	function links( $links, $file ) {
		if( $file == 'shelf-migrate/shelf-migrate.php') {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=shelf-migrate' ) . '">' . __('Settings') . '</a>';
		}
		return $links;
	}
	
	function adminMenu() {
     add_options_page( 'Shelf Migrate', 'Shelf Migrate', 'administrator', 'shelf-migrate', array( &$this, 'adminPage' ) );
  }

  function adminPage() {
		if (version_compare(get_bloginfo( 'version' ), '3.1', '>=')) {
			if ( ! empty( $_POST['migrate-shelf'] ) ) {
				$this->migrate_post( 'quotes', 'quote' );
				$this->migrate_post( 'links', 'link' );
				$this->migrate_post( 'video', 'video' );
				$this->migrate_post( 'images', 'image' );
				$this->migrate_post( 'audio', 'audio' );
			} else {
			?>
				<form method="post" action="">
				<?php wp_nonce_field('migrate-shelf') ?>

					<p><?php printf( __( "Use this tool to migrate Shelf to use WordPress 3.1", 'shelf-migrate' ), admin_url( 'options-media.php' ) ); ?></p>

					<p><?php _e( 'To begin, just press the button below.', 'shelf-migrate'); ?></p>

					<p><input type="submit" class="button" name="migrate-shelf" id="migrate-shelf" value="<?php _e( 'Migrate Shelf', 'shelf-migrate' ) ?>" /></p>

				</form>
			<?php
			}
		} else {
			?>
			<p>This script requires WordPress 3.1 or higher. Please upgrade WordPress.</p>
			<?php
		}
  }

	function migrate_post( $tumblog_slug, $post_format_slug ) {
		echo "Migrating ".$tumblog_slug."...";
		$posts = query_posts( array( "tumblog" => $tumblog_slug ) );
		
		foreach( $posts as $post) {
			switch( $post_format_slug ) {
				case 'quote':
					$quote_url = get_post_meta( $post->ID, 'quote-url', true );
					$content = '<blockquote>';
					if( $this->is_valid_url( $quote_url ) ){
						$content .= '<a href="'.$quote_url.'">'.get_post_meta( $post->ID, 'quote-copy', true ).'</a>';
					} else {
						$content .= get_post_meta( $post->ID, 'quote-copy', true );
					}
					$content .= '</blockquote>';
					break;
				case 'link':
					$content = '<a href="'.get_post_meta( $post->ID, 'link-url', true ).'">'.$post->post_title.'</a>';
					break;
				case 'video':
					$content = get_post_meta( $post->ID, 'video-embed', true );
					$content .= $post->post_content;
					break;
				case 'image':
					$content = $post->post_content;
					break;
				case 'audio':
					$content = $post->post_content;
					break;
			}
			$this->update_post( $post, $content, $post_format_slug );
		}
		echo "OK<br />";
	}
	
	function update_post( $post, $content, $format ) {
		global $wpdb;
		$wpdb->query('UPDATE '.$wpdb->posts.' SET post_content = "'.mysql_real_escape_string($content).'" WHERE ID = '.$post->ID);
		
		if ( !has_post_format( $format )) {
			set_post_format( $post, $format );
		}
	}
	
	function is_valid_url( $url ){
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
	}
	
}

$migration = new ShelfMigrate();


?>