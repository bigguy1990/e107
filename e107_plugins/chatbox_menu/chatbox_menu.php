<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */

/**
 *	e107 Chatbox plugin
 *
 *	@package	e107_plugins
 *	@subpackage	chatbox
 */

error_reporting(E_ALL);
if(isset($_POST['chatbox_ajax']))
{
	define('e_MINIMAL',true);
}

include_once('../../class2.php');

global $e107cache, $e_event, $e107;


$tp = e107::getParser();
$pref = e107::getPref(); 



if (!plugInstalled('chatbox_menu')) 
{
	return '';
}

include_lan(e_PLUGIN.'chatbox_menu/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'.php');

// FIXME - start - LAN is not loaded
/*
if(($pref['cb_layer']==2) || isset($_POST['chatbox_ajax']))
{
	if(isset($_POST['chat_submit']))
	{
		

		//Normally the menu.sc file will auto-load the language file, this is needed in case
		//ajax is turned on and the menu is not loaded from the menu.sc
		include_lan(e_PLUGIN.'chatbox_menu/languages/'.e_LANGUAGE.'/'.e_LANGUAGE.'.php');
	}
}
// FIXME - end
*/

// if(!defined('e_HANDLER')){ exit; }
require_once(e_HANDLER.'emote.php');

$emessage='';







class chatbox_shortcodes extends e_shortcode
{
	function sc_username($parm='')
	{
		list($cb_uid, $cb_nick) = explode(".", $this->var['cb_nick'], 2);
		if($this->var['user_name'])
		{
			$cb_nick = "<a href='".e_HTTP."user.php?id.{$cb_uid}'>".$this->var['user_name']."</a>";
		}
		else
		{
			$cb_nick = $tp -> toHTML($cb_nick,FALSE,'USER_TITLE, emotes_off, no_make_clickable');
			$cb_nick = str_replace("Anonymous", LAN_ANONYMOUS, $cb_nick);
		}
		
		return $cb_nick;	
	}	
	
	function sc_timedate($parm='')
	{
		return  e107::getDate()->convert_date($this->var['cb_datestamp'], "relative");		
	}
		

	function sc_message($parm = '')
	{
		if($this->var['cb_blocked'])
		{
			return CHATBOX_L6;	
		}
		
		$pref 			= e107::getPref();
		$emotes_active 	= $pref['cb_emote'] ? 'USER_BODY, emotes_on' : 'USER_BODY, emotes_off';
		
		list($cb_uid, $cb_nick) = explode(".", $this->var['cb_nick'], 2);
		
		$cb_message = e107::getParser()->toHTML($this->var['cb_message'], false, $emotes_active, $cb_uid, $pref['menu_wordwrap']);

		return $cb_message;

		$replace[0] = "["; $replace[1] = "]";
		$search[0] = "&lsqb;"; $search[1] =  "&rsqb;";
		$cb_message = str_replace($search, $replace, $cb_message);	
	}

	function sc_avatar($parm='')
	{
		return e107::getParser()->parseTemplate("{USER_AVATAR=".$this->var['user_image']."}");
	}
	
	function sc_bullet($parm = '')
	{
		$bullet = "";
		
		if(defined('BULLET'))
		{
			$bullet = '<img src="'.THEME_ABS.'images/'.BULLET.'" alt="" class="icon" />';
		}
		elseif(file_exists(THEME.'images/bullet2.gif'))
		{
			$bullet = '<img src="'.THEME_ABS.'images/bullet2.gif" alt="" class="icon" />';
		}	
		
		return $bullet;
	}

}
















if((isset($_POST['chat_submit']) || e_AJAX_REQUEST) && $_POST['cmessage'] != '')
{
	if(!USER && !$pref['anon_post'])
	{
		// disallow post
	}
	else
	{
		$nick = trim(preg_replace("#\[.*\]#si", "", $tp -> toDB($_POST['nick'])));

		$cmessage = $_POST['cmessage'];
		$cmessage = preg_replace("#\[.*?\](.*?)\[/.*?\]#s", "\\1", $cmessage);

		$fp = new floodprotect;
		if($fp -> flood("chatbox", "cb_datestamp"))
		{
			if((strlen(trim($cmessage)) < 1000) && trim($cmessage) != "")
			{
				$cmessage = $tp -> toDB($cmessage);
				if($sql->select("chatbox", "*", "cb_message='$cmessage' AND cb_datestamp+84600>".time()))
				{
					$emessage = CHATBOX_L17;
				}
				else
				{
					$datestamp = time();
					$ip = e107::getIPHandler()->getIP(FALSE);
					if(USER)
					{
						$nick = USERID.".".USERNAME;
						$sql -> db_Update("user", "user_chats=user_chats+1, user_lastpost='".time()."' WHERE user_id='".USERID."' ");
					}
					else if(!$nick)
					{
						$nick = "0.Anonymous";
					}
					else
					{
						if($sql->select("user", "*", "user_name='$nick' ")){
							$emessage = CHATBOX_L1;
						}
						else
						{
							$nick = "0.".$nick;
						}
					}
					if(!$emessage)
					{
						$sql->insert("chatbox", "0, '$nick', '$cmessage', '".time()."', '0' , '$ip' ");
						$edata_cb = array("cmessage" => $cmessage, "ip" => $ip);
						$e_event -> trigger("cboxpost", $edata_cb);
						$e107cache->clear("nq_chatbox");
					}
				}
			}
			else
			{
				$emessage = CHATBOX_L15;
			}
		}
		else
		{
			$emessage = CHATBOX_L19;
		}
	}
}

if(!USER && !$pref['anon_post']){
	if($pref['user_reg'])
	{
		$texta = "<div style='text-align:center'>".CHATBOX_L3."</div><br /><br />";
	}
}
else
{
	$cb_width = (defined("CBWIDTH") ? CBWIDTH : "");

	if($pref['cb_layer'] == 2)
	{
		$texta =  "\n<form id='chatbox' action='".e_SELF."?".e_QUERY."'  method='post' onsubmit='return(false);'>
		<div><input type='hidden' name='chatbox_ajax' id='chatbox_ajax' value='1' /></div>
		";
	}
	else
	{
		$texta =  (e_QUERY ? "\n<form id='chatbox' method='post' action='".e_SELF."?".e_QUERY."'>" : "\n<form id='chatbox' method='post' action='".e_SELF."'>");
	}
	$texta .= "<div id='chatbox-input-block'>";

	if(($pref['anon_post'] == "1" && USER == FALSE))
	{
		$texta .= "\n<input class='tbox chatbox' type='text' id='nick' name='nick' value='' maxlength='50' ".($cb_width ? "style='width: ".$cb_width.";'" : '')." /><br />";
	}

	if($pref['cb_layer'] == 2)
	{

		$oc = "onclick=\"javascript:sendInfo('".SITEURLBASE.e_PLUGIN_ABS."chatbox_menu/chatbox_menu.php', 'chatbox_posts', this.form);\"";
	}
	else
	{
		$oc = "";
	}
	$texta .= "
	<textarea placeholder=\"".LAN_CHATBOX_100."\" required class='tbox chatbox input-xlarge' id='cmessage' name='cmessage' cols='20' rows='5' style='".($cb_width ? "width:".$cb_width.";" : '')." overflow: auto' onselect='storeCaret(this);' onclick='storeCaret(this);' onkeyup='storeCaret(this);'></textarea>
	<br />
	<input class='btn button' type='submit' id='chat_submit' name='chat_submit' value='".CHATBOX_L4."' {$oc}/>
	";
	
	// $texta .= "<input class='btn button' type='reset' name='reset' value='".CHATBOX_L5."' />"; // How often do we see these lately? ;-)

	if($pref['cb_emote'] && $pref['smiley_activate'])
	{
		$texta .= "
		<input class='btn button' type='button' style='cursor:pointer' size='30' value='".CHATBOX_L14."' onclick=\"expandit('emote')\" />
		<div style='display:none' id='emote'>".r_emote()."
		</div>\n";
	}

	$texta .="</div>\n</form>\n";
}

if($emessage != ""){
	$texta .= "<div style='text-align:center'><b>".$emessage."</b></div>";
}

if(!$text = $e107cache->retrieve("nq_chatbox"))
{
	global $pref,$tp;
	$pref['chatbox_posts'] = ($pref['chatbox_posts'] ? $pref['chatbox_posts'] : 10);
	$chatbox_posts = $pref['chatbox_posts'];
	if(!isset($pref['cb_mod']))
	{
		$pref['cb_mod'] = e_UC_ADMIN;
	}
	define("CB_MOD", check_class($pref['cb_mod']));

	$qry = "
	SELECT c.*, u.user_name FROM #chatbox AS c
	LEFT JOIN #user AS u ON SUBSTRING_INDEX(c.cb_nick,'.',1) = u.user_id
	ORDER BY c.cb_datestamp DESC LIMIT 0, ".intval($chatbox_posts);

	global $CHATBOXSTYLE;

	
	if($CHATBOXSTYLE)
	{
		$CHATBOX_TEMPLATE['start'] = "";
		$CHATBOX_TEMPLATE['item'] = $CHATBOXSTYLE;
		$CHATBOX_TEMPLATE['end'] = "";
	}
	else 	// default chatbox style
	{
		$tp->parseTemplate("{SETIMAGE: w=40}",true); // set thumbnail size. 
		
		$CHATBOX_TEMPLATE['start'] 	= "<ul class='unstyled'>";
		$CHATBOX_TEMPLATE['item'] 	= "<li>
										{AVATAR} <b>{USERNAME}</b>&nbsp;
										<small class='muted smalltext'>{TIMEDATE}</small><br />
										<p style='margin-left:50px'>{MESSAGE}</p>
										</li>\n";
										
		$CHATBOX_TEMPLATE['end'] 	= "</ul>";
	}
		
	$sc = e107::getScBatch('chatbox');		
			
	if($sql->gen($qry))
	{
		$cbpost = $sql->db_getList();
		$text .= "<div id='chatbox-posts-block'>\n";
		
		$text .= $tp->parseTemplate($CHATBOX_TEMPLATE['start'], true, $sc);
		
		foreach($cbpost as $cb)
		{
			$sc->setVars($cb);
			$text .= $tp->parseTemplate($CHATBOX_TEMPLATE['item'], false, $sc);
		}
		
		$text .= $tp->parseTemplate($CHATBOX_TEMPLATE['end'], true, $sc);
		
		$text .= "</div>";
	}
	else
	{
		$text .= "<span class='mediumtext'>".CHATBOX_L11."</span>";
	}
	
	
	$total_chats = $sql->count("chatbox");
	if($total_chats > $chatbox_posts || CB_MOD)
	{
		$text .= "<br /><div style='text-align:center'><a href='".e_PLUGIN_ABS."chatbox_menu/chat.php'>".(CB_MOD ? CHATBOX_L13 : CHATBOX_L12)."</a> (".$total_chats.")</div>";
	}
	$e107cache->set("nq_chatbox", $text);
}




$caption = (file_exists(THEME."images/chatbox_menu.png") ? "<img src='".THEME_ABS."images/chatbox_menu.png' alt='' /> ".CHATBOX_L2 : CHATBOX_L2);

if($pref['cb_layer'] == 1)
{
	$text = $texta."<div style='border : 0; padding : 4px; width : auto; height : ".$pref['cb_layer_height']."px; overflow : auto; '>".$text."</div>";
	$ns -> tablerender($caption, $text, 'chatbox');
}
elseif($pref['cb_layer'] == 2 && e_AJAX_REQUEST)
{
	$text = $texta.$text;
	$text = str_replace(e_IMAGE, e_IMAGE_ABS, $text);
	echo $text;
}
else
{
	$text = $texta.$text;
	if($pref['cb_layer'] == 2)
	{
		$text = "<div id='chatbox_posts'>".$text."</div>";
	}
	$ns -> tablerender($caption, $text, 'chatbox');
}

//$text = ($pref['cb_layer'] ? $texta."<div style='border : 0; padding : 4px; width : auto; height : ".$pref['cb_layer_height']."px; overflow : auto; '>".$text."</div>" : $texta.$text);
//if(ADMIN && getperms("C")){$text .= "<br /><div style='text-align: center'>[ <a href='".e_PLUGIN."chatbox_menu/admin_chatbox.php'>".CHATBOX_L13."</a> ]</div>";}
//$ns -> tablerender($caption, $text, 'chatbox');


?>