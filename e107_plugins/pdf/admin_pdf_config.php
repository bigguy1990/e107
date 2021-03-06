<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Plugin Administration - PDF generator
 *
 * $Source: /cvs_backup/e107_0.8/e107_plugins/pdf/admin_pdf_config.php,v $
 * $Revision$
 * $Date$
 * $Author$
 *
*/

/**
 *	e107 pdf generation plugin
 *
 *	@package	e107_plugins
 *	@subpackage	pdf
 *	@version 	$Id$;
 */


require_once('../../class2.php');
if (!getperms('P') || !plugInstalled('pdf')) 
{
	header('location:'.e_BASE.'index.php');
	exit;
}
require_once(e_ADMIN.'auth.php');
require_once(e_HANDLER.'form_handler.php');
$rs = new form;


unset($text);

include_lan(e_PLUGIN.'pdf/languages/English_admin_pdf.php');


/**
 *	Update prefs to new values.
 *
 *	@param boolean $setDefaults = set all prefs to default values if TRUE
 */
function updatePDFPrefs(&$oldPrefs, $setDefaults = FALSE)
{
	$tp = e107::getParser();
	$prefChanges = array();
	$pdfNew = e107::getPlugConfig('pdf');
	$mes = eMessage::getInstance();
	$prefList = getDefaultPDFPrefs();

	if ($setDefaults)
	{
		$oldPrefs = $prefList;
		$adminEvent = 'PDF_02';
		$adminMessage = PDF_LAN_33;
		$prefChanges[] = 'all => defaults';
		foreach($prefList as $k => $default)
		{
			$pdfNew->set($k, $default);
		}
	}
	else
	{
		$adminEvent = 'PDF_01';
		$adminMessage = PDF_LAN_18;
		foreach($prefList as $k => $default)
		{
			if (isset($_POST[$k]))
			{
				$newVal = $tp->toDB($_POST[$k]);
				if ($oldPrefs[$k] != $newVal)
				{
					$oldPrefs[$k] = $newVal;
					$pdfNew->set($k, $newVal);
					$prefChanges[] = $k.' => '.$newVal;
				}
			}
			elseif (!isset($oldPrefs[$k]))
			{
				$oldPrefs[$k] = $default;	// Restore any lost prefs
			}
		}
	}
	if (count($prefChanges))
	{
		$result = $pdfNew->save();
		if ($result === TRUE)
		{
			// Do admin logging
			$logString = implode('[!br!]', $prefChanges);
			e107::getAdminLog()->log_event($adminEvent, $logString, E_LOG_INFORMATIVE, '');
			$mes->add($adminMessage, E_MESSAGE_SUCCESS);	
		}
		elseif ($result === FALSE)
		{
			$mes->add(PDF_LAN_32, E_MESSAGE_ERROR);		
		}
		else
		{		// Should never happen
			$mes->add('PDF Unexpected result: '.$result, E_MESSAGE_INFO);

		}
	}
	//$admin_log->logArrayAll('PDF_01',$pdfpref);
	//return $message;
}


function getDefaultPDFPrefs()
{
	$pdfpref['pdf_margin_left']				= '25';
	$pdfpref['pdf_margin_right']			= '15';
	$pdfpref['pdf_margin_top']				= '15';
	$pdfpref['pdf_font_family']				= 'helvetica';
	$pdfpref['pdf_font_size']				= '8';
	$pdfpref['pdf_font_size_sitename']		= '14';
	$pdfpref['pdf_font_size_page_url']		= '8';
	$pdfpref['pdf_font_size_page_number']	= '8';
	$pdfpref['pdf_show_logo']				= true;
	$pdfpref['pdf_show_sitename']			= false;
	$pdfpref['pdf_show_page_url']			= true;
	$pdfpref['pdf_show_page_number']		= true;
	$pdfpref['pdf_error_reporting']			= true;
	return $pdfpref;
}



function getPDFPrefs()
{
	$ans = e107::pref('pdf');         // retrieve pref array.
	if (count($ans) == 0)
	{
		$ans = getDefaultPDFPrefs();
	}

	return $ans;
}

