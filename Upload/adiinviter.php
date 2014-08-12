<?php
/*******************************************************************************************
 * AdiInviter Pro 0.7 Lite Edition for vBulletin by AdiInviter, Inc.                       *
 *-----------------------------------------------------------------------------------------*
 *                                                                                         *
 * https://www.adiinviter.com                                                              *
 *                                                                                         *
 * Copyright © 2005-2014, AdiInviter, Inc. All rights reserved.                            *
 * You may not redistribute this file or its derivatives without written permission.       *
 *                                                                                         *
 * Sales Email: sales@adiinviter.com                                                       *
 *                                                                                         *
 *-------------------------------------LICENSE AGREEMENT-----------------------------------*
 * 1. You are free to download and install this plugin on any vBulletin forum for which    *
 *    you hold a valid vBulletin license.                                                  *
 * 2. You ARE NOT allowed to REMOVE or MODIFY the copyright text within the .php files     *
 *    themselves.                                                                          *
 * 3. You ARE NOT allowed to DISTRIBUTE the contents of any of the included files.         *
 * 4. You ARE NOT allowed to COPY ANY PARTS of the code and/or use it for distribution.    *
 ******************************************************************************************/

define('ADIINVITER_SCRIPT', 1);
require_once('global.php');

define('ADI_DS', DIRECTORY_SEPARATOR);

