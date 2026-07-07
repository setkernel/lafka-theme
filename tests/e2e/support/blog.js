/* lafka-theme/tests/e2e/support/blog.js
 *
 * Deterministic blog-content seeder for the NX1-10a VISUAL PARITY harness
 * (tests/visual/nx1-10a.spec.js). The demo store seeder (NX1-09a,
 * `wp lafka seed-demo`) creates the WooCommerce catalogue but ships NO blog
 * content, so the legacy monolith surfaces this wave retires — the classic
 * blog index / category archive / search results (content.php + index.php /
 * archive.php / search.php) and single-post comments (comments.php) — have
 * nothing to render against. This helper adds a fixed, idempotent set of posts,
 * a category, comments, and the static-front-page wiring those surfaces need,
 * so their goldens are reproducible run to run.
 *
 * REPRODUCIBILITY (equivalent WP-CLI, run inside the wp-env cli container):
 *   wp option update show_on_front page
 *   wp option update page_on_front  <Home page id>
 *   wp option update page_for_posts <Blog page id>
 *   wp term create category "Kitchen Notes" --slug=kitchen-notes
 *   wp post create --post_type=post --post_status=publish --post_date=... \
 *       --post_title="..." --post_name=<slug> --post_content=... \
 *       --post_excerpt=... --comment_status=open   (x3, category assigned)
 *   wp comment create --comment_post_ID=<post1> --comment_approved=1 ...  (x2)
 * The single wp-eval below performs all of the above atomically + idempotently.
 *
 * Everything is FIXED: slugs, post_date (so the date-based permalinks are
 * stable), content, excerpts, and comment dates/authors. Featured images reuse
 * the demo catalogue's own attachments (resolved by product slug, since raw
 * attachment ids are not stable across a wipe/reseed). Dynamic date/time text
 * and network-loaded avatars are MASKED by the spec.
 *
 * @since lafka-theme 6.21.0 (NX1-10a monolith teardown safety net)
 */
const { wpCli } = require( './wp-cli' );

/** Stable identifiers the spec addresses seeded blog content by. */
const BLOG = {
	categorySlug: 'kitchen-notes',
	categoryName: 'Kitchen Notes',
	blogPageSlug: 'blog',
	// Newest first — the blog index / archive render reverse-chronologically.
	posts: {
		longform: 'art-of-neapolitan-dough', // long-form + image + comments.
		oven: 'behind-our-wood-fired-oven',
		pairings: 'five-topping-pairings-we-love',
	},
	searchTerm: 'dough', // present in every seeded post → multi-result search.
};

