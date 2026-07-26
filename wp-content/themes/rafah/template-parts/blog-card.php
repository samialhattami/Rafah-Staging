<?php
/**
 * Rafah — Blog article card (presentation). Expects $post_id in scope
 * (provided by rafah_blog_card() in Rafah Core).
 *
 * @package Rafah_Theme
 */

defined( 'ABSPATH' ) || exit;

$rafah_cats = get_the_category( $post_id );
$rafah_cat  = ( $rafah_cats && ! is_wp_error( $rafah_cats ) ) ? $rafah_cats[0] : null;
?>
<article class="rafah-blog-card rafah-fade-up">
	<a class="rafah-blog-card__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $post_id, 'rafah-card', array( 'loading' => 'lazy' ) ); ?>
		<?php endif; ?>
	</a>
	<div class="rafah-blog-card__body">
		<?php if ( $rafah_cat ) : ?>
			<a class="rafah-blog-card__cat" href="<?php echo esc_url( get_category_link( $rafah_cat ) ); ?>"><?php echo esc_html( $rafah_cat->name ); ?></a>
		<?php endif; ?>
		<h3 class="rafah-blog-card__title">
			<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
		</h3>
		<p class="rafah-blog-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 20 ) ); ?></p>
		<div class="rafah-blog-card__foot">
			<span class="rafah-blog-card__date">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
				<?php echo esc_html( get_the_date( '', $post_id ) ); ?>
			</span>
			<a class="rafah-blog-card__more" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( rafah_text( 'read_more' ) ); ?></a>
		</div>
	</div>
</article>