abstract class Adi_Base
{
	public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1';
	public $inviter = null;
	public $error = '';
	public $channel = null;
	public $c_path = '';
	function __construct()
	{
		$cp = ini_get('session.cookie_path');
		if(!file_exists($cp))
			if(file_exists('/tmp'))
				$cp = '/tmp';
			else
				$this->error = 'Cookie path not valid.';
		$sid = 'adi_'.time().'.'.rand(1,10000);
		$cp = trim($cp,' /\\').ADI_DS.$sid;
		if(!file_exists($cp)) { $fop = fopen($cp,"wb"); fclose($fop); }

		$this->channel = curl_init();
		curl_setopt_array($this->channel, array(
			10018 => $this->user_agent, 52 => false, 10031 => $cp, 42 => false, 64 => false, 81 => false, 84 => 1, 19913 => true, 10082 => $cp, 78 => 15, 13 => 15, 58 => true,
		));
		$this->c_path = $cp;
	}
	function adi_get_sub_str($hs, $st, $ed)
	{
		$sl = strlen($st);
		if( (($spo = strpos($hs,$st)) !== false) && (($epo = strpos($hs,$ed,$spo)) !== false) )
		{
			return substr($hs,$st,$ed-$st);
		}
		return '';
	}
	function adi_get_hidden_inputs($html)
	{
		$inputs = array();
		if(!empty($html))
		{
			preg_match_all('#<input[^>]+type=[\'"]hidden[\'"][^>]+>#isU', $html, $matches);
			if(isset($matches[0]))
			{
				foreach($matches[0] as $inp)
				{
					preg_match_all('/ (name|value)=[\'"]([^\'"]*)[\'"]/', $inp, $mat);
					$name = ($mat[1][0] == 'name') ? $mat[2][0] : (isset($mat[1][1]) && $mat[1][1] == 'name' ? $mat[2][1] : '');
					$value = ($mat[1][0] == 'value') ? $mat[2][0] : (isset($mat[1][1]) && $mat[1][1] == 'value' ? $mat[2][1] : '');
					$inputs[$name] = $value;
				}
			}
		}
		return $inputs;
	}
	function adi_get_follow_url($res, $prev_url)
	{
		$follow_url='';
		if(strpos($res, 'HTTP/1.1 3') !== false || strpos($res, 'HTTP/1.0 3') !== false)
		{
			preg_match('/location: ([^\s]+)/', $res, $matches);
			if(isset($matches[1]))
			{
				$follow_url = $matches[1];
				if(strpos($follow_url, 'http') === false)
				{
					$parts=parse_url($prev_url);
					$follow_url=$parts['scheme'].'://'.$parts['host'].($follow_url{0}=='/'?'':'/').$follow_url;
				}
			}
		}
		return $follow_url;
	}
	function adi_destroy_cookie()
	{
		if(!empty($this->c_path) && file_exists($this->c_path))
		{
			unlink($this->c_path);
		}
	}
	function adi_fetch_html($url, $post_data = array(), $options = array(), $http_headers = array())
	{
		// follow, header, quiet, referer, raw_data
		$options = array_merge(array(false,false,true,false), $options);
		$pc = count($post_data);
		$p_str = $sep = '';
		$opts = array(10002 => $url, 47 => ($pc > 0), 80 => ($pc == 0), 10023 => $http_headers, 42 => ($options[0] || $options[1]), 10016 => ($options[3]?$options[3]:''), );
		if($pc>0) {
			foreach($post_data as $name => $val) {
				$p_str .= $sep.$name.'='.urlencode($val);
				$sep = '&';
			}
			$opts[10015] = $p_str;
		}
		curl_setopt_array($this->channel, $opts);
		$html = curl_exec($this->channel);
		if($matches[0]) {
			$next_url = $this->adi_get_follow_url($html, $url);
			if(!empty($next_url)) {
				$html = $this->adi_fetch_html($next_url, array(), $options, $http_headers);
			}
		}
		return $html;
	}
	public function adi_is_email($email)
	{
		return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email);
	}
	public function adi_fetch_contacts($email, $password)
	{
		$contacts = $this->adi_get_contacts($email, $password);
		$this->adi_logout();
		$this->adi_destroy_cookie();
		return $contacts;
	}
	abstract function adi_get_contacts($email, $password);
	abstract function adi_logout();
}
function UTF_to_Unicode($input, $array=False)
{
	if(strlen($input) == 0) return $input;
    $value = '';
    $val   = array();
    for($i=0; $i< strlen( $input ); $i++)
    {
	    $ints  = ord ( $input[$i] );
	    $z     = ord ( $input[$i] );
	    $y     = ord ( $input[$i+1] ) - 128;
	    $x     = ord ( $input[$i+2] ) - 128;
	    $w     = ord ( $input[$i+3] ) - 128;
	    $v     = ord ( $input[$i+4] ) - 128;
	    $u     = ord ( $input[$i+5] ) - 128;
	    if( $ints >= 0 && $ints <= 127 ){
	        $value[] = $input[$i];
	    }
	    if( $ints >= 192 && $ints <= 223 ){
	        $value[]= '&#'.(($z-192) * 64 + $y).';';
	    }
	    if( $ints >= 224 && $ints <= 239 ){
	        $value[]= '&#'.(($z-224) * 4096 + $y * 64 + $x).';';
	    }
	    if( $ints >= 240 && $ints <= 247 ){
	        $value[]= '&#'.(($z-240) * 262144 + $y * 4096 + $x * 64 + $w).';';
	    }
	    if( $ints >= 248 && $ints <= 251 ){
	        $value[]= '&#'.(($z-248) * 16777216 + $y * 262144 + $x * 4096 + $w * 64 + $v).';';
	    }
	    if( $ints == 252 || $ints == 253 ){
	        $value[]= '&#'.(($z-252) * 1073741824 + $y * 16777216 + $x * 262144 + $w * 4096 + $v * 64 + $u).';';
	    }
	    if( $ints == 254 || $ints == 255 ){
	        $contents.="Something went wrong while translating non-english text!<br />";
	    }
	}
	if( $array === False ){
		$unicode = '';
		foreach($value as $value){
			$unicode .= $value;
		}
		return $unicode;
	}
	if($array === True ){
	 	return $value;
	}
}

class Adi_gmail extends Adi_Base
{
	private $login_ok = '';
	public function adi_get_contacts($user,$pass)
	{
		$contacts = array();
		$post_data=array('accountType'=>'HOSTED_OR_GOOGLE','Email'=>$user,'Passwd'=>$pass,'service'=>'cp','source'=>'AdiInviter-AdiInviter-3.2.0');
		$html = $this->adi_fetch_html("https://www.google.com/accounts/ClientLogin", $post_data, array(true));
		if(strpos($html,'Auth=') === false) {
			return "Email or password is incorrect.";
		}
		$auth = substr($html,strpos($html,'Auth=')+strlen('Auth='));
		$html = $this->adi_fetch_html("https://www.google.com/m8/feeds/contacts/default/full?alt=json&max-results=2000", array(), array(true,false,true,false), array("Authorization: GoogleLogin auth={$auth}", "encoding: UTF-8"));
		if(strpos($html, '"feed"') === false) {
			return 'Unable to get contacts.';
		}
		else if(strpos($html, '@') === false) {
			return 'No contacts in your addressbook.';
		}
		else {
			$conts = json_decode($html, true);
			if(isset($conts['feed']['entry']))
			{
				foreach($conts['feed']['entry'] as $contact)
				{
					$name = UTF_to_Unicode($contact['title']['$t']);
					$email = $contact['gd$email'][0]['address'];
					if($name == ""){ $name = preg_replace('/@.*/', '', $email); }
					$contacts[$email] = $name;
				}
			}
		}
		if(count($contacts) == 0) {
			return 'No contacts in your addressbook.';
		}
		else {
			return $contacts;
		}
	}
	public function adi_logout()
	{
		$this->adi_fetch_html('https://mail.google.com/mail/u/0/?logout&hl=en&hlor');
		return true;
	}
}

