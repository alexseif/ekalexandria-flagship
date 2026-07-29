<?php
/**
 * Title: Cemeteries Gallery
 * Slug: ekalexandria-flagship/gallery-cemeteries
 * Categories: gallery
 */
?>
<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
<?php
$image_ids = array(10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673);
foreach ( $image_ids as $id ) {
	echo '<!-- wp:image {"id":' . $id . ',"sizeSlug":"large","linkDestination":"none"} -->';
	echo '<figure class="wp-block-image size-large"><img class="wp-image-' . $id . '"/></figure>';
	echo '<!-- /wp:image -->';
}
?>
</figure>
<!-- /wp:gallery -->
