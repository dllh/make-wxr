<?php 

class WXR_Generator extends WP_CLI_Command {

	// For setting minimum tag and category ids (not essential most of the time and not something that really needs exposing as an overrideable option probably.
	private $tag_min_id = 1;
	private $cat_min_id = 1;

	// Not an option we'll override, as it's just used to cache generated slugs and names.
	private $term_prefixes = array();

	// For storing things as we generate the lists at the top so that we can then reference them within posts.
	private $authors = array();
	private $categories = array();
	private $tags = array();

	/**
	 * Generate a WXR file with dummy data with optional parameters. This is good for making dummy data
	 * for site testing or for benchmarking in cases in which you want a site with a predetermined 
	 * number of posts, categories, comments, etc.
	 * 
	 * @synopsis [--site_title=<string>] [--site_url=<url>] [--post_count=<int>] [--comments_per_post=<int>] [--tag_count=<int>] [--cat_count=<int>] [--author_count=<int>] [--nest_comments=<true|false>]
	 */
	public function __invoke( $_, $assoc_args ) {

		$defaults = array(
			'site_title'        => 'Just Another WordPress Site',
			'site_url'          => 'http://wordpress.org/',
			'post_count'        => 10,
			'comments_per_post' => 2,
			'tag_count'         => 3,
			'cat_count'         => 3,
			'tags_per_post'     => 5,
			'cats_per_post'     => 5,
			'author_count'      => 1,
			'nest_comments'     => false,
		);

		$args = wp_parse_args( $assoc_args, $defaults );

		$has_errors = false;

		foreach ( $args as $key => $value ) {
			if ( is_callable( array( $this, 'check_' . $key ) ) ) {
				$result = call_user_func( array( $this, 'check_' . $key ), $value );
				if ( false === $result )
					$has_errors = true;
			}
		}


		if ( $has_errors ){
			WP_CLI::error( "Unable to proceed." );
			exit( 1 );
		}

		$this->echo_wxr();
	}

	private function check_nest_comments( $val ) {
		$this->nest_comments = 'true' == $val;
		return true;
	}

	private function check_site_title( $title ) {
		// Not really much to validate here. Just need to set the var.
		$this->site_title = $title;
		return true;
	}

	private function check_site_url( $url ) {
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			WP_CLI::warning( 'Site url should be a url.' );
			return false;
		}