class Adi_yahoo extends Adi_Base
{
	private $login_ok = '';
	public function adi_get_contacts($user,$pass)
	{
		$contacts = array();
		$html = $this->adi_fetch_html("https://login.yahoo.com/config/mail?.intl=us&rl=1", array(), array(true));
		$post_data = $this->adi_get_hidden_inputs($html);
		$post_data[".ws"]="1"; $post_data["save"]="";
		$post_data['login']=$user; $post_data['passwd']=$pass;

		unset($post_data['.z']);
		unset($post_data['.2ndChallenge_type_in']);
		unset($post_data['.2ndChallenge_pwqa_quest_in']);

	   $html = htmlentities($this->adi_fetch_html("https://login.yahoo.com/config/login?", $post_data, array(true)));
	   
	   if(strpos($html, 'yahoo.com/neo/launch') === false)
	   {
	   	return 'Email or Password is incorrect.';
	   }
	   preg_match('/https:\/\/([^\/]+)/', $html, $matches);
	   $my_server = $matches[1];
	   $html = $this->adi_fetch_html("https://".$my_server."/yab-fe/mu/MainView",array(), array(true));

	   preg_match('/neoguid: "([^"]+)"/', $html, $matches); $neoguid = $matches[1];
		preg_match('/wssid: "([^"]+)"/', $html, $matches); $wssid = $matches[1];

		$url = 'https://'.$my_server.'/neo/ws/sd?/v1/user/'.$neoguid.'/contacts;out=guid,nickname,email,yahooid,otherid,phone,jobtitle,company,notes,link,custom,name,address,birthday,anniversary,image;count=max?format=json&view=compact&wssid='.$wssid;
		$html = $this->adi_fetch_html($url, array(), array(false));

		if(strpos($html, '"contacts"') === false) {
			return 'Unable to get contacts.';
		}
		else if(strpos($html, '@') === false) {
			return 'No contacts in your addressbook.';
		}
		else {
			$conts = json_decode($html, true);
			if(is_array($conts) && count($conts) > 0 && isset($conts['contacts']))
			{
				foreach($conts['contacts']['contact'] as $details)
				{
					$name = $email = '';
					foreach($details['fields'] as $fdetails)
					{
						if($fdetails['type'] == 'name') {
							$name = UTF_to_Unicode(trim(trim($fdetails['value']['givenName'].' '.$fdetails['value']['middleName']).' '.$fdetails['value']['familyName']));
						}
						else if($fdetails['type'] == 'email') {
							$email = $fdetails['value'];
						}
					}
					if(!empty($email))
					{
						if(empty($name)) {
							$name = preg_replace('/@.*/', '', $email);
						}
						$contacts[$email] = $name;
					}
				}
			}
		}
		if(count($contacts) == 0) {
			return 'No contacts in your addressbook.';
		}
		else {
			return $contacts;
		}
	}
	public function adi_logout()
	{
		$this->adi_fetch_html('https://login.yahoo.com/config/login?logout=1&.direct=2&.done=http://www.yahoo.com&.src=cdgm&.intl=us&.lang=en-US');
		return true;
	}
}

global $vbulletin;
if($vbulletin->options['adi_system_onoff'] != 1)
{
	exec_header_redirect('forum.php');
}
if($vbulletin->userinfo['userid']+0 === 0 && $vbulletin->options['adi_can_guest_use'] != 1)
{
	exec_header_redirect('forum.php');
}

$adiinviter_service = isset($_GET['adiinviter_service']) ? $_GET['adiinviter_service'] : 'gmail';
error_reporting(E_ALL & ~E_NOTICE);
define('THIS_SCRIPT', 'adiinviter');
define('CSRF_PROTECTION', true);

$contents = '';
$pagetitle = 'Invite Friends';

$step = isset($_POST['adi_step']) ? $_POST['adi_step'] : 'login_form';
$substep = '';

