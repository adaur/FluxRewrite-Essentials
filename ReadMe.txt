##
##       		   Title:  FluxRewrite Essentials
##
##   		     Version:  1.1.1
##  	 Works on FluxBB:  1.4.*
##    Date de la version:  2005-11-20 (PunRewrite - PunBB 1.2)
## 						   2009-12-10 (PunRewrite Essentials 1.1 - PunBB 1.2)
##						   2010-19-06 (FluxRewrite Essentials 1.1.1 - FluxBB 1.4)
##               Authors:  Kévin Dunglas (keyes) [dunglas+punbb AT gmail DOT com] (PunRewrite)
##                         adaur [adaur.underground@gmail.com] (PunRewrite Essentials / FluxRewrite Essentials)
##
##      	 Description: FluxRewrite is a mod created to enhance your positioning in search engines.
##						  It rewrites URLs to include the topic title. For example:
##                  	  http://wawa-coffee.net/viewtopic.php?id=245945956 becames 
##                   	  http://wawa-coffee.net/topic-245945956-sortie-du-nouveau-punrewrite.html
##                   	  Other features :
##                     		 * Rewrites all the URL seen in index.php, viewtopic.php, viewforum.php & search.php;
##                    		 * Replaces all special caracters like "é" or "ç" by regular caracters like "e" or "c";
##                    		 * Deletes the words of 3 letters or less
##
## 		  Affected files: index.php
##                    	  viewforum.php
##                   	  viewtopic.php
##                    	  search.php
##                    	  edit.php
##                    	  delete.php
##                    	  include/functions.php
##
##             	   Notes:  Licence GPL (see Licence.txt). You must use Apache with mod_rewrite enabled.
##					       The extern's modification is optional.
##
##            DISCLAIMER:  Please note that "mods" are not officially supported by
##                         PunBB / FluxBB. Installation of this modification is done at your
##                  	   own risk. Backup your forum database and any and all
##                   	   applicable files before proceeding.
##


#
#---------[ 1. OPEN ]-------------------------------------------------------
#

include/common.php

#
#---------[ 2. FIND ]----------------------------------------------------
#

require PUN_ROOT.'include/functions.php';

#
#---------[ 4. ADD AFTER ]------------------------------------------------
#

// Load FluxRewrite Essentials
require PUN_ROOT.'include/fluxrewrite.php';

#
#---------[ 1. OPEN ]-------------------------------------------------------
#

include/functions.php

#
#---------[ 2. FIND ]----------------------------------------------------
#

//
// Make sure that HTTP_REFERER matches base_url/script
//
function confirm_referrer($script, $error_msg = false)
{
	global $pun_config, $lang_common;

	// There is no referrer
	if (empty($_SERVER['HTTP_REFERER']))
		message($error_msg ? $error_msg : $lang_common['Bad referrer']);

	$referrer = parse_url(strtolower($_SERVER['HTTP_REFERER']));
	// Remove www subdomain if it exists
	if (strpos($referrer['host'], 'www.') === 0)
		$referrer['host'] = substr($referrer['host'], 4);

	$valid = parse_url(strtolower(get_base_url().'/'.$script));
	// Remove www subdomain if it exists
	if (strpos($valid['host'], 'www.') === 0)
		$valid['host'] = substr($valid['host'], 4);

	// Check the host and path match. Ignore the scheme, port, etc.
	if ($referrer['host'] != $valid['host'] || $referrer['path'] != $valid['path'])
		message($error_msg ? $error_msg : $lang_common['Bad referrer']);
}

#
#---------[ 4. REPLACE WITH ]------------------------------------------------
#

//
// Make sure that HTTP_REFERER matches base_url/script
//
function confirm_referrer($script, $error_msg = false)
{
	global $pun_config, $lang_common;
	static $rewrites = array('viewtopic.php' => 'topic-', 'viewforum.php' => 'forum-', 'post.php' => 'message-');

	// There is no referrer
	if (empty($_SERVER['HTTP_REFERER']))
		message($error_msg ? $error_msg : $lang_common['Bad referrer']);

	$referrer = parse_url(strtolower($_SERVER['HTTP_REFERER']));
	// Remove www subdomain if it exists
	if (strpos($referrer['host'], 'www.') === 0)
		$referrer['host'] = substr($referrer['host'], 4);

	$valid = parse_url(strtolower(get_base_url().'/'.$script));
	// Remove www subdomain if it exists
	if (strpos($valid['host'], 'www.') === 0)
		$valid['host'] = substr($valid['host'], 4);

	// Check the host and path match. Ignore the scheme, port, etc.
	if ($referrer['host'] != $valid['host'] || $referrer['path'] != $valid['path'])
	{
		if (array_key_exists($script, $rewrites))
		{
            if (!preg_match('#^'.get_base_url().'/'.$rewrites[$script].'[0-9]+-[0-9|a-b|\-|\.]*(.?)#i', strtolower($_SERVER['HTTP_REFERER'])))
                message($lang_common['Bad referrer']);                    
        }
		else
            message($lang_common['Bad referrer']);   
	}
}

