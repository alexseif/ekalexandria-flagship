<?php
/**
 * Title: Science Museum Gallery
 * Slug: ekalexandria-flagship/gallery-science-museum
 * Categories: gallery
 */
?>
<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
<?php
$image_ids = array(7813, 7814, 7815);
foreach ( $image_ids as $id ) {
	echo '<!-- wp:image {"id":' . $id . ',"sizeSlug":"large","linkDestination":"none"} -->';
	echo '<figure class="wp-block-image size-large"><img class="wp-image-' . $id . '"/></figure>';
	echo '<!-- /wp:image -->';
}
?>
</figure>
<!-- /wp:gallery -->