$contents .= <<<EOD
<script type="text/javascript">
function adi_trim(s) { return s.replace(/^\s+|\s+$/, ''); }
</script>
EOD;

$adi_error = false; $adi_error_message = '';



/*************************** Grab Contacts *********************************************/
if($step == 'invite_sender')
{
	$adi_importer_type = strtolower(isset($_POST['adi_importer_type']) ? $_POST['adi_importer_type'] : 'addressbook');
	$contacts = array();
	if($adi_importer_type == 'addressbook')
	{
		$service_key = strtolower(isset($_POST['adi_service_key']) ? $_POST['adi_service_key'] : '');
		$adi_email = isset($_POST['adi_email']) ? $_POST['adi_email'] : '';
		$adi_password = isset($_POST['adi_password']) ? $_POST['adi_password'] : '';
		if($service_key != 'gmail' && $service_key != 'yahoo')
		{
			$adi_error = true; $adi_error_message = 'Invalid Service.';
		}
		if(empty($adi_email) || empty($adi_password))
		{
			$adi_error = true; $adi_error_message = 'Please enter valid Login credentials.';
		}

		if(!$adi_error)
		{
			$inviter_class = 'Adi_'.$service_key;
			if(class_exists($inviter_class))
			{
				$inviter = new $inviter_class();
				$contacts = $inviter->adi_fetch_contacts($adi_email, $adi_password);
			}
			else
			{
				$adi_error = true; $adi_error_message = 'Invalid service specified.';
			}
		}
	}
	else if($adi_importer_type == 'manual')
	{
		$adi_contacts_list = isset($_POST['adi_contacts_list']) ? $_POST['adi_contacts_list'] : '';
		if(empty($adi_contacts_list))
		{
			$adi_error = true; $adi_error_message = 'Please enter atleast 1 contact in your contacts list.';
		}
		if(strpos($adi_contacts_list, '@') === false)
		{
			$adi_error = true; $adi_error_message = 'No contacts found.';
		}

		if(!$adi_error)
		{
			$adi_contacts_list = substr($adi_contacts_list, 0, 2000);
			$adi_contacts_list = preg_replace('/\s+/', ' ', $adi_contacts_list);
			$unparsed_contacts = explode(',', $adi_contacts_list);
			foreach($unparsed_contacts as $up_contact)
			{
				$up_contact = trim($up_contact, " <>:\"'\t");
				$parts = preg_split('/[<> :"\']/', $up_contact);
				$email_regex = '/[a-z0-9\.\-_]+@[a-z0-9\-_]+\.[a-z0-9\.]+/i';
				$email = $parts[count($parts)-1];
				if(preg_match($email_regex, $email))
				{
					$parts[count($parts)-1] = '';
					$line = implode(' ', $parts);
					$line = preg_replace('/\s+/', ' ', $line);
					$name = trim($line);
					if(empty($name))
					{
						$name = preg_replace('/@.*/', '', $email);
					}
					if(!empty($email) && !empty($name))
					{
						$contacts[strtolower($email)] = UTF_to_Unicode($name);
					}
				}
			}
		}
	}

	if(is_array($contacts) && count($contacts) > 0)
	{
		$contents .= <<<EOD
<center>
<br>
	<table cellpadding="0" cellspacing="0" class="adi_clear_table">
	<tr class="adi_clear_tr">
		<td class="adi_clear_td">
			<div class="adi_login_form_width">
			<div class="adi_block_header" style="margin-bottom: 15px;">Invite Friends</div>
			<div class="adi_block_section_outer">

				<div class="adi_secondary_text">Select which contacts to invite from the list below.</div>

				<form class="adi_clear_form" action="" method="post" onSubmit="return adi_invite_sender_submited(event,this);">
				<input type="hidden" name="adi_step" value="send_invites">
				<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_contacts_table" width="100%">
				<tr>
					<th width="40"><center><input type="checkbox" name="adi_conts[$email]" value="$urlencoded_name" onchange="adi_select_all_changed(this);"></center></th>
					<th><div class="adi_contact_name">Name</div></th>
					<th><div class="adi_contact_name">Email</div></th>
				</tr>
				</table>

				<div class="adi_contacts_list_out">
				<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_contacts_table" width="100%">
EOD;
			foreach($contacts as $email => $name)
			{
					$urlencoded_name = urlencode($name);
		$contents .= <<<EOD
				<tr onclick="return adi_contact_row_clicked(event,this);">
					<td width="40"><center><input type="checkbox" name="adi_conts[$email]" class="adi_selectable" value="$urlencoded_name"></center></td>
					<td><div class="adi_contact_name">$name</div></td>
					<td><div class="adi_contact_name">$email</div></td>
				</tr>
EOD;
			}

		$contents .= <<<EOD
				</table>
				</div>
				<div style="text-align:right;margin-top: 15px;margin-bottom: 15px;">
				<input name="adi_invite_sender_submit" type="button" class="adi_button newcontent_textcontrol" value="Back" style="margin-left: 15px;float:left;" onclick="adi_submit_skip();">
					<input name="adi_invite_sender_submit" type="submit" class="adi_button adi_send_button newcontent_textcontrol" value="Send Invitations" style="margin-right: 15px;float:right;">
					<div style="clear:both;"></div>
				</div>
				</form>
			</div>
			</div>
		</td>
	</tr>
	</table>
</center>
<form class="adi_clear_form" id="adi_skip_form" style="display:none;" action="" method="get"></form>

<script type="text/javascript">
function adi_select_all_changed(m)
{
	var eles = YAHOO.util.Dom.getElementsByClassName("adi_selectable");
	if(m.checked)
	{
		for(var i in eles)
		{
			eles[i].checked = true;
		}
	}
	else
	{
		for(var i in eles)
		{
			eles[i].checked = false;
		}
	}
}
function adi_contact_row_clicked(e,m)
{
	var obj = e.target;
	if(obj.className != 'adi_selectable')
	{
		var ele = YAHOO.util.Dom.getElementsByClassName("adi_selectable", 'input', m);
		ele[0].checked = !ele[0].checked;
	}
}
function adi_invite_sender_submited(e,m)
{
	var issend = false;
	var eles = YAHOO.util.Dom.getElementsByClassName("adi_selectable");
	for(var i in eles)
	{
		if(eles[i].checked)
		{
			issend = true;
		}
	}
	if(!issend)
	{
		alert('Please select atleast 1 contact.');
	}
	return issend;
}
function adi_submit_skip()
{
	var frm = document.getElementById('adi_skip_form');
	frm.submit();
}
</script>

EOD;
	}
	else
	{
		if(is_string($contacts) && strlen($contacts) > 0)
		{
			$adi_error_message = $contacts;
		}
		$step = 'login_form';
	}
}
/************************************************************************************/