// Long-form post body: headings, lists, blockquote and an inline image so the
// legacy typography / .entry-content rules are all exercised on one surface.
// NB: no `${` sequences below — this is a template literal, and a bare `$php`
// token is literal text; only `${...}` would interpolate. PHP interpolation
// uses the `{$var}` form throughout to stay clear of it.
const PHP = `
$blog_cat_name = "Kitchen Notes";
$blog_cat_slug = "kitchen-notes";
$cat_id = 0;
$existing_cat = get_term_by( "slug", $blog_cat_slug, "category" );
if ( $existing_cat ) {
	$cat_id = (int) $existing_cat->term_id;
} else {
	$new_cat = wp_insert_term( $blog_cat_name, "category", array( "slug" => $blog_cat_slug ) );
	if ( ! is_wp_error( $new_cat ) ) {
		$cat_id = (int) $new_cat["term_id"];
	}
}

$ensure_page = function( $slug, $title ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		return (int) $p->ID;
	}
	return (int) wp_insert_post( array(
		"post_title"   => $title,
		"post_name"    => $slug,
		"post_status"  => "publish",
		"post_type"    => "page",
		"post_content" => "",
	) );
};
$home_id = $ensure_page( "home", "Home" );
$blog_id = $ensure_page( "blog", "Blog" );
update_option( "show_on_front", "page" );
update_option( "page_on_front", $home_id );
update_option( "page_for_posts", $blog_id );

$thumb_for = function( $product_slug ) {
	$p = get_page_by_path( $product_slug, OBJECT, "product" );
	if ( ! $p ) {
		return 0;
	}
	return (int) get_post_thumbnail_id( $p->ID );
};

$longform_body = "<p>Great pizza starts with great dough. In our kitchen the dough is the quiet protagonist &mdash; a living thing we feed, fold, and rest for the better part of two days before it ever meets the fire.</p>\n"
	. "<h2>Why slow fermentation matters</h2>\n"
	. "<p>A long, cold ferment builds flavour that a quick rise simply cannot. The dough relaxes, the gluten organises itself, and the crust bakes up light with those blistered, leopard-spotted edges.</p>\n"
	. "<h3>Our three non-negotiables</h3>\n"
	. "<ul>\n<li>Type 00 flour, milled fine.</li>\n<li>A patient 48-hour cold proof.</li>\n<li>A screaming-hot oven floor.</li>\n</ul>\n"
	. "<h3>The method, step by step</h3>\n"
	. "<ol>\n<li>Autolyse the flour and water.</li>\n<li>Add the levain and salt.</li>\n<li>Bulk ferment, then ball.</li>\n<li>Cold-proof, then bake.</li>\n</ol>\n"
	. "<blockquote><p>Good dough cannot be hurried &mdash; it can only be understood.</p></blockquote>\n"
	. "<h2>From bench to fire</h2>\n"
	. "<p>When a ball of dough has proofed just right it is soft, airy, and eager. We stretch it by hand, dress it simply, and slide it onto the stone. Ninety seconds later, dinner.</p>\n"
	. "<p>That is the whole secret: respect the dough and it will reward you every single time.</p>";

$oven_body = "<p>The heart of the restaurant is a wood-fired oven we built by hand. It reaches temperatures a home oven can only dream of, and it is what gives every pizza and every baked dish its character.</p>\n"
	. "<h2>Seasoned oak, nothing else</h2>\n"
	. "<p>We burn only seasoned oak. It is clean, it is hot, and it lends a gentle smokiness to the dough that you can taste in the very first bite.</p>\n"
	. "<p>Come in on a cold evening and you will find the oven glowing &mdash; the best seat in the house is the one nearest the fire.</p>";

$pairings_body = "<p>Toppings are where personality shows up. Here are five pairings we keep coming back to, each one designed to sit lightly on our dough without overwhelming it.</p>\n"
	. "<ol>\n<li>Fresh mozzarella and basil.</li>\n<li>Fennel sausage and chilli.</li>\n<li>Mushroom and thyme.</li>\n<li>Prosciutto and rocket.</li>\n<li>Roasted pepper and olive.</li>\n</ol>\n"
	. "<p>Whatever you choose, remember the dough is doing half the work.</p>";

$posts = array(
	array(
		"slug"    => "art-of-neapolitan-dough",
		"title"   => "The Art of Neapolitan Dough",
		"date"    => "2026-06-15 09:00:00",
		"excerpt" => "Why we let our dough rest for two full days before it ever meets the fire.",
		"body"    => $longform_body,
		"product" => "margherita-pizza",
	),
	array(
		"slug"    => "behind-our-wood-fired-oven",
		"title"   => "Behind Our Wood-Fired Oven",
		"date"    => "2026-06-01 09:00:00",
		"excerpt" => "Seasoned oak, a hand-built hearth, and the dough that loves it.",
		"body"    => $oven_body,
		"product" => "garlic-bread",
	),
	array(
		"slug"    => "five-topping-pairings-we-love",
		"title"   => "Five Topping Pairings We Love",
		"date"    => "2026-05-20 09:00:00",
		"excerpt" => "Five combinations that sit lightly on a well-made dough.",
		"body"    => $pairings_body,
		"product" => "greek-salad",
	),
);

// Deterministic index: drop any post NOT in the seeded set (e.g. the default
// "Hello world!" whose install-date and stock comment would otherwise churn the
// blog index / search goldens). Throwaway env — benign.
$keep_slugs = array( "art-of-neapolitan-dough", "behind-our-wood-fired-oven", "five-topping-pairings-we-love" );
foreach ( get_posts( array( "post_type" => "post", "post_status" => "any", "numberposts" => -1, "fields" => "ids" ) ) as $existing_id ) {
	$existing_post = get_post( $existing_id );
	if ( $existing_post && ! in_array( $existing_post->post_name, $keep_slugs, true ) ) {
		wp_delete_post( $existing_id, true );
	}
}

$ids = array();
foreach ( $posts as $spec ) {
	$existing = get_page_by_path( $spec["slug"], OBJECT, "post" );
	$data = array(
		"post_title"    => $spec["title"],
		"post_name"     => $spec["slug"],
		"post_content"  => $spec["body"],
		"post_excerpt"  => $spec["excerpt"],
		"post_status"   => "publish",
		"post_type"     => "post",
		"post_date"     => $spec["date"],
		"post_date_gmt" => $spec["date"],
		"comment_status" => "open",
	);
	if ( $existing ) {
		$data["ID"] = $existing->ID;
		$pid = (int) wp_update_post( $data );
	} else {
		$pid = (int) wp_insert_post( $data );
	}
	if ( $pid && $cat_id ) {
		wp_set_post_terms( $pid, array( $cat_id ), "category" );
	}
	$tid = $thumb_for( $spec["product"] );
	if ( $pid && $tid ) {
		set_post_thumbnail( $pid, $tid );
	}
	$ids[ $spec["slug"] ] = $pid;
}

// Comments on the long-form post: reset then insert two fixed, approved ones.
$comment_post = isset( $ids["art-of-neapolitan-dough"] ) ? $ids["art-of-neapolitan-dough"] : 0;
if ( $comment_post ) {
	foreach ( get_comments( array( "post_id" => $comment_post, "fields" => "ids" ) ) as $cid ) {
		wp_delete_comment( $cid, true );
	}
	$comments = array(
		array( "author" => "Mara Whitfield", "email" => "mara@example.com", "date" => "2026-06-16 10:30:00", "text" => "This is exactly why your crust tastes the way it does. The 48-hour proof is worth every minute." ),
		array( "author" => "Tomas Reed", "email" => "tomas@example.com", "date" => "2026-06-17 14:05:00", "text" => "Tried the oak-fired method at home and the smokiness really does come through. Thank you for sharing the steps." ),
	);
	foreach ( $comments as $c ) {
		wp_insert_comment( array(
			"comment_post_ID"      => $comment_post,
			"comment_author"       => $c["author"],
			"comment_author_email" => $c["email"],
			"comment_content"      => $c["text"],
			"comment_date"         => $c["date"],
			"comment_date_gmt"     => $c["date"],
			"comment_approved"     => 1,
			"comment_type"         => "comment",
		) );
	}
	wp_update_comment_count( $comment_post );
}

echo "seedBlog: category={$cat_id} posts=" . implode( ",", $ids ) . " comments_on={$comment_post}";
`;