#
#---------[ 2. FIND ]----------------------------------------------------
#

//
// Update posts, topics, last_post, last_post_id and last_poster for a forum
//
function update_forum($forum_id)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts = $num_posts + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result)) // There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else // There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}

#
#---------[ 4. REPLACE WITH ]------------------------------------------------
#

//
// Update posts, topics, last_post, last_post_id and last_poster for a forum
//
function update_forum($forum_id)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts = $num_posts + $num_topics; // $num_posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster, subject, id, num_replies FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result)) // There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster, $last_topic, $last_topic_id, $num_replies) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', num_replies='.$num_replies.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\', last_topic=\''.$db->escape($last_topic).'\', last_topic_id='.$last_topic_id.' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else // There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post=NULL, last_post_id=NULL, last_poster=NULL, last_topic=NULL, last_topic_id=NULL, num_replies=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster/last_topic', __FILE__, __LINE__, $db->error());
}

#
#---------[ 8. OPEN ]---------------------------------------------------------
#

index.php

#
#---------[ 9.FIND ]---------------------------------------------
#

$forum_field = '<h3><a href="viewforum.php?id='.$cur_forum['fid'].'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '').'</h3>';

#
#---------[ 10. REPLACE BY ]-------------------------------------------------
#

$forum_field = '<h3><a href="'.makeurl("forum-", $cur_forum['fid'], $cur_forum['forum_name'], 1, false, false).'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</a>'.(!empty($forum_field_new) ? ' '.$forum_field_new : '').'</h3>';

#
#---------[ 11. FIND ]---------------------------------------------
#

f.last_poster

#
#---------[ 12. ADD AFTER ]-------------------------------------------------
#

, f.last_topic, f.last_topic_id, f.num_replies
	
