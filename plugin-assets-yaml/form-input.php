<?php
// ============================================
// LOGIC LAYER
// ============================================

// Determine category ID based on page type
$ymal_id = null;
if ($pars[1] == 'legal-guides') {
	$ymal_id = 68;
} else if ($pars[1] == 'articles') {
	$ymal_id = 4;
}

// Fetch posts for the current category
$post_data = mysql(
	brilliantDirectories::getDatabaseConfiguration('database'),
	"SELECT * FROM `data_posts` WHERE post_status='1' AND data_id=$ymal_id"
);

// Extract selected items from post data
$like_list = isset($post['like_list']) ? explode(",", $post['like_list']) : array();

// Prepare label text based on category
$label_text = ($pars[1] == 'legal-guides') ? 'Related Legal Guides' : 'Articles You Might Find Useful';

// ============================================
// PRESENTATION LAYER
// ============================================
?>

<div class="form-group">
	<label class="vertical-label">
		<span><?php echo $label_text; ?></span>
	</label>

	<div class="scrollbox">
		<?php while ($data_info = mysql_fetch_assoc($post_data)) { 
			// Fetch category information for this post
			$post_categories = mysql(
				brilliantDirectories::getDatabaseConfiguration('database'),
				"SELECT * FROM `data_categories` WHERE data_id='" . (int)$data_info['data_id'] . "'"
			);
			$data_name = mysql_fetch_assoc($post_categories);
			
			// Check if this item is already selected
			$is_checked = in_array($data_info['post_id'], $like_list);
		?>
			<input 
				name="like_list[]" 
				value="<?php echo (int)$data_info['post_id']; ?>" 
				type="checkbox"
				<?php echo $is_checked ? 'checked' : ''; ?> 
			/>
			<?php echo htmlspecialchars($data_name['data_name'] . ' - ' . $data_info['post_title']); ?>
			<br>
		<?php } ?>
	</div>
</div>
