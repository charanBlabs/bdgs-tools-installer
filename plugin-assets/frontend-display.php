<?php
// ============================================
// LOGIC LAYER
// ============================================

// Early return if no items to display
if (empty($post['like_list'])) {
	return;
}

// Parse like_list and separate into post_ids
$like_items = array_map('trim', explode(',', $post['like_list']));
$post_ids = array();

foreach ($like_items as $item) {
	$id = (int) $item;
	if ($id > 0) {
		$post_ids[] = $id;
	}
}

// Fetch related posts
$like_post_data = false;
if (!empty($post_ids)) {
	$post_ids_safe = implode(',', array_map('intval', $post_ids));
	$like_post_data = mysql(
		brilliantDirectories::getDatabaseConfiguration('database'),
		"SELECT * FROM `data_posts` WHERE `post_id` IN ($post_ids_safe) ORDER BY FIELD(`post_id`, $post_ids_safe)"
	);
}

// Check if there's any data to display
$has_posts = $like_post_data && mysql_num_rows($like_post_data) > 0;

if (!$has_posts) {
	return;
}

// Determine section header based on data category
if ($post['data_id'] == 68) {
	$section_title = 'Related Legal Guides';
} else if ($post['data_id'] == 4) {
	$section_title = 'Articles You Might Find Useful';
} else {
	$section_title = 'You May Also Like';
}

// ============================================
// PRESENTATION LAYER
// ============================================
?>


<div class="module bd-yaml-related-articles-container">
	<div class="bd-yaml-related-header">
		<h3><?php echo $section_title; ?></h3>
	</div>

	<div class="bd-yaml-grid">
		<?php
		// Display related posts
		if ($has_posts) {
			while ($data_title = mysql_fetch_assoc($like_post_data)) {
				// Format the date
				$formatted_date = '';
				if (!empty($data_title['post_live_date']) && strlen($data_title['post_live_date']) >= 8) {
					$formatted_date = date("M d, Y", strtotime($data_title['post_live_date']));
				}

				// Determine image source
				$img_src = !empty($data_title['post_image']) ? $data_title['post_image'] : '/images/default-article.jpg';
				$post_title = htmlspecialchars($data_title['post_title']);
				$post_author = $data_title['post_author'];
		?>
			<div class="bd-yaml-post-wrapper">
				<a href="/<?php echo $data_title['post_filename']; ?>" class="bd-yaml-post" title="<?php echo $post_title; ?>">
					<div class="bd-yaml-thumb">
						<?php if (!empty($data_title['post_image'])) { ?>
							<img src="<?php echo $img_src; ?>" alt="<?php echo $post_title; ?>" onerror="this.style.display='none'">
						<?php } else { ?>
							<div style="width:100%; height:100%; background:#eee; display:flex; align-items:center; justify-content:center; color:#ccc; font-size:20px;">
								<span class="fa fa-file-text"></span>
							</div>
						<?php } ?>
					</div>

					<div class="bd-yaml-content">
						<h4 class="bd-yaml-post-title"><?php echo $post_title; ?></h4>

						<div class="bd-yaml-meta">
							<?php if ($formatted_date) { ?>
								<span class="bd-yaml-date"><?php echo $formatted_date; ?></span>
							<?php } ?>

							<?php if ($formatted_date && $post_author) { ?>
								<i>|</i>
							<?php } ?>

							<?php if ($post_author) { ?>
								<span class="bd-yaml-author">By <?php echo $post_author; ?></span>
							<?php } ?>
						</div>
					</div>
				</a>
			</div>

		<?php
			}
		}
		?>
	</div>
	<div style="clear:both;"></div>
</div>