#
#---------[ 11. FIND ]---------------------------------------------
#

	if ($cur_forum['last_post'] != '')
		$last_post = '<a href="viewtopic.php?pid='.$cur_forum['last_post_id'].'#p'.$cur_forum['last_post_id'].'">'.format_time($cur_forum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';

#
#---------[ 12. REPLACE BY ]-------------------------------------------------
#

	if ($cur_forum['last_post'] != '')
	{
		$num_pages_topic = ceil(($cur_forum['num_replies'] + 1) / $pun_user['disp_posts']);
		$last_post = '<a href="'.makeurl("topic-", $cur_forum['last_topic_id'], $cur_forum['last_topic'], $num_pages_topic, false, $cur_forum['last_post_id']).'">'.format_time($cur_forum['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_forum['last_poster']).'</span>';
	}


#
#---------[ 13. OPEN ]---------------------------------------------------------
#

viewforum.php

#
#---------[ 13. MOVE ]---------------------------------------------------------
#

$num_pages_topic = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

#
#---------[ 13. AFTER ]---------------------------------------------------------
#

$icon_type = 'icon';

#
#---------[ 14. FIND ]---------------------------------------------
#

		if ($cur_topic['moved_to'] == null)
			$last_post = '<a href="viewtopic.php?pid='.$cur_topic['last_post_id'].'#p'.$cur_topic['last_post_id'].'">'.format_time($cur_topic['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
		else
			$last_post = '- - -';

#
#---------[ 15. REPLACE BY ]-------------------------------------------------
#

		if ($cur_topic['moved_to'] == null)
			$last_post = '<a href="'.makeurl("topic-", $cur_topic['id'], $cur_topic['subject'].'-page-'.$num_pages_topic, $new = null, $post = $cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a><br /><span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['last_poster']).'</span>';
		else
			$last_post = '- - -';

#
#---------[ 16. FIND ]---------------------------------------------
#

$subject = '<a href="viewtopic.php?id='.$cur_topic['moved_to'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 17. REPLACE BY ]-------------------------------------------------
#

$subject = '<a href="'.makeurl("topic-", $cur_topic['moved_to'], $cur_topic['subject']).'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 18. FIND ]---------------------------------------------
#

$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 19. REPLACE BY ]-------------------------------------------------
#

$subject = '<a href="'.makeurl("topic-", $cur_topic['id'], $cur_topic['subject']).'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 20. FIND ]---------------------------------------------
#

$subject = '<a href="viewtopic.php?id='.$cur_topic['id'].'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 21. REPLACE BY ]-------------------------------------------------
#

$subject = '<a href="'.makeurl("topic-", $cur_topic['id'], $cur_topic['subject']).'">'.pun_htmlspecialchars($cur_topic['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_topic['poster']).'</span>';

#
#---------[ 22. FIND ]---------------------------------------------
#

$subject_new_posts = '<span class="newtext">[ <a href="viewtopic.php?id='.$cur_topic['id'].'&amp;action=new" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';

#
#---------[ 23. REPLACE BY ]-------------------------------------------------
#

$subject_new_posts = '<span class="newtext">[ <a href="'.makeurlnew("topic-", $cur_topic['id'], $cur_topic['subject']).'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';

#
#---------[ 24. FIND ]---------------------------------------------
#

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'viewforum.php?id='.$id);

#
#---------[ 25. REPLACE BY ]-------------------------------------------------
#

// Generate paging links 
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate_rewrited($num_pages, $p, 'forum-'.$id.'-'.clean_url($cur_forum['forum_name']));

#
#---------[ 26. FIND ]---------------------------------------------
#

$subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$cur_topic['id']).' ]</span>';

#
#---------[ 27. REPLACE BY ]-------------------------------------------------
#

$subject_multipage = '<span class="pagestext">[ '.paginate_rewrited($num_pages_topic, -1, 'topic-'.$cur_topic['id'].'-'.clean_url($cur_topic['subject'])).' ]</span>';

#
#---------[ 26. FIND ]---------------------------------------------
#

<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></a></li>

#
#---------[ 27. REPLACE BY ]-------------------------------------------------
#

<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $id, $cur_forum['forum_name']) ?>"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></a></li>

#
#---------[ 26. FIND ]---------------------------------------------
#

<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></a></li>

#
#---------[ 27. REPLACE BY ]-------------------------------------------------
#

<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $id, $cur_forum['forum_name']) ?>"><strong><?php echo pun_htmlspecialchars($cur_forum['forum_name']) ?></strong></a></li>


#
#---------[ 28. OPEN ]---------------------------------------------------------
#

viewtopic.php

#
#---------[ 29. FIND ]---------------------------------------------
#
		<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
		<li><span>»&#160;</span><a href="viewtopic.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>

#
#---------[ 30. REPLACE BY ]-------------------------------------------------
#

		<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $cur_topic['forum_id'], $cur_topic['forum_name']) ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
		<li><span>»&#160;</span><a href="<?php echo makeurl("topic-", $id, $cur_topic['subject']) ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>
		
#
#---------[ 29. FIND ]---------------------------------------------
#
		<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
		<li><span>»&#160;</span><a href="viewtopic.php?id=<?php echo $id ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>

#
#---------[ 30. REPLACE BY ]-------------------------------------------------
#

		<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $cur_topic['forum_id'], $cur_topic['forum_name']) ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li>
		<li><span>»&#160;</span><a href="<?php echo makeurl("topic-", $id, $cur_topic['subject']) ?>"><strong><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></strong></a></li>

#
#---------[ 35. FIND ]---------------------------------------------
#

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'viewtopic.php?id='.$id);

#
#---------[ 36. REPLACE BY ]-------------------------------------------------
#

// Generate paging links
$paging_links = '<span class="pages-label">'.$lang_common['Pages'].': </span>'.paginate_rewrited($num_pages, $p, 'topic-'.$id.'-'.clean_url($cur_topic['subject']));

#
#---------[ 36. FIND ]---------------------------------------------
#

