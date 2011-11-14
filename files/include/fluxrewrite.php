<?php

/**
 * Copyright (C) 2008-2011 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * FluxRewrite by adaur
 */

//
// Generate a string with numbered links (for multipage scripts)
//
function paginate_rewrited($num_pages, $cur_page, $link)
{
	global $lang_common;

	$pages = array();
	$link_to_all = false;

	// If $cur_page == -1, we link to all pages (used in viewforum.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
	}

	if ($num_pages <= 1)
		$pages = array('<strong class="item1">1</strong>');
	else
	{
		// Add a previous page link
		if ($num_pages > 1 && $cur_page > 1)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'-page-'.($cur_page - 1).'.html">'.$lang_common['Previous'].'</a>';

		if ($cur_page > 3)
		{
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'-page-1.html">1</a>';

			if ($cur_page > 5)
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'-page-'.$current.'.html">'.forum_number_format($current).'</a>';
			else
				$pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';
		}

		if ($cur_page <= ($num_pages-3))
		{
			if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4))
				$pages[] = '<span class="spacer">'.$lang_common['Spacer'].'</span>';

			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'-page-'.$num_pages.'.html">'.forum_number_format($num_pages).'</a>';
		}

		// Add a next page link
		if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.$link.'-page-'.($cur_page +1).'.html">'.$lang_common['Next'].'</a>';
	}

	return implode(' ', $pages);
}

// Let's rewrite !
// Type must be "forum" for forum, "topic" for topic or "message" for post
// ID is the id of the content wanted
// Name is the title of the category or topic  

function makeurl($type, $id, $name, $new = null, $post = null) {    
    
    //gentle replace of special chars;
	$a = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ@()/[]|\'&';
    $b = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn---------';
    $url = utf8_encode(strtr(utf8_decode($name), utf8_decode($a), utf8_decode($b)));
    $url = preg_replace('/ /', '-', $url);
    //replace non alpha-num chars by - and trim possible last dashes    
    $url=trim(preg_replace('/[^a-z|A-Z|0-9|-]/', '', strtolower($url)), '-');
    //remove multiple occurences of -
    $url=preg_replace('/\-+/', '-', $url);
	if ($new != null)
		$url = $url .'-new-messages';
	$url = urlencode($type . $id .'-'. $url .'.html');
	if ($post != null)
		$url = $url.'#p'.$post;
    return $url;
}

function makeurlname($name) {  
    
    //gentle replace of special chars
	$a = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ@()/[]|\'&';
    $b = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn---------';
    $url = utf8_encode(strtr(utf8_decode($name), utf8_decode($a), utf8_decode($b)));
    $url = preg_replace('/ /', '-', $url);
    //replace non alpha-num chars by - and trim possible last dashes    
    $url = trim(preg_replace('/[^a-z|A-Z|0-9|-]/', '', strtolower($url)), '-');
    //remove multiple occurences of -
    $url = preg_replace('/\-+/', '-', $url);
    $url = urlencode($url);

    return $url;
}