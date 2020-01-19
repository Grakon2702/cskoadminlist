<?php

include "steamidphpmaster/SteamID.php";

$vip_or_admin_session_cookie = "XXX" //You need to have account with VIP or admin perms;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Admin {

	public $nick;
	public $steamid;
	public $steamurl;
	public $perms;
	public $forumprofileurl;
	public $forumcolor;
	
	function __construct($nick, $steamid, $steamurl) {
		$this->nick = $nick;
		$this->steamid = $steamid;
		$this->steamurl = $steamurl;
	}
	
	static function cmp_obj($a, $b) {
		$al = strtolower($a->nick);
        $bl = strtolower($b->nick);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
	}
	
}

class Adminlist {
	
	private $admins = array();
	private $count_admins;
	
	function __construct() {
		$this->count_admins = 0;
	}
	
	function get_count($count_of = "nothing") {
		
		if ($count_of === "nothing" or ($count_of !== "Aim" and
										$count_of !== "Fun" and 
										$count_of !== "Furien" and 
										$count_of !== "Jailbreak" and 
										$count_of !== "Jump" and 
										$count_of !== "Knife" and 
										$count_of !== "Trouble in Terrorist Town" and 
										$count_of !== "Zombie" and
										$count_of !== "Všechny"))										
			return $this->count_admins;
		else {
			
			$n = 0;
			for ($i = 0; $i < $this->count_admins; $i++) {
				if (strpos($this->admins[$i]->perms, $count_of) !== false or strpos($this->admins[$i]->perms, "Všechny") !== false)
					$n++;
			}
		}
		
		return $n;
		
	}
	
	function new_admin($nick, $steamid, $steamurl) {
		array_push($this->admins, new Admin($nick, $steamid, $steamurl));
		usort($this->admins, array("Admin", "cmp_obj"));
		$this->count_admins++;		
	}
	
	function get_admin($index_or_nick_or_steamid) {
		
		if (is_numeric($index_or_nick_or_steamid)) {
			
			if($index_or_nick_or_steamid > $this->count_admins)
				return $this->admins[count_admins];
			elseif ($index_or_nick_or_steamid < 0)
				return $this->admins[0];
			else
				return $this->admins[$index_or_nick_or_steamid];
			
		}
		elseif (substr($index_or_nick_or_steamid, 0, 5) === "STEAM") {
			
			for($i = 0; $i < $this->count_admins; $i++) {
				if ($index_or_nick_or_steamid === $this->admins[$i]->steamid)
					return $this->admins[$i];
				else
					continue;
			}
			
		}
		else {
			
			for($i = 0; $i < $this->count_admins; $i++) {
				if ($index_or_nick_or_steamid === $this->admins[$i]->nick)
					return $this->admins[$i];
				else
					continue;
			}
			
			return 1;
			
		}
		
	}
}

$categories = array(
"Aim" => 27015,
"Fun" => 27068,
"Furien" => 27034,
"Jailbreak" => 27041,
"Jump" => 27025,
"Knife" => 27019,
"Trouble in Terrorist Town" => 27111,
"Zombie" => 27017
);

//loading steam ids and urls
$adminlist = new Adminlist();

$admins = explode("<option ", explode("</select>", explode("<select name='admin'", file_get_contents('https://banlist.csko.cz/ban_search.php'))[1])[0]);
unset($admins[0]);

foreach($admins as $admin){
    $steamid = explode('"', explode('value="', $admin)[1])[0];
    $nick = explode('"', explode('label="', $admin)[1])[0];
	
	//correction of user Kotel
	if ($steamid === "STEAM_0:1:4411144000")
		$steamid = "STEAM_0:1:4411144";
	
	try {
		$s = new SteamID($steamid);
	}
	catch( InvalidArgumentException $e ) {
		echo 'Given SteamID could not be parsed.';
	}
	
	$steamurl = "http://steamcommunity.com/profiles/" . $s->ConvertToUInt64();
	
	$adminlist->new_admin($nick, $steamid, $steamurl);
}

//loading permissions

$dataServers = array();
$dataAdmins = array();

$ch = curl_init('https://csko.cz/adminlist/show1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=$vip_or_admin_session_cookie');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$html = curl_exec($ch);
curl_close($ch);


//loading ports
$servers = explode("<th> ", explode('<th> Nick </th>', explode('<th> Celkem </th>', $html)[1])[0]);
unset($servers[0]);

foreach($servers as $server){
	$dataServers[] = trim(str_replace(" </th>", "", $server));
}

//assigning permissions to admins
$admins = explode('<td style="border: ', $html);
unset($admins[0]);

foreach($admins as $admin){
	
	$nick = explode(' </h3>', explode('<h3>', $admin)[1])[0];
	
	if(!isset($dataAdmins[$nick])){
	$currentAdmin = array();
	
	$servers = explode('<td ', $admin);
	unset($servers[0]);
	unset($servers[1]);
	
	$i = 0;
	foreach($servers as $server){
		if (strpos($server, '#0B770C') !== false)
			$currentAdmin[$dataServers[$i]] = true;
		else
			$currentAdmin[$dataServers[$i]] = false;
		
		$i++;
	}
	$dataAdmins[$nick] = $currentAdmin;
	}
}