header('Location: viewtopic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);

#
#---------[ 37. REPLACE BY ]-------------------------------------------------
#

A faire

#
#---------[ 38. FIND ]---------------------------------------------
#

header('Location: viewtopic.php?id='.$id.'&action=last');

#
#---------[ 39. REPLACE BY ]-------------------------------------------------
#

header('Location: topic-'.$id.'-last-message.html');

#
#---------[ 40. FIND ]---------------------------------------------
#

header('Location: viewtopic.php?pid='.$last_post_id.'#p'.$last_post_id);

#
#---------[ 41. REPLACE BY ]-------------------------------------------------
#

A faire

#
#---------[ 40. FIND ]---------------------------------------------
#

<a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>">

#
#---------[ 41. REPLACE BY ]-------------------------------------------------
#

<a href="<?php echo makeurl("topic-", $id, $cur_topic['subject'].'-page-'.$p, $new = null, $post = $cur_post['id']) ?>">

#
#---------[ 42. OPEN ]---------------------------------------------------------
#

search.php

#
#---------[ 42. FIND ]---------------------------------------------------------
#

$forum = '<a href="viewforum.php?id='.$cur_search['forum_id'].'">'.pun_htmlspecialchars($cur_search['forum_name']).'</a>';

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

$forum = '<a href="'.makeurl("forum-", $cur_search['forum_id'], $cur_search['forum_name']).'">'.pun_htmlspecialchars($cur_search['forum_name']).'</a>';

#
#---------[ 42. AFTER ]---------------------------------------------------------
#

if ($pun_config['o_censoring'] == '1')
	$cur_search['subject'] = censor_words($cur_search['subject']);
				
#
#---------[ 42. ADD ]---------------------------------------------------------
#

$num_pages_topic = ceil(($cur_search['num_replies'] + 1) / $pun_user['disp_posts']);

#
#---------[ 42. AFTER ]---------------------------------------------------------
#

						$pposter = '<strong>'.$pposter.'</strong>';
				}
				
#
#---------[ 42. ADD ]---------------------------------------------------------
#

					$last_post_h2 = '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject'].'-page-'.$num_pages_topic, $new = null, $post = $cur_search['pid']).'">'.format_time($cur_search['last_post']).'</a>';
					$last_post_footer = '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject'].'-page-'.$num_pages_topic, $new = null, $post = $cur_search['pid']).'">'.$lang_search['Go to post'].'</a>';
				
#
#---------[ 42. FIND ]---------------------------------------------------------
#

<h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?></span> <span><?php if ($cur_search['pid'] != $cur_search['first_post_id']) echo $lang_topic['Re'].' ' ?><?php echo $forum ?></span> <span>»&#160;<a href="viewtopic.php?id=<?php echo $cur_search['tid'] ?>"><?php echo pun_htmlspecialchars($cur_search['subject']) ?></a></span> <span>»&#160;<a href="viewtopic.php?pid=<?php echo $cur_search['pid'].'#p'.$cur_search['pid'] ?>"><?php echo format_time($cur_search['pposted']) ?></a></span></span></h2>

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

<h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?></span> <span><?php if ($cur_search['pid'] != $cur_search['first_post_id']) echo $lang_topic['Re'].' ' ?><?php echo $forum ?></span> <span>Â»&#160;<?php echo '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject']).'">'.pun_htmlspecialchars($cur_search['subject']).'</a>'; ?></span> <span>Â»&#160;<?php echo $last_post_h2 ?></a></span></span></h2>

#
#---------[ 42. FIND ]---------------------------------------------------------
#

						<li><span><a href="viewtopic.php?id=<?php echo $cur_search['tid'] ?>"><?php echo $lang_search['Go to topic'] ?></a></span></li>
						<li><span><a href="viewtopic.php?pid=<?php echo $cur_search['pid'].'#p'.$cur_search['pid'] ?>"><?php echo $lang_search['Go to post'] ?></a></span></li>
						
#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

						<li><span><?php echo '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject']).'">'.$lang_search['Go to topic'].'</a>'; ?></a></span></li>
						<li><span><?php echo $last_post_footer ?></a></span></li>
						
#
#---------[ 42. FIND ]---------------------------------------------------------
#

$subject = '<a href="viewtopic.php?id='.$cur_search['tid'].'">'.pun_htmlspecialchars($cur_search['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['poster']).'</span>';

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

$subject = '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject']).'">'.pun_htmlspecialchars($cur_search['subject']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['poster']).'</span>';

#
#---------[ 42. FIND ]---------------------------------------------------------
#

