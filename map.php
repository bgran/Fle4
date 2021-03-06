<?php
function knbu_get_knowledge_type_select() {
	global $knbu_kbsets;
	$value = '<select name="knbu_type">
	<option disabled selected>Select knowledge type</option>';
	foreach($knbu_kbsets[knbu_get_kbset_for_post(get_the_ID())]->KnowledgeTypeSet->KnowledgeType as $type)
		$value .= '<option value="'.$type['ID'].'">'.$type['Name'].'</option>';
	$value .= '</select>';
	return $value;
}

function knbu_get_legends() {
	global $knbu_kbsets;
	
	foreach($knbu_kbsets[knbu_get_kbset_for_post(get_the_ID())]->KnowledgeTypeSet->KnowledgeType as $type) {
		$color = $type['Colour'];
		echo '<li><span class="color" style="background-color: '.$color.'"></span>'.$type['Name'].'</li>';
	}
	echo '<li><span class="color" style="background-color: black"></span> Unspecified</li>';
}

$replies = get_comments(array(
			'status' => 'approve',
			'post_id' => get_the_ID()
			));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?php the_title(); ?> (Map) | <?php bloginfo('name'); ?></title>
<link href='http://fonts.googleapis.com/css?family=Junge' rel='stylesheet' type='text/css'>
<?php wp_head(); ?>
</head>
<body class="knbu-map-view">
	<div id="map">
		<div id="raven"></div>
		<div id="fps"></div>
		<div id="grouping">
			<ul>
				<li><a id="grouping-byknowledgetypes">Group by knowledge types</a></li>
				<li><a id="grouping-byauthors">Group by authors</a></li>
				<li><a id="grouping-discussion">Discussion</a></li>
				<li><a id="grouping-time">Time</a></li>
			</ul>
		</div>
		<div id="navigation">
			<div id="zoom"></div>
			<div id="pan">
				<div class="left"></div>
				<div class="right"></div>
				<div class="up"></div>
				<div class="down"></div>
				<div class="center"></div>
			</div>
		</div>
		<div id="legend">
		<ul>
			<?php knbu_get_legends(); ?>
		</ul>
		</div>
	</div>
	
	<div id="message">
		<div class="message-header">
			<h4 class="message-type"></h4>
		</div>
		<div class="message-content-wrapper">
			<h3 class="message-title"><span class="message-username">Username</span> <span class="message-date">6:43 pm 12th June 2013</span><div style="clear:both"></div></h3>
			
				<div class="message-meta">
					<div class="message-avatar"></div>
				</div>
			<div class="message-content">
			</div>
			<div style="clear:both"></div>
		<div class="message-coords"></div>
		<a class="reply-toggle knbu-form-link" id="open-reply">Reply</a>
		<div id="reply-wrapper">
			<form>
				<?php if(is_user_logged_in()) { ?>
					<input type="hidden" value="1" id="current_user">
					<?php } else { ?>
					<p>Your info <br/>
					<input type="hidden" value="0" id="current_user">
					<input type="text" placeholder="Name" id="current_user_name"> 
					<input type="text" placeholder="Email" id="current_user_email">
					</p>
				<?php } ?>
				<input type="hidden" value="<?php echo admin_url('admin-ajax.php'); ?>" id="admin-ajax-url">
				<input type="hidden" value="<?php echo get_the_ID(); ?>" id="post-id">
				<input type="hidden" name="parent-comment" id="parent-comment-id">
				<p>Comment <br><input type="text" id="comment-title" placeholder="Title"> <!-- Knowledge type --> <?php echo knbu_get_knowledge_type_select(); ?></p>
				<p style="clear: both"><!--Reply<br>-->
				<textarea style="width: 95%" rows="8" name="comment-content" placeholder="Reply"></textarea></p>
				<p><input type="button" value="Send" id="submit-reply" ></p>
			</form>
		</div>
		<div style="clear:both"></div>
		</div>
	</div>
		<?php
			usort($replies, 'knbu_cmp');
			knbu_get_childs(0, $replies);
		?>
	</body>
</html>
<?php
function knbu_get_childs($id, $replies) {
	global $knowledgeTypes, $knbu_kbsets, $post;
	echo '<ul '.($id == 0 ? 'id="data" style="display: none"' : '').'
	data-username="'.get_the_author_meta('display_name', $post->post_author).'" 
	data-content="'.$post->post_content.'"
	data-title="'.$post->post_title.'"
	data-avatar="'.knbu_get_avatar_url( $post->user_id ).'"
	data-username="'.get_the_author_meta( 'display_name', $post->user_id ).'"
	data-email="'.$post->user_email.'"
	data-date="'.date(get_option('date_format').' '.get_option('time_format'), strtotime($post->post_date)).'"
	data-timestamp="'.strtotime($post->post_date).'"
	>';
	foreach($replies as $reply) {
		if($reply->comment_parent == $id) {		
			$type = false;
			$name = 'Unspecified';
			$color = '#000';
			$type = get_comment_meta($reply->comment_ID, 'kbtype', true);
			
			foreach($knbu_kbsets[knbu_get_kbset_for_post(get_the_ID())]->KnowledgeTypeSet->KnowledgeType as $t) {	
				if($t['ID'] == $type) {
					$name = $t['Name']; 
					$color = $t['Colour'];
				}
			}
			$p = '';
			echo '<li class="kbtype-'.$type.'" 
			data-id="'.$reply->comment_ID.'"
			data-kbtype="'.$type.'"
			data-additional-parents="'.get_comment_meta($reply->comment_ID, 'knbu_map_additional_parents', true).$p.'"
			data-kbname="'.$name.'"
			data-username="'.$reply->comment_author.'"
			data-content="'.$reply->comment_content.'"
			data-date="'.date(get_option('date_format').' '.get_option('time_format'), strtotime($reply->comment_date)).'"
			data-timestamp="'.strtotime($reply->comment_date).'"
			data-color="'.$color.'"
			data-title="'.(strlen(get_comment_meta($reply->comment_ID, 'comment_title', true)) > 0 ? get_comment_meta($reply->comment_ID, 'comment_title', true) : '(no title)').'"
			data-avatar="'.knbu_get_avatar_url($reply->user_id).'">';
			knbu_get_childs($reply->comment_ID, $replies);
			echo '</li>';
		}
	}
	echo '</ul>';
}


function knbu_cmp($a, $b) {
	if($a->comment_parent == $b->comment_parent) 
		return 0;
	return $a->comment_parent > $b->comment_parent ? 1 : -1;
}
?>