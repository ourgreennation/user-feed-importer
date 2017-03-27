<?php
/**
 * User Feed Functions
 *
 * Creates an options page where users can set the interval of imports and the authors to
 * import the posts as.
 *
 * @package  User_Feed_Importer
 */

namespace Lift\Plugins\User_Feed_Importer;

/**
 * Override Rel Canonical on User Posts
 *
 * @param  string   $canonical_url The original canonical url.
 * @param  \WP_Post $post          A WP_Post object.
 * @return string                  Filtered canonical url.
 */
function posts_rel_canonical( $canonical_url, \WP_Post $post ) {
	if ( ! has_term( 'import', 'post_tag', $post ) ) {
		return $canonical_url;
	}

	$canonical = get_the_guid( absint( $post_id ) );
	if ( $canonical && ( false === strpos( $canonical, home_url() ) ) ) {
		$canonical_url = $canonical;
	}

	return $canonical_url;
}
add_filter( 'get_canonical_url', __NAMESPACE__ . '\\posts_rel_canonical', 10, 2 );