$subject_new_posts = '<span class="newtext">[ <a href="viewtopic.php?id='.$cur_search['tid'].'&amp;action=new" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

$subject_new_posts = '<span class="newtext">[ <a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject'], $new = true).'" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a> ]</span>';

#
#---------[ 42. FIND ]---------------------------------------------------------
#

$subject = implode(' ', $status_text).' '.$subject;

$num_pages_topic = ceil(($cur_search['num_replies'] + 1) / $pun_user['disp_posts']);
				
#
#---------[ 42. DELETE ]---------------------------------------------------------
#

$num_pages_topic = ceil(($cur_search['num_replies'] + 1) / $pun_user['disp_posts']);

#
#---------[ 42. FIND ]---------------------------------------------------------
#

				if ($num_pages_topic > 1)
					$subject_multipage = '<span class="pagestext">[ '.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$cur_search['tid']).' ]</span>';
				else
					$subject_multipage = null;

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

				if ($num_pages_topic > 1)
					$subject_multipage = '<span class="pagestext">[ '.paginate_rewrited($num_pages_topic, -1, 'topic-'.$cur_search['tid'].'-'.clean_url($cur_search['subject'])).' ]</span>';
				else
					$subject_multipage = null;
				
				$last_post = '<a href="'.makeurl("topic-", $cur_search['tid'], $cur_search['subject'].'-page-'.$num_pages_topic, $new = null, $post = $cur_search['last_post_id']).'">'.format_time($cur_search['last_post']).'</a>';
				
#
#---------[ 42. FIND ]---------------------------------------------------------
#

<td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$cur_search['last_post_id'].'#p'.$cur_search['last_post_id'].'">'.format_time($cur_search['last_post']).'</a> <span class="byuser">'.$lang_common['by'].' '.pun_htmlspecialchars($cur_search['last_poster']) ?></span></td>

#
#---------[ 42. REPLACE WITH ]---------------------------------------------------------
#

<td class="tcr"><?php echo $last_post ?></span></td>

#
#---------[ 59. OPEN ]---------------------------------------------------------
#

post.php

#
#---------[ 60. FIND ]---------------------------------------------
#

		redirect('viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $lang_post['Post redirect']);

#
#---------[ 61. REPLACE BY ]-------------------------------------------------
#

		$result = $db->query('SELECT subject, num_replies FROM '.$db->prefix.'topics WHERE id='.$new_tid) or error('Unable to get subject', __FILE__, __LINE__, $db->error());
		list($subject, $num_replies) = $db->fetch_row($result);
		
		$num_pages = ceil(($num_replies + 1) / $pun_user['disp_posts']);
		redirect(makeurl("topic-", $new_tid, $subject.'-page-'.$num_pages, $new = null, $post = $new_pid), $lang_post['Post redirect']);

#
#---------[ 62. FIND ]---------------------------------------------
#

<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_posting['id'] ?>"><?php echo pun_htmlspecialchars($cur_posting['forum_name']) ?></a></li>
<?php if (isset($cur_posting['subject'])): ?>			<li><span>»&#160;</span><a href="viewtopic.php?id=<?php echo $tid ?>"><?php echo pun_htmlspecialchars($cur_posting['subject']) ?>

#
#---------[ 63. REPLACE BY ]-------------------------------------------------
#

<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $cur_posting['id'], $cur_posting['forum_name']) ?>"><?php echo pun_htmlspecialchars($cur_posting['forum_name']) ?></a></li>
<?php if (isset($cur_posting['subject'])): ?>			<li><span>»&#160;</span><a href="<?php echo makeurl("topic-", $tid, $cur_posting['subject']) ?>"><?php echo pun_htmlspecialchars($cur_posting['subject']) ?></a>

#
#---------[ 64. FIND ]---------------------------------------------
#

$mail_message = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message);

#
#---------[ 65. REPLACE BY ]-------------------------------------------------
#

$mail_message = str_replace('<post_url>', get_base_url().'/'.makeurl("topic-", $tid, $cur_posting['subject'], $new = null, $post = $new_pid), $mail_message);

#
#---------[ 66. FIND ]---------------------------------------------
#