// Default list just in case
$fontlist=array('times','courier','helvetica','symbol');






function getFontInfo($fontName)
{
	$type = 'empty';	// Preset the stuff we're going to read
	$dw = 0;
	$cw = array();
	$name='';
	//$desc=array('Ascent'=>900,'Descent'=>-300,'CapHeight'=>-29,'Flags'=>96,'FontBBox'=>'[-879 -434 1673 900]','ItalicAngle'=>-16.5,'StemV'=>70,'MissingWidth'=>600);
	//$up=-125;
	//$ut=50;
	include(e_PLUGIN.'pdf/fonts/'.$fontName);
	return array('type' => $type, 'weight' => $dw, 'codes' => count($cw), 'name' => $name);
}



function getFontList($match = '')
{
	require_once(e_HANDLER.'file_class.php');
	$fl = new e_file();
	if (!$match) $match = '~^uni2cid';
	$fileList = $fl->get_files(e_PLUGIN.'pdf/fonts/',$match, 'standard', 1);
	$fontList = array();
	$intList = array();
	foreach ($fileList as $v)
	{
		if (isset($v['fname']) && (substr($v['fname'],-4) == '.php'))
		{
			$intList[] = substr($v['fname'],0,-4);
		}
	}
	unset($fileList);
	sort($intList);				// This will guarantee that base font names appear before bold, italic etc
	foreach ($intList as $f)
	{
		if (substr($f,-2) == 'bi')
		{
			$fontList[substr($f,0,-2)]['bi'] = $f.'.php';
		}
		elseif (substr($f,-1) == 'i')
		{
			$fontList[substr($f,0,-1)]['i'] = $f.'.php';
		}
		elseif (substr($f,-1) == 'b')
		{
			$fontList[substr($f,0,-1)]['b'] = $f.'.php';
		}
		else
		{	// Must be base font name
			$fontList[$f]['base'] = $f.'.php';
		}
	}
	// Now get the info on each font.
	foreach ($fontList as $font => $info)
	{
		$fontList[$font]['info'] = getFontInfo($info['base']);
	}
	//print_a($fontList);
	return $fontList;
}



$pdfpref = getPDFPrefs();



if(isset($_POST['update_pdf']))
{
//	$message = updatePDFPrefs();
	updatePDFPrefs($pdfpref, FALSE);
}


if(isset($_POST['default_pdf']))
{
	updatePDFPrefs($pdfpref, TRUE);
}

e107::getRender()->tablerender(PDF_LAN_35, eMessage::getInstance()->render());


/*
if(isset($message))
{
	$caption = PDF_LAN_1;
	$ns -> tablerender($caption, $message);
}
*/


$fontList = getFontList();
$coreList = array();
foreach ($fontList as $font => $info)
{
	if ($info['info']['type'] == 'core')
	{
		$coreList[$font] = $font;
	}
}


$text = "
".$rs -> form_open("post", e_SELF, "pdfform", "", "enctype='multipart/form-data'")."
<table class='table adminform'>

<tr>
	<td>".PDF_LAN_5."</td>
	<td>".$rs -> form_text("pdf_margin_left", 10, $pdfpref['pdf_margin_left'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_6."</td>
	<td>".$rs -> form_text("pdf_margin_right", 10, $pdfpref['pdf_margin_right'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_7."</td>
	<td>".$rs -> form_text("pdf_margin_top", 10, $pdfpref['pdf_margin_top'], 10)."</td>
</tr>";

$text .= "
<tr>
	<td>".PDF_LAN_8."</td>
	<td>
		".$rs -> form_select_open("pdf_font_family");
		foreach($coreList as $font => $info)
		{
			$text .= $rs -> form_option($font, ($pdfpref['pdf_font_family'] == $font ? "1" : "0"), $font);
		}
		$text .= $rs -> form_select_close()."
	</td>
</tr>