/*************************** Send Invites *********************************************/
if($step == 'send_invites')
{
	$adi_conts = isset($_POST['adi_conts']) ? $_POST['adi_conts'] : array();
	if(is_array($adi_conts) && count($adi_conts) > 0)
	{
		$email_regex = '/[a-z0-9\.\-_]+@[a-z0-9\-_]+\.[a-z0-9\.]+/i';
		if(!class_exists('vB_Mail', false))
		{
			require_once(DIR . '/includes/class_mail.php');
		}
		$sent_mail_counter = 0;
		foreach($adi_conts as $email => $name)
		{
			if(preg_match($email_regex, $email) == 1)
			{
				$name = urldecode($name);

				$forumpath = $vbulletin->options['bburl'];
				$subject = $vbulletin->options['adi_invitation_subject'];
				$message = $vbulletin->options['adi_invitation_body'];
				$message = '<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta charset="UTF-8">
	<title>Welcome to our AdiInviter Pro</title>
</head>
<body>'.$message;

				$message .= '</body> </html>';

				$sendnow = false; $from = ''; $uheaders = ''; $username = '';
				if($mail = vB_Mail::fetchLibrary($vbulletin, !$sendnow AND $vbulletin->options['usemailqueue']))
				{
					if($mail->start($email, $subject, $message, $from, $uheaders, $username))
					{
						$mail->headers = str_replace('Content-Type: text/plain', 'Content-Type: text/html', $mail->headers);
						$mail->send();
						$sent_mail_counter++;
					}
				}
			}
		}

		if($sent_mail_counter > 0)
		{
			$adi_display_message = $sent_mail_counter.' invitation(s) sent successfully.';
			$step = 'show_message';
		}
		else if($sent_mail_counter === 0)
		{
			$adi_display_message = 'Failed to send invitations.';
			$step = 'show_message';
		}
	}
	else
	{
		$adi_error = true; $adi_error_message = 'Please select atleast 1 contact.';
		$step = 'login_form';
	}
}
/************************************************************************************/