$mail_message_full = str_replace('<post_url>', get_base_url().'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message_full);

#
#---------[ 67. REPLACE BY ]-------------------------------------------------
#

$mail_message_full = str_replace('<post_url>', get_base_url().'/'.makeurl("topic-", $tid, $cur_posting['subject'], $new = null, $post = $new_pid), $mail_message_full);

#
#---------[ 68. FIND ]---------------------------------------------
#

$mail_message = str_replace('<topic_url>', get_base_url().'/viewtopic.php?id='.$new_tid, $mail_message);

#
#---------[ 69. REPLACE BY ]-------------------------------------------------
#

$mail_message = str_replace('<topic_url>', get_base_url().'/'.makeurl("topic-", $new_tid, $pun_config['o_censoring'] == '1' ? $censored_subject : $subject), $mail_message);


#
#---------[ 68. FIND ]---------------------------------------------
#

$mail_message_full = str_replace('<topic_url>', get_base_url().'/viewtopic.php?id='.$new_tid, $mail_message_full);

#
#---------[ 69. REPLACE BY ]-------------------------------------------------
#

$mail_message_full = str_replace('<topic_url>', get_base_url().'/'.makeurl("topic-", $new_tid, $pun_config['o_censoring'] == '1' ? $censored_subject : $subject), $mail_message_full);



#
#---------[ 70. OPEN ]---------------------------------------------------------
#

edit.php

#
#---------[ 71. FIND ]---------------------------------------------
#

redirect('viewtopic.php?pid='.$id.'#p'.$id, $lang_post['Edit redirect']);

#
#---------[ 72. REPLACE BY ]-------------------------------------------------
#

		if (!$can_edit_subject)
		{
			$result = $db->query('SELECT subject FROM '.$db->prefix.'topics WHERE id='.$cur_post['tid']) or error('Unable to get subject', __FILE__, __LINE__, $db->error());
			$subject = $db->result($result);
		}

		$result2 = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE id BETWEEN '.$cur_post['first_post_id'].' AND '.$id.' AND topic_id='.$cur_post['tid']) or error('Unable to get subject', __FILE__, __LINE__, $db->error());
		$num_replies = $db->result($result2);
		
		$num_pages = ceil(($num_replies + 1) / $pun_user['disp_posts']);
		redirect(makeurl("topic-", $cur_post['tid'], $subject.'-page-'.$num_pages, $new = null, $post = $id), $lang_post['Edit redirect']);

#
#---------[ 73. FIND ]---------------------------------------------
#

			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="viewtopic.php?id=<?php echo $cur_post['tid'] ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>

#
#---------[ 74. REPLACE BY ]-------------------------------------------------
#

			<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $cur_post['fid'], $cur_post['forum_name']) ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo makeurl("topic-", $cur_post['tid'], $cur_post['subject']) ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>

#
#---------[ 75. OPEN ]---------------------------------------------------------
#

delete.php

#
#---------[ 76. FIND ]---------------------------------------------
#

redirect('viewforum.php?id='.$cur_post['fid'], $lang_delete['Topic del redirect']);

#
#---------[ 77. REPLACE BY ]-------------------------------------------------
#

redirect(makeurl("forum-", $cur_post['fid'], $cur_post['forum_name']), $lang_delete['Topic del redirect']);

#
#---------[ 78. FIND ]---------------------------------------------
#

redirect('viewtopic.php?pid='.$post_id.'#p'.$post_id, $lang_delete['Post del redirect']);

#
#---------[ 79. REPLACE BY ]----------------------------------------
#

redirect(makeurl("topic-", $cur_post['tid'], $cur_post['subject']), $lang_delete['Post del redirect']);

#
#---------[ 80. FIND ]---------------------------------------------
#

			<li><span>»&#160;</span><a href="viewforum.php?id=<?php echo $cur_post['fid'] ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="viewtopic.php?pid=<?php echo $id ?>#p<?php echo $id ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>

#
#---------[ 81. REPLACE BY ]-------------------------------------------------
#

			<li><span>»&#160;</span><a href="<?php echo makeurl("forum-", $cur_post['fid'], $cur_post['forum_name']) ?>"><?php echo pun_htmlspecialchars($cur_post['forum_name']) ?></a></li>
			<li><span>»&#160;</span><a href="message-<?php echo $id ?>.html#p<?php echo $id ?>"><?php echo pun_htmlspecialchars($cur_post['subject']) ?></a></li>
		
			
#
#---------[ 102. Save your files and upload them; you're done! ]-----------------
#

Important : Don't forget to upload .htaccess!!