<tr>
	<td>".PDF_LAN_9."</td>
	<td>".$rs -> form_text("pdf_font_size", 10, $pdfpref['pdf_font_size'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_10."</td>
	<td>".$rs -> form_text("pdf_font_size_sitename", 10, $pdfpref['pdf_font_size_sitename'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_11."</td>
	<td>".$rs -> form_text("pdf_font_size_page_url", 10, $pdfpref['pdf_font_size_page_url'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_12."</td>
	<td>".$rs -> form_text("pdf_font_size_page_number", 10, $pdfpref['pdf_font_size_page_number'], 10)."</td>
</tr>
<tr>
	<td>".PDF_LAN_13."</td>
	<td>
		".$rs -> form_radio("pdf_show_logo", "1", ($pdfpref['pdf_show_logo'] ? "1" : "0"), "", "").PDF_LAN_3."
		".$rs -> form_radio("pdf_show_logo", "0", ($pdfpref['pdf_show_logo'] ? "0" : "1"), "", "").PDF_LAN_4."
	</td>
</tr>
<tr>
	<td>".PDF_LAN_14."</td>
	<td>
		".$rs -> form_radio("pdf_show_sitename", "1", ($pdfpref['pdf_show_sitename'] ? "1" : "0"), "", "").PDF_LAN_3."
		".$rs -> form_radio("pdf_show_sitename", "0", ($pdfpref['pdf_show_sitename'] ? "0" : "1"), "", "").PDF_LAN_4."
	</td>
</tr>
<tr>
	<td>".PDF_LAN_15."</td>
	<td>
		".$rs -> form_radio("pdf_show_page_url", "1", ($pdfpref['pdf_show_page_url'] ? "1" : "0"), "", "").PDF_LAN_3."
		".$rs -> form_radio("pdf_show_page_url", "0", ($pdfpref['pdf_show_page_url'] ? "0" : "1"), "", "").PDF_LAN_4."
	</td>
</tr>
<tr>
	<td>".PDF_LAN_16."</td>
	<td>
		".$rs -> form_radio("pdf_show_page_number", "1", ($pdfpref['pdf_show_page_number'] ? "1" : "0"), "", "").PDF_LAN_3."
		".$rs -> form_radio("pdf_show_page_number", "0", ($pdfpref['pdf_show_page_number'] ? "0" : "1"), "", "").PDF_LAN_4."
	</td>
</tr>
<tr>
	<td>".PDF_LAN_20."</td>
	<td>
		".$rs -> form_radio("pdf_error_reporting", "1", ($pdfpref['pdf_error_reporting'] ? "1" : "0"), "", "").PDF_LAN_3."
		".$rs -> form_radio("pdf_error_reporting", "0", ($pdfpref['pdf_error_reporting'] ? "0" : "1"), "", "").PDF_LAN_4."
	</td>
</tr>
</table>
<div class='buttons-bar center'>
	".$rs->form_button('submit', 'update_pdf', LAN_UPDATE)."&nbsp;&nbsp;&nbsp;&nbsp;
	".$rs->form_button('submit', 'default_pdf', PDF_LAN_34)."
</div>
".$rs -> form_close()."
";

$ns -> tablerender(PDF_LAN_2, $text);


$text = "
<table class='table adminform'>
<tr><th>".PDF_LAN_21."</th><th>".PDF_LAN_22."</th><th>".PDF_LAN_23."</th>
	<th>".PDF_LAN_24."</th><th title='".PDF_LAN_25."'>".PDF_LAN_26."</th></tr>\n";

foreach ($fontList as $font => $info)
{
	$wa = array(PDF_LAN_27);
	if (isset($info['b'])) $wa[] = PDF_LAN_28;
	if (isset($info['i'])) $wa[] = PDF_LAN_29;
	if (isset($info['bi'])) $wa[] = PDF_LAN_30;
	$variants = implode(', ', $wa);
	$text .= "<tr><td>{$font}</td><td>{$info['info']['type']}</td><td>{$variants}</td><td>{$info['info']['weight']}</td><td>{$info['info']['codes']}</td></tr>\n";
}

$text .= '</table>';
$ns->tablerender(PDF_LAN_31, $text);


require_once(e_ADMIN.'footer.php');

?>