		$this->site_url = $url;
		return true;
	}

	private function check_post_count( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Post count should be numeric.' );
			return false;
		}

		$this->post_count = $num;
		return true;
	}

	private function check_comments_per_post( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Comments per post count should be numeric.' );
			return false;
		}

		$this->comments_per_post = $num;
		return true;
	}

	private function check_tag_count( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Tag count should be numeric.' );
			return false;
		}

		$this->tag_count = $num;
		return true;
	}

	private function check_cat_count( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Category count should be numeric.' );
			return false;
		}

		$this->cat_count = $num;
		return true;
	}

	private function check_cats_per_post( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Category count per post should be numeric.' );
			return false;
		}

		$this->cats_per_post = $num;
		return true;
	}

	private function check_tags_per_post( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Tag count per post should be numeric.' );
			return false;
		}

		$this->tags_per_post = $num;
		return true;
	}

	private function check_author_count( $num ) {
		if ( ! is_numeric( $num ) ) {
			WP_CLI::warning( 'Author count should be numeric.' );
			return false;
		}

		$this->author_count = $num;
		return true;
	}

	private function init() {
		// Values we can override.
		$this->site_title = 'Just another WordPress site';
		$this->site_url = 'http://wordpress.org/';
		$this->post_count = 10;
		$this->comments_per_post = 2;
		$this->tag_count = 3;
		$this->cat_count = 3;
		$this->author_count = 1;
		$this->nest_comments = false;
		
	}

	private function generate_authors() {
		for ( $i = 1; $i <= $this->author_count; $i++ ) {
				$this->authors[] = 'author' . $i;
			?>
				<wp:author><wp:author_id><?php echo (int) $i; ?></wp:author_id><wp:author_login><?php echo esc_html( 'author' . $i ); ?></wp:author_login><wp:author_email></wp:author_email><wp:author_display_name><![CDATA[<?php echo esc_html( 'Author ' . $i ); ?>]]></wp:author_display_name><wp:author_first_name><![CDATA[]]></wp:author_first_name><wp:author_last_name><![CDATA[]]></wp:author_last_name></wp:author>
			<?php
		}	
	}

	private function generate_categories() {
		for ( $i = $this->cat_min_id; $i < ( $this->cat_min_id + $this->cat_count ); $i++ ) {
				$this->categories[ $i ] = $i;
			?>
				<wp:category><wp:term_id><?php echo (int) $i; ?></wp:term_id><wp:category_nicename><?php echo esc_html( $this->get_term_slug( 'category', $i ) ); ?></wp:category_nicename><wp:category_parent></wp:category_parent><wp:cat_name><![CDATA[<?php echo esc_html( $this->get_term_name( 'category', $i ) ); ?>]]></wp:cat_name></wp:category>
			<?php
		}
	}

	private function generate_tags() {
		for ( $i = $this->tag_min_id; $i < ( $this->tag_min_id + $this->tag_count ); $i++ ) {
				$this->tags[ $i ] = $i;
			?>
				<wp:term><wp:term_id><?php echo (int) $i; ?></wp:term_id><wp:term_taxonomy>post_tag</wp:term_taxonomy><wp:term_slug><?php echo esc_html( $this->get_term_slug( 'tag', $i ) ); ?></wp:term_slug><wp:term_name><![CDATA[<?php echo esc_html( $this->get_term_name( 'tag', $i ) ); ?>]]></wp:term_name></wp:term>
			<?php
		}
	}

	// Generate a randomish string for inclusion in tag and category slugs and names.
	private function get_random_term_prefix( $taxonomy, $id ) {
		if ( ! array_key_exists( $taxonomy . '-' . $id, $this->term_prefixes ) ) {
			$randomish_string = $taxonomy . $id . mt_rand();
			$randomish_string = md5( $randomish_string );
			$randomish_string = str_shuffle( $randomish_string );
			$randomish_string = substr( $randomish_string, 0, 10 );

			$this->term_prefixes[ $taxonomy . '-' . $id ] = $randomish_string;
		}

		return $this->term_prefixes[ $taxonomy . '-' . $id ];

	}

	// Helper function because we want to be consistent in this in both the category listing near the top of the wxr and in the per-post categories.
	private function get_term_name( $taxonomy, $id ) {
		$prefix = $this->get_random_term_prefix( $taxonomy, $id );
		return ucfirst( $taxonomy ) . ' ' . $prefix . ' ' . $id;
	}

	// Helper function because we want to be consistent in this in both the category listing near the top of the wxr and in the per-post categories.
	private function get_term_slug( $taxonomy, $id ) {
		$prefix = $this->get_random_term_prefix( $taxonomy, $id );
		return $taxonomy . '-' . $prefix . '-' . $id;
	}

	private function generate_posts() {

		$running_comment_count = 0;

		for ( $i = 1; $i <= $this->post_count; $i++ ) {
			$now = time();
			$timestamp = rand( $now - ( 60 * DAY_IN_SECONDS ), $now );
			$slug_date = @date( 'Y/m', $timestamp );

			?>
			<item>
				<title>Post Number <?php echo $i; ?></title>
				<link><?php echo esc_url( trailingslashit( $this->site_url ) . $slug_date . '/post-number-' . (int) $i . '/' ); ?></link>
				<pubDate><?php echo @date( 'r', $timestamp ); ?></pubDate>
				<dc:creator><?php echo esc_html( $this->authors[ array_rand( $this->authors ) ] ); ?></dc:creator>
				<guid isPermaLink="false"><?php echo esc_url( add_query_arg( $this->site_url, 'p', (int) $i ) ); ?></guid>
				<description></description>
				<content:encoded><![CDATA[<?php echo esc_html( $this->get_random_text() ); ?>]]></content:encoded>
				<excerpt:encoded><![CDATA[]]></excerpt:encoded>
				<wp:post_id><?php echo (int) $i; ?></wp:post_id>
				<wp:post_date><?php echo @date( 'Y-m-d H:i:s', $timestamp ); ?></wp:post_date>
				<wp:post_date_gmt><?php echo gmdate( 'Y-m-d H:i:s', $timestamp ); ?></wp:post_date_gmt>
				<wp:comment_status>open</wp:comment_status>
				<wp:ping_status>open</wp:ping_status>
				<wp:post_name>post-number-<?php echo (int) $i; ?></wp:post_name>
				<wp:status>publish</wp:status>
				<wp:post_parent>0</wp:post_parent>
				<wp:menu_order>0</wp:menu_order>
				<wp:post_type>post</wp:post_type>
				<wp:post_password></wp:post_password>
				<wp:is_sticky>0</wp:is_sticky>

				<?php
					$category_keys = array_rand( $this->categories, min( count( $this->categories ), $this->cats_per_post ) );
					foreach ( $category_keys as $category_key => $category_id ) {
				?>
						<category domain="category" nicename="<?php echo esc_attr( $this->get_term_slug( 'category', $category_id ) ); ?>"><![CDATA[<?php echo esc_html( $this->get_term_name( 'category', $category_id ) ); ?>]]></category>
				
				<?php	
					}
				?>

				<?php
					$tag_keys = array_rand( $this->tags, min( count( $this->tags ), $this->tags_per_post ) );
					foreach ( $tag_keys as $tag_key => $tag_id ) {
				?>
						<category domain="post_tag" nicename="<?php echo esc_attr( $this->get_term_slug( 'tag', $tag_id ) ); ?>"><![CDATA[<?php echo esc_html( $this->get_term_name( 'tag', $tag_id ) ); ?>]]></category>
				
				<?php	
					}
				?>

				<?php
					for ( $j = $this->comments_per_post; $j > 0; $j-- ) {
						$comment_timestamp = rand( $timestamp, $now );
						$running_comment_count++;
						$comment_parent = 0;
						if ( true === $this->nest_comments ) {
							$comment_parent = rand( 1, $j );
						}
				?>
						<wp:comment>
							<wp:comment_id><?php echo (int) $running_comment_count; ?></wp:comment_id>
							<wp:comment_author><![CDATA[<?php echo esc_html( $this->authors[ array_rand( $this->authors ) ] ); ?>]]></wp:comment_author>
							<wp:comment_author_email></wp:comment_author_email>
							<wp:comment_author_url><?php echo esc_url( $this->site_url ); ?></wp:comment_author_url>
							<wp:comment_author_IP></wp:comment_author_IP>
							<wp:comment_date><?php echo @date( 'Y-m-d H:i:s', $comment_timestamp ); ?></wp:comment_date>
							<wp:comment_date_gmt><?php echo gmdate( 'Y-m-d H:i:s', $comment_timestamp ); ?></wp:comment_date_gmt>
							<wp:comment_content><![CDATA[<?php echo esc_html( $this->get_random_text() ); ?>]]></wp:comment_content>
							<wp:comment_approved>1</wp:comment_approved>
							<wp:comment_type></wp:comment_type>
							<wp:comment_parent><?php echo (int) $comment_parent; ?></wp:comment_parent>
							<wp:comment_user_id>0</wp:comment_user_id>
						</wp:comment>
				<?php } ?>

			</item>
			<?php

		}
	}

	private function get_random_text() {
		$words = 'lorem ipsum dolor sit amet consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';
		$words = explode( ' ', $words );

		$out = array();

		$sentence_count = rand( 1, 7 );

		for ( $i = 1; $i <= $sentence_count; $i++ ) {
			shuffle( $words );
			$words_in_sentence = array_slice( $words, 0, rand( 5, count( $words ) ) );
			$out[] = ucfirst( array_pop( $words_in_sentence ) ) . ' ' . implode( ' ', $words_in_sentence ) . '.';
		}
		return implode( "\n\n", $out );

	}

	private function echo_wxr() {
		global $wp_version;
		echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"; 

		?>
		<!-- generator="WordPress/<?php echo $wp_version; ?>" created="<?php echo date( 'Y-m-d H:i:s' );?>" -->
		<rss version="2.0"
			xmlns:excerpt="http://wordpress.org/export/1.1/excerpt/"
			xmlns:content="http://purl.org/rss/1.0/modules/content/"
			xmlns:wfw="http://wellformedweb.org/CommentAPI/"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:wp="http://wordpress.org/export/1.1/"
		>

		<channel>
			<title><?php echo esc_html( $this->site_title ); ?></title>
			<link><?php echo esc_url( $this->site_url ); ?></link>
			<description><?php echo esc_html( $this->site_title ); ?></description>
			<pubDate><?php echo date( 'r' ); ?></pubDate>
			<language>en</language>
			<wp:wxr_version>1.1</wp:wxr_version>
			<wp:base_site_url><?php echo esc_url( $this->site_url ); ?></wp:base_site_url>
			<wp:base_blog_url><?php echo esc_url( $this->site_url ); ?></wp:base_blog_url>

			<?php $this->generate_authors(); ?>

			<?php $this->generate_categories(); ?>

			<?php $this->generate_tags(); ?>

			<generator>http://wordpress.org/?v=<?php echo $wp_version; ?></generator>

			<?php $this->generate_posts(); ?>

		</channel>
</rss>
<?php
	}
}

WP_CLI::add_command( 'make-wxr', 'WXR_Generator' );