for ($i = 0; $i < $adminlist->get_count(); $i++) {
	
	$nick = $adminlist->get_admin($i)->nick;
	
	$perms = array();
	
	if(isset($dataAdmins[$nick])){
		
		foreach($categories as $catname => $catport){
			if($dataAdmins[$nick][$catport] == true) $perms[] = $catname;
		}
	
		if(count($categories) == count($perms))
			$adminlist->get_admin($nick)->perms = "Všechny";
		else
			$adminlist->get_admin($nick)->perms = implode(", ", $perms);
		
	}	
}

//loading profile urls and colors
$blocks = explode("<h4 class=\"blocksubhead\">", explode("<div id=\"pagetitle\">", file_get_contents("https://csko.cz/forum/showgroups.php"))[1]);
unset($blocks[0]);
unset($blocks[235]);

$maintags = Array();

foreach($blocks as $block) {
	$maintags[] = explode("</h4>", $block)[0];
}

$maintags = array_unique($maintags);

foreach($maintags as $maintag) {
	$separation = explode("color=", $maintag);
	
	if (count($separation) == 2) {
		$nick = explode(">", explode("</font>", $separation[1])[0])[1];
		$color = explode(" style=" ,explode(">" , explode("</font>" ,$separation[1])[0])[0])[0];
	}
	else {
		$nick = explode("</a>", explode("\">", $separation[0])[1])[0];
		$color = "#417394";
	}
	
	$url = explode("?", explode("\" class=", $maintag)[0])[1];
	$url = substr($url, 0, -39);
	
	for ($i = 0; $i < $adminlist->get_count(); $i++) {
		if ($adminlist->get_admin($nick) !== 1) {
			$adminlist->get_admin($nick)->forumcolor = $color;
			$adminlist->get_admin($nick)->forumprofileurl = "https://csko.cz/forum/member.php?" . $url;
		}
		else
			continue;
	}
}

/*for ($i = 0; $i < $adminlist->get_count(); $i++)
	echo $adminlist->get_admin($i)->nick . "</br>";*/



$count_of_all = $adminlist->get_count();
$count_of_globals = $adminlist->get_count("Všechny");
$count_of_aim = $adminlist->get_count("Aim");
$count_of_fun = $adminlist->get_count("Fun");
$count_of_furien = $adminlist->get_count("Furien");
$count_of_jailbreak = $adminlist->get_count("Jailbreak");
$count_of_jump = $adminlist->get_count("Jump");
$count_of_knife = $adminlist->get_count("Knife");
$count_of_ttt = $adminlist->get_count("Trouble in Terrorist Town");
$count_of_zombie = $adminlist->get_count("Zombie");
?>

<!doctype html>
<html lang="cs">
	<head>
		<title>Adminlist Kotelny</title>
		<meta charset="utf-8">
	</head>
	<body>
		<p>Počet administrátorů:</p>
		<ul>
			<li><b>Všech:</b> <?php echo $count_of_all; ?></li>
			<li><b>S právy na všech serverech:</b> <?php echo $count_of_globals; ?></li>
			<li><b>Na sekci:</b>
				<ul>
					</br>
					<li>Aim: <?php echo $count_of_aim; ?></li>
					<li>Fun: <?php echo $count_of_fun; ?></li>
					<li>Furien: <?php echo $count_of_furien; ?></li>
					<li>Jailbreak: <?php echo $count_of_jailbreak; ?></li>
					<li>Jump: <?php echo $count_of_jump; ?></li>
					<li>Knife: <?php echo $count_of_knife; ?></li>
					<li>Trouble in Terrorist Town: <?php echo $count_of_ttt; ?></li>
					<li>Zombie: <?php echo $count_of_zombie; ?></li>
				</ul>
			</li>
		</ul>
		</br></br>
		<table width="850" align="center" border="1">
			<tr>
				<td><b>Nick / účet na fóru</b></td>
				<td><b>SteamID / Steam profil</b></td>
				<td><b>Sekce</b></td>
			</tr>
			<?php
				for ($i = 0; $i < $adminlist->get_count(); $i++) {
	
				$j = $i + 1;
				$nick = $adminlist->get_admin($i)->nick;
				$steamid = $adminlist->get_admin($i)->steamid;
				$permissions = $adminlist->get_admin($i)->perms;
				$steamurl = $adminlist->get_admin($i)->steamurl;
				$forumurl = $adminlist->get_admin($i)->forumprofileurl;
				$color = $adminlist->get_admin($i)->forumcolor;
	
				echo "<tr><td><a href='$forumurl'><b><font color='$color'>$nick</font></b></a></td><td><a href='$steamurl'><b><font color='#417394'>$steamid</font></b></a></td><td>$permissions</td></tr>";
	
}
			?>
		</table>
	</body>
</html>
<style>
	a, li, p, b, td { font: 13px Verdana, Arial, Tahoma, Calibri, Geneva, sans-serif; color: #333333; }
	b { font-weight: bold; }
	a { text-decoration: none; }
	a:hover { text-decoration: underline; }
	table, th, td { border: 3px solid gray; }
	table { border-collapse: collapse; }
	th, td { padding: 6.5px; }
</style>
