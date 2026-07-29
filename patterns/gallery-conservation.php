<?php
/**
 * Title: Conservation Gallery
 * Slug: ekalexandria-flagship/gallery-conservation
 * Categories: gallery
 */
?>
<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
<?php
$image_ids = array(7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942);
foreach ( $image_ids as $id ) {
	echo '<!-- wp:image {"id":' . $id . ',"sizeSlug":"large","linkDestination":"none"} -->';
	echo '<figure class="wp-block-image size-large"><img class="wp-image-' . $id . '"/></figure>';
	echo '<!-- /wp:image -->';
}
?>
</figure>
<!-- /wp:gallery -->