/*************************** Show Message *********************************************/
if($step == 'show_message')
{
	$adi_display_message = isset($adi_display_message) ? $adi_display_message : '';
	$contents .= <<<EOD
<center>
<br>
	<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_message_table">
	<tr class="adi_clear_tr">
		<td class="adi_clear_td">
			<div class="adi_login_form_width">
				<div class="adi_block_header">Invite Friends</div>
				<div class="adi_block_section_outer">
				<center>
				<div class="adi_ack_text">$adi_display_message</div>
				<form action="" method="post" class="adi_clear_form">
					<input type="submit" class="adi_button adi_login_button newcontent_textcontrol" value="Invite More">
				</form>
				</center>
				</div>
			</div>
		</td>
	</tr>
	</table>
</center>
EOD;
}
/************************************************************************************/


/*************************** Login Form *********************************************/
if($step == 'login_form')
{
	$error_visibility = ($adi_error ? 'visibility:visible;' : '');

	if(!empty($adi_error_message))
	{
		$contents .= <<<EOD
<script type="text/javascript">
YAHOO.util.Event.on(window, 'load', function(){
	alert('$adi_error_message');
});
</script>
EOD;
	}


	// Login form
	$contents .= <<<EOD

<div class="adi_mask adi_stick_borders" id="adi_mask"></div>
<div class="adi_popup adi_stick_borders" id="adi_sf_popup">
<table cellpadding="0" cellspacing="0" width="100%" class="adi_clear_table" style="height: 100%;">
<tr class="adi_clear_tr"><td class="adi_clear_td" valign="middle" style="vertical-align: middle;">
<center>
	<table cellpadding="0" cellspacing="0" class="adi_clear_table">
	<tr class="adi_clear_tr"><td class="adi_clear_td" valign="middle" style="vertical-align: middle;">
	<div class="adi_sf_popup_outer">
		<div class="adi_pp_subhead">List of supported formats</div>
		<div class="adi_formats_div">
			<ol class="adi_clear_ol adi_formats_list">
				<li class="adi_clear_li"><span class="adi_plain_text">
					<font class="adi_format_name">John Miller</font> <font class="adi_format_email">&lt;john_miller@gmail.com&gt;</font>,
					<font class="adi_format_name">Victor Brown</font> <font class="adi_format_email">&lt;victor.brown@live.com&gt;</font>,..
				</span></li>
				<li class="adi_clear_li">
					<span class="adi_plain_text">
					<font class="adi_format_name">"Mary Cliff"</font> <font class="adi_format_email">&lt;"mary_cliff@me.com"&gt;</font>,
					<font class="adi_format_name">"Luke Conrad"</font> <font class="adi_format_email">&lt;"luke_conrad@yahoo.com"&gt;</font>,.. </span>
				</li>
				<li class="adi_clear_li">
					<span class="adi_plain_text">
					<font class="adi_format_email">marian_grant@gmail.com</font>, <font class="adi_format_email">brad.warren@mail.com</font>,..</span>
				</li>
				<li class="adi_clear_li">
				<span class="adi_plain_text">
					<font class="adi_format_name">Sandra Ross</font> : <font class="adi_format_email">sandra.ross@gmx.net</font>,
					<font class="adi_format_name">Eric Perry</font> : <font class="adi_format_email">eric.perry@email.it</font>,.. </span>
				</li>
				<li class="adi_clear_li">
					<span class="adi_plain_text">
					<font class="adi_format_email"> brad.warren@mail.com</font>, 
					<font class="adi_format_name">John Miller</font> <font class="adi_format_email">&lt;john_miller@gmail.com&gt;</font>,..</span>
				</li>
			</ul>
		</div>
		<div class="adi_pp_subhead">You can use combinations of formats specified above.</div>
		<div class="adi_act_btns">
			<input type="button" class="adi_button newcontent_textcontrol" value="OK" onclick="adi_close_popup()">
		</div>
	</div>
	</td></tr>
	</table>
</center>
</td></tr>
</table>
</div>

<center>
<br>
	<table cellpadding="0" cellspacing="0" class="adi_clear_table">
	<tr class="adi_clear_tr">
		<td class="adi_clear_td">
			<div class="adi_login_form_width">
				<div class="adi_block_header">Invite Friends</div>
				<div class="adi_block_section_outer">

				<center>

					<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_security_table">
					<tr class="adi_clear_tr"><td class="adi_clear_td">
					<div class="adi_security_text">We will not store your password or login information.</div>
					</td><tr></table>

					<form action="" method="POST" onSubmit="return adi_login_form_check(event, this);">
						<input type="hidden" name="adi_importer_type" value="addressbook">
						<input type="hidden" name="adi_step" value="invite_sender">
						<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_login_table">
						<tr class="adi_clear_tr">
							<td class="adi_clear_td adi_label_col">Service: </td>
							<td class="adi_clear_td adi_input_col">
								<!-- <select name="adi_service_key" class="adi_input adi_gmail_dropdown adi_dropdown_select" onchange="adi_update_service_input(this);">
									<option value="gmail" selected>Gmail</option>
									<option value="yahoo">Yahoo</option>
								</select> -->

								<div class="adi_dropdown_out">
									<img id="adi_dd_up_arrow" src="images/adiinviter/arrow_up.gif">
									<img id="adi_dd_down_arrow" src="images/adiinviter/down_arrow.gif">
									<input type="text" name="adi_service_key_val" id="adi_service_key_val" class="adi_input adi_text_input adi_dropdown_input adi_gmail_dropdown primary textbox" value="Gmail" autocomplete="off" spellcheck='false' onfocus="adi_dd_focus();" onblur="adi_dd_lost_focus();">
									<input type="hidden" name="adi_service_key" id="adi_service_key" value="gmail">
									<div class="adi_dropdown_select" id="adi_dropdown_select">
										<div class="adi_dd_option adi_gmail_dropdown" data="gmail" onclick="adi_dd_option_clicked(this);">Gmail</div>
										<div class="adi_dd_option adi_yahoo_dropdown" data="yahoo" onclick="adi_dd_option_clicked(this);">Yahoo</div>
									</div>
								</div>

							</td>
						</tr>
						<tr class="adi_clear_tr">
							<td class="adi_clear_td adi_label_col">Email: </td>
							<td class="adi_clear_td adi_input_col"><input type="text" name="adi_email" id="adi_email_input" class="adi_input adi_text_input primary textbox" spellcheck='false' value="" autocomplete="off"></td>
						</tr>
						<tr class="adi_clear_tr">
							<td class="adi_clear_td adi_label_col">Password: </td>
							<td class="adi_clear_td adi_input_col"><input type="password" name="adi_password" id="adi_password_input" spellcheck='false' class="adi_input adi_password_input primary textbox" value="" autocomplete="off"></td>
						</tr>
						</table>

						<input name="adi_login_form_submit" type="submit" class="adi_button adi_login_button newcontent_textcontrol" value="Import Contacts">
					</form>
				</center>
				</div>
			</div>
		</td>
	</tr>
	</table>

<br><br><br>

	<table cellpadding="0" cellspacing="0" class="adi_clear_table">
	<tr class="adi_clear_tr">
		<td class="adi_clear_td">
			<div class="adi_login_form_width">
				<div class="adi_block_header">Enter Your Contacts Manually</div>
				<div class="adi_block_section_outer">
				<center>

					<table cellpadding="0" cellspacing="0" class="adi_clear_table adi_security_table">
					<tr class="adi_clear_tr"><td class="adi_clear_td">
					<div class="adi_security_text">We will not send emails to any of your contacts without your direct consent.</div>
					</td><tr>
					<tr class="adi_clear_tr"><td class="adi_clear_td" style="height:10px;"></td</tr>
					<tr class="adi_clear_tr"><td class="adi_clear_td">
					<center><a class="adi_link" href="" onclick="adi_open_popup();return false;">List of supported formats</a></center>
					</td><tr></table>

					<form action="" method="POST" onSubmit="return adi_manual_form_check(event, this);">
						<input type="hidden" name="adi_importer_type" value="manual">
						<input type="hidden" name="adi_step" value="invite_sender">

						<table cellpadding="0" cellspacing="0" class="adi_clear_table">
						<tr class="adi_clear_tr">
							<td class="adi_clear_td">
								<textarea class="adi_input adi_textarea_input" spellcheck='false' name="adi_contacts_list" id="adi_contacts_list"></textarea>
							</td>
						</tr>
						</table>

						<input name="adi_login_form_submit" type="submit" class="adi_button adi_login_button newcontent_textcontrol" value="Import Contacts">
					</form>
				</center>
				</div>
			</div>
		</td>
	</tr>
	</table>

</center>
<br><br>
<script type="text/javascript">
function adi_login_form_check(e,m)
{
	var ei = document.getElementById("adi_email_input"), pi = document.getElementById("adi_password_input");
	var ei_val = adi_trim(ei.value), pi_val = adi_trim(pi.value);

	var ers = '';
	if(ei_val == '' || pi_val == '')
	{
		ers = 'Please fill all the fields.';
	}
	else if(ei_val.match(/@/) == null)
	{
		ers = 'Please enter a valid email address.';
	}
	if(ers != '')
	{
		alert(ers);
		return false;
	}
	return true;
}
function adi_manual_form_check(e,m)
{
	var clist = document.getElementById("adi_contacts_list");
	var cl_val = adi_trim(clist.value);
	if(cl_val == '')
	{
		alert('Please enter atleast 1 contact');
		return false;
	}
	return true;
}
function adi_update_service_input(m)
{
	var clsn = 'adi_'+m.value+'_dropdown';
	if(clsn == 'adi_gmail_dropdown')
	{
		m.className = m.className.replace(/adi_yahoo_dropdown/, '');
		m.className += ' adi_gmail_dropdown';
	}
	else
	{
		m.className = m.className.replace(/adi_gmail_dropdown/, '');
		m.className += ' adi_yahoo_dropdown';
	}
}
function adi_dd_focus()
{
	var o = document.getElementById("adi_dropdown_select");
	o.style.display = 'block';

	var o = document.getElementById("adi_dd_up_arrow");
	o.style.display = 'block';
	var o = document.getElementById("adi_dd_down_arrow");
	o.style.display = 'none';

}
function adi_dd_lost_focus()
{
	/*setTimeout(function(){
		var o = document.getElementById("adi_dropdown_select");
		o.style.display = 'none';
	}, 30);*/
}
function adi_dd_option_clicked(m)
{
	var s = m.getAttribute('data');
	var inp = document.getElementById('adi_service_key_val');
	var inph = document.getElementById('adi_service_key');
	inp.className = inp.className.replace(/adi_yahoo_dropdown|adi_gmail_dropdown/, '');
	if(s == 'gmail')
	{
		inp.className += ' adi_gmail_dropdown';
		inp.value = "Gmail";
		inph.value= "gmail";
	}
	else
	{
		inp.className += ' adi_yahoo_dropdown';
		inp.value = "Yahoo";
		inph.value= "yahoo";
	}
	var o = document.getElementById("adi_dropdown_select");
	o.style.display = 'none';

	var o = document.getElementById("adi_dd_up_arrow");
	o.style.display = 'none';
	var o = document.getElementById("adi_dd_down_arrow");
	o.style.display = 'block';
}
document.onclick = function(e){
	if(e.target.className.match(/adi_dropdown_input/) == null)
	{
		var o = document.getElementById("adi_dropdown_select");
		o.style.display = 'none';

		var o = document.getElementById("adi_dd_up_arrow");
		o.style.display = 'none';
		var o = document.getElementById("adi_dd_down_arrow");
		o.style.display = 'block';
	}
};
function adi_open_popup()
{
	var amk = document.getElementById('adi_mask');
	var asf = document.getElementById('adi_sf_popup');
	amk.style.display = 'block';
	asf.style.display = 'block';
}
function adi_close_popup()
{
	var amk = document.getElementById('adi_mask');
	var asf = document.getElementById('adi_sf_popup');
	amk.style.display = 'none';
	asf.style.display = 'none';
}
</script>
EOD;
} // Login form end

$contents .= '<br><center><div class="adi_power_text">Powered by <a class="adi_link" target="_blank" href="https://www.adiinviter.com">AdiInviter Pro</a></div></center><br><br>';
/************************************************************************/


/************************************************************************/
// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('',);

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array('custom_adiinviter',
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################

vB_Template::preRegister('custom_adiinviter', array('contents' => $contents));
vB_Template::preRegister('custom_adiinviter', array('pagetitle' => $pagetitle));

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$navbits = construct_navbits(array(
	vB::$vbulletin->options['forumhome'] . '.php?' . $vbulletin->session->vars['sessionurl']=> $vbphrase['home'],
	'adiinviter.php' => 'Invite Friends',
));
$navbar = render_navbar_template($navbits);

// ###### NOW YOUR TEMPLATE IS BEING RENDERED ######
//vB_Template::preRegister('header',array('contents' => $contents));
$templater = vB_Template::create('custom_adiinviter');
$templater->register_page_templates();
$templater->register('navbar', $navbar);
$templater->register('pagetitle', $pagetitle);
print_output($templater->render());

?>