/**
 * Seed the deterministic blog fixture. Idempotent; safe to run before every
 * visual suite (called from tests/visual/support/global-setup.js after the
 * store is prepared, so the reused product attachments already exist).
 */
function seedBlog() {
	wpCli( [ 'eval', PHP ] );
}

/**
 * Return the pretty permalink for a seeded post slug (date-based, so it must be
 * resolved at run time rather than constructed).
 *
 * @param {string} slug Post slug, e.g. BLOG.posts.longform.
 * @return {string} Absolute path (e.g. /2026/06/15/art-of-neapolitan-dough/).
 */
function blogPostPath( slug ) {
	const url = wpCli( [
		'eval',
		'$p=get_page_by_path("' + slug + '",OBJECT,"post");echo $p?wp_make_link_relative(get_permalink($p->ID)):"";',
	] );
	return url || '/';
}

/**
 * Restore the env to the show_on_front=posts baseline this harness found. The
 * created Home/Blog pages + posts are left in place (idempotently reused next
 * run); only the front-page mode is reverted so the throwaway env is returned
 * to how the suite found it.
 */
function restoreFrontPage() {
	wpCli( [ 'option', 'update', 'show_on_front', 'posts' ] );
}

module.exports = { BLOG, seedBlog, blogPostPath, restoreFrontPage };
