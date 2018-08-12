<?php

/*
 * Faucet in a BOX
 * https://faucetinabox.com/
 *
 * Copyright (c) 2014-2016 LiveHome Sp. z o. o.
 *
 * (ultimate) extensions and bugfixes by http://makejar.com/
 *
 * This file is part of Faucet in a BOX.
 *
 * Faucet in a BOX is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Faucet in a BOX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Faucet in a BOX.  If not, see <http://www.gnu.org/licenses/>.
 */

$version = '69';

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

if(stripos($_SERVER['REQUEST_URI'], '@') !== FALSE ||
   stripos(urldecode($_SERVER['REQUEST_URI']), '@') !== FALSE) {
    header("Location: ."); die('Please wait...');
}

session_start();
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', false);

$missing_configs = array();

$session_prefix = crc32(__FILE__);

$disable_curl = false;
$verify_peer = true;
$local_cafile = false;
require_once("config.php");
if(!isset($disable_admin_panel)) {
    $disable_admin_panel = false;
    $missing_configs[] = array(
        "name" => "disable_admin_panel",
        "default" => "false",
        "desc" => "Allows to disable Admin Panel for increased security"
    );
}

if(!isset($connection_options)) {
    $connection_options = array(
        'disable_curl' => $disable_curl,
        'local_cafile' => $local_cafile,
        'verify_peer' => $verify_peer,
        'force_ipv4' => false
    );
}
if(!isset($connection_options['verify_peer'])) {
    $connection_options['verify_peer'] = $verify_peer;
}

if (!isset($display_errors)) $display_errors = false;
ini_set('display_errors', $display_errors);
if($display_errors)
    error_reporting(-1);


if(array_key_exists('HTTP_REFERER', $_SERVER)) {
    $referer = $_SERVER['HTTP_REFERER'];
} else {
    $referer = "";
}

$host = parse_url($referer, PHP_URL_HOST);
// ultimate host:port bugfix
$host_http=$_SERVER['HTTP_HOST'];
$host_http=explode(':', $host_http);
$host_http=$host_http[0];
if($host_http != $host) {
//if($_SERVER['HTTP_HOST'] != $host) {
    if (
        array_key_exists("$session_prefix-address_input_name", $_SESSION) &&
        array_key_exists($_SESSION["$session_prefix-address_input_name"], $_POST)
    ) {
        $_POST[$_SESSION["$session_prefix-address_input_name"]] = "";
        trigger_error("REFERER CHECK FAILED, ASSUMING CSRF!");
    }
}

function getUniqueRequestID() {
    global $unique_request_id;

    if (!empty($unique_request_id)) {
        return $unique_request_id;
    } else {
        return '';
    }
}

function showExtensionsErrorPage($extensions_status) {
    global $version;
    require_once("script/admin_templates.php");
    
    $page = str_replace("<:: content ::>", $extensions_error_template, $master_template);
    
    foreach ($extensions_status as $ext => $status) {
        $page = str_replace("<:: {$ext}_color ::>", ($status ? "success" : "danger"), $page);
        $page = str_replace("<:: {$ext}_glyphicon ::>", ($status ? $extensions_ok_glyphicon : $extensions_error_glyphicon), $page);
    }
    
    die($page);
}

//Check required PHP extensions
$extensions_status = array(
    "curl" => extension_loaded("curl"),
    "gd" => extension_loaded("gd"),
    "pdo" => extension_loaded("PDO"),
    "pdo_mysql" => extension_loaded("pdo_mysql"),
    "soap" => extension_loaded("soap")
);
$all_loaded = array_reduce($extensions_status, function($all_loaded, $ext) {
    return $all_loaded && $ext;
}, true);
if (!$all_loaded) {
    showExtensionsErrorPage($extensions_status);
}


require_once('libs/services.php');

try {
    $sql = new PDO($dbdsn, $dbuser, $dbpass, array(PDO::ATTR_PERSISTENT => true,
                                                   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch(PDOException $e) {
    if ($display_errors) die("Can't connect to database. Check your config.php. Details: ".$e->getMessage());
    else die("Can't connect to database. Check your config.php or set \$display_errors = true; to see details.");
}

$db_updates = array(
    15 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('version', '15');"),
    17 => array("ALTER TABLE `Faucetinabox_Settings` CHANGE `value` `value` TEXT NOT NULL;", "INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('balance', 'N/A');"),
    33 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('ayah_publisher_key', ''), ('ayah_scoring_key', '');"),
    34 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('custom_admin_link_default', 'true')"),
    38 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('reverse_proxy', 'none')", "INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('default_captcha', 'recaptcha')"),
    41 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('captchme_public_key', ''), ('captchme_private_key', ''), ('captchme_authentication_key', ''), ('reklamper_enabled', '')"),
    46 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('last_balance_check', '0')"),
    54 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('funcaptcha_public_key', ''), ('funcaptcha_private_key', '')"),
    55 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('block_adblock', ''), ('button_timer', '0')"),
    56 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('ip_check_server', ''),('ip_ban_list', ''),('hostname_ban_list', ''),('address_ban_list', '')"),
    58 => array("DELETE FROM `Faucetinabox_Settings` WHERE `name` IN ('captchme_public_key', 'captchme_private_key', 'captchme_authentication_key', 'reklamper_enabled')"),
    63 => array("INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('safety_limits_end_time', '')"),
    64 => array(
        "INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('iframe_sameorigin_only', ''), ('asn_ban_list', ''), ('country_ban_list', ''), ('nastyhosts_enabled', '')",
        "UPDATE `Faucetinabox_Settings` new LEFT JOIN `Faucetinabox_Settings` old ON old.name = 'ip_check_server' SET new.value = IF(old.value = 'http://v1.nastyhosts.com/', 'on', '') WHERE new.name = 'nastyhosts_enabled'",
        "DELETE FROM `Faucetinabox_Settings` WHERE `name` = 'ip_check_server'"
    ),
    65 => array(
        "DELETE FROM `Faucetinabox_Settings` WHERE `name` IN ('ayah_publisher_key', 'ayah_scoring_key') ",
        "UPDATE `Faucetinabox_Settings` SET `value` = IF(`value` != 'none' OR `value` != 'none-auto', 'on', '') WHERE `name` = 'reverse_proxy' "
    ),
    66 => array(
        "ALTER TABLE `Faucetinabox_Settings` CHANGE `value` `value` LONGTEXT NOT NULL;",
        "INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('service', 'faucethub');",
        "CREATE TABLE IF NOT EXISTS `Faucetinabox_IP_Locks` ( `ip` VARCHAR(20) NOT NULL PRIMARY KEY, `locked_since` TIMESTAMP NOT NULL );",
        "CREATE TABLE IF NOT EXISTS `Faucetinabox_Address_Locks` ( `address` VARCHAR(60) NOT NULL PRIMARY KEY, `locked_since` TIMESTAMP NOT NULL );"
    ),
    67 => array(
        "ALTER TABLE `Faucetinabox_Refs` DROP COLUMN `balance`;",
        "INSERT IGNORE INTO `Faucetinabox_Settings` (`name`, `value`) VALUES ('ip_white_list', ''), ('update_last_check', '');"
    )
);

$default_data_query = <<<QUERY
create table if not exists Faucetinabox_Settings (
    `name` varchar(64) not null,
    `value` longtext not null,
    primary key(`name`)
);
create table if not exists Faucetinabox_IPs (
    `ip` varchar(45) not null,
    `last_used` timestamp not null,
    primary key(`ip`)
);
create table if not exists Faucetinabox_Addresses (
    `address` varchar(60) not null,
    `ref_id` int null,
    `last_used` timestamp not null,
    primary key(`address`)
);
create table if not exists Faucetinabox_Refs (
    `id` int auto_increment not null,
    `address` varchar(60) not null unique,
    primary key(`id`)
);
create table if not exists Faucetinabox_Pages (
    `id` int auto_increment not null,
    `url_name` varchar(50) not null unique,
    `name` varchar(255) not null,
    `html` text not null,
    primary key(`id`)
);
CREATE TABLE if not exists `Faucetinabox_NH_Log` (
  `Faucetinabox_NH_Log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Faucetinabox_NH_Log_time` int(11) NOT NULL DEFAULT '0',
  `Faucetinabox_NH_Log_IP` varchar(45) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_address` varchar(50) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_address_ref` varchar(50) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_suggestion` enum('allow','deny') NOT NULL DEFAULT 'deny',
  `Faucetinabox_NH_Log_reason` varchar(128) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_country_code` varchar(3) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_country` varchar(64) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_asn` int(11) NOT NULL DEFAULT '0',
  `Faucetinabox_NH_Log_asn_name` varchar(128) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_host` varchar(128) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_useragent` varchar(256) NOT NULL DEFAULT '',
  `Faucetinabox_NH_Log_session_id` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`Faucetinabox_NH_Log_id`),
  KEY `Faucetinabox_NH_Log_time` (`Faucetinabox_NH_Log_time`),
  KEY `Faucetinabox_NH_Log_session_id` (`Faucetinabox_NH_Log_session_id`)
);
create table if not exists `Faucetinabox_IP_Locks` (
    `ip` varchar(45) not null primary key,
    `locked_since` timestamp not null
);
create table if not exists `Faucetinabox_Address_Locks` (
    `address` varchar(60) not null primary key,
    `locked_since` timestamp not null
);

INSERT IGNORE INTO Faucetinabox_Settings (name, value) VALUES
('apikey', ''),
('timer', '180'),
('rewards', '90*100, 10*500'),
('referral', '15'),
('solvemedia_challenge_key', ''),
('solvemedia_verification_key', ''),
('solvemedia_auth_key', ''),
('recaptcha_private_key', ''),
('recaptcha_public_key', ''),
('funcaptcha_private_key', ''),
('funcaptcha_public_key', ''),
('name', 'Faucet in a Box <sup>ultimate</sup>'),
('short', 'Just another Faucet in a Box <sup>ultimate</sup>'),
('template', 'default'),
('custom_body_cl_default', ''),
('custom_box_bottom_cl_default', ''),
('custom_box_bottom_default', ''),
('custom_box_top_cl_default', ''),
('custom_box_top_default', ''),
('custom_box_left_cl_default', ''),
('custom_box_left_default', ''),
('custom_box_right_cl_default', ''),
('custom_box_right_default', ''),
('custom_css_default', '/* custom_css */\\n/* center everything! */\\n.row {\\n    text-align: center;\\n}\\n#recaptcha_widget_div, #recaptcha_area {\\n    margin: 0 auto;\\n}\\n/* do not center lists */\\nul, ol {\\n    text-align: left;\\n}'),
('custom_footer_cl_default', ''),
('custom_footer_default', ''),
('custom_main_box_cl_default', ''),
('custom_palette_default', ''),
('custom_admin_link_default', 'true'),
('version', '$version'),
('currency', 'BTC'),
('balance', 'N/A'),
('reverse_proxy', 'on'),
('last_balance_check', '0'),
('default_captcha', 'recaptcha'),
('ip_ban_list', ''),
('hostname_ban_list', ''),
('address_ban_list', ''),
('block_adblock', ''),
('button_timer', '0'),
('safety_limits_end_time', ''),
('iframe_sameorigin_only', ''),
('asn_ban_list', ''),
('country_ban_list', ''),
('nastyhosts_enabled', ''),
('service', 'faucethub'),
('ip_white_list', ''),
('update_last_check', '')
;
QUERY;

function randHash($length) {
    $alphabet = str_split('qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890');
    $hash = '';
    for($i = 0; $i < $length; $i++) {
        $hash .= $alphabet[array_rand($alphabet)];
    }
    return $hash;
}

function getNastyHostsServer() {
    return "http://v1.nastyhosts.com/";
}

function checkRevProxyIp($file) {
    require_once("libs/http-foundation/IpUtils.php");
    return IpUtils::checkIp($_SERVER['REMOTE_ADDR'], array_map(function($v) { return trim($v); }, file($file)));
}

function detectRevProxyProvider() {
    if(checkRevProxyIp("libs/ips/cloudflare.txt")) {
        return "CloudFlare";
    } elseif(checkRevProxyIp("libs/ips/incapsula.txt")) {
        return "Incapsula";
    }
    return "none";
}

function getIP() {
    global $sql;
    static $cache_ip;
    if ($cache_ip) return $cache_ip;
    $ip = null;
    $type = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'reverse_proxy'")->fetch();
    if ($type && $type[0] == "on") {
        if (checkRevProxyIp("libs/ips/cloudflare.txt")) {
            $ip = array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : null;
        } elseif (checkRevProxyIp("libs/ips/incapsula.txt")) {
            $ip = array_key_exists('HTTP_INCAP_CLIENT_IP', $_SERVER) ? $_SERVER['HTTP_INCAP_CLIENT_IP'] : null;
        }
    }
    if (empty($ip)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $cache_ip = $ip;
    return $ip;
}

function is_ssl(){
    if(isset($_SERVER['HTTPS'])){
        if('on' == strtolower($_SERVER['HTTPS']))
            return true;
        if('1' == $_SERVER['HTTPS'])
            return true;
        if(true == $_SERVER['HTTPS'])
            return true;
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])){
        return true;
    }
    if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
        return true;
    }
    return false;
}

function ipSubnetCheck ($ip, $network) {
    $network = explode("/", $network);
    $net = $network[0];

    if(count($network) > 1) {
        $mask = $network[1];
    } else {
        $mask = 32;
    }

    $net = ip2long ($net);
    $mask = ~((1 << (32 - $mask)) - 1);

    $ip_net = $ip & $mask;

    return ($ip_net == $net);
}

function nastyhosts_log($suggestion, $reason='', $response=array()) {
  global $sql, $session_prefix;
  if (empty($session_prefix)) {
    return;
  }
  if (empty($_SESSION["$session_prefix-address_input_name"])) {
    return;
  }
  if (empty($_POST[$_SESSION["$session_prefix-address_input_name"]])) {
    return;
  }
  // Delete the log that is older than a day - for better performance execute every ~20 requests
  if (mt_rand(0, 20)==5) {
    $sql->exec("DELETE FROM `Faucetinabox_NH_Log` WHERE Faucetinabox_NH_Log_time<".(time()-86400).";");
  }
  $q=$sql->prepare("INSERT INTO `Faucetinabox_NH_Log` SET
                      Faucetinabox_NH_Log_time=?,
                      Faucetinabox_NH_Log_IP=?,
                      Faucetinabox_NH_Log_address=?,
                      Faucetinabox_NH_Log_address_ref=?,
                      Faucetinabox_NH_Log_suggestion=?,
                      Faucetinabox_NH_Log_reason=?,
                      Faucetinabox_NH_Log_country_code=?,
                      Faucetinabox_NH_Log_country=?,
                      Faucetinabox_NH_Log_asn=?,
                      Faucetinabox_NH_Log_asn_name=?,
                      Faucetinabox_NH_Log_host=?,
                      Faucetinabox_NH_Log_useragent=?,
                      Faucetinabox_NH_Log_session_id=?
                    ;");
  $q->execute(array(
                      time(),
                      trim(getIP()),
                      trim(!empty($_POST[$_SESSION["$session_prefix-address_input_name"]])?$_POST[$_SESSION["$session_prefix-address_input_name"]]:''),
                      trim(!empty($_GET['r'])?$_GET['r']:''),
                      trim(!empty($suggestion)?$suggestion:''),
                      trim(!empty($reason)?$reason:''),
                      trim(!empty($response['country']['code'])?$response['country']['code']:''),
                      trim(!empty($response['country']['country'])?$response['country']['country']:''),
                      trim(!empty($response['asn']['asn'])?$response['asn']['asn']:'0'),
                      trim(!empty($response['asn']['name'])?substr($response['asn']['name'], 0, 128):''),
                      trim(!empty($response['hostnames'][0])?$response['hostnames'][0]:''),
                      trim(!empty($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:''),
                      session_id().'-'.getUniqueRequestID()
                      ));
}

/* wrong and too easy to abuse
function suspicious($server, $comment) {
    if($server) {
        @file_get_contents($server."report/1/".urlencode(getIP())."/".urlencode($comment));
    }
}
*/

// check if configured
try {
    $pass = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'password'")->fetch();
} catch(PDOException $e) {
    $pass = null;
}

if ($pass) {
    // check db updates
    $dbversion = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'version'")->fetch();
    if ($dbversion) {
        $dbversion = intval($dbversion[0]);
    } else {
        $dbversion = -1;
    }
    foreach ($db_updates as $v => $update) {
        if($v > $dbversion) {
            foreach($update as $query) {
                $sql->exec($query);
            }
        }
    }
    if ($dbversion < 17) {
        // dogecoin changed from satoshi to doge
        // better clear rewards...
        $c = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'currency'")->fetch();
        if($c[0] == 'DOGE')
            $sql->exec("UPDATE `Faucetinabox_Settings` SET `value` = '' WHERE name = 'rewards'");
    }
    if (intval($version) > intval($dbversion)) {
        $q = $sql->prepare("UPDATE `Faucetinabox_Settings` SET `value` = ? WHERE `name` = 'version'");
        $q->execute(array($version));
    }

    $iframe_sameorigin_only = $sql->query("SELECT `value` FROM  `Faucetinabox_Settings` WHERE `name` = 'iframe_sameorigin_only'")->fetch();
    if ($iframe_sameorigin_only && $iframe_sameorigin_only[0] == "on") {
        header("X-Frame-Options: SAMEORIGIN");
    }

    $security_settings = array();
    $nastyhosts_enabled = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'nastyhosts_enabled' ")->fetch();
    if ($nastyhosts_enabled && $nastyhosts_enabled[0]) {
        $security_settings["nastyhosts_enabled"] = true;
    } else {
        $security_settings["nastyhosts_enabled"] = false;
    }

    $q = $sql->query("SELECT `name`, `value` FROM `Faucetinabox_Settings` WHERE `name` in ('ip_ban_list', 'ip_white_list', 'hostname_ban_list', 'address_ban_list', 'asn_ban_list', 'country_ban_list')");
    while ($row = $q->fetch()) {
        if (stripos($row["name"], "_list") !== false) {
            $security_settings[$row["name"]] = array();
            if (preg_match_all("/[^,;\s]+/", $row["value"], $matches)) {
                foreach($matches[0] as $m) {
                    $security_settings[$row["name"]][] = $m;
                }
            }
        } else {
            $security_settings[$row["name"]] = $row["value"];
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        # ADMINLOG START
        require_once('libs/adminlog.php');
        $adminlog=new adminlog();
        # ADMINLOG END
/*
// ultimate - not used anymore
        $fake_address_input_used = false;
        if (!empty($_POST["address"])) {
            $fake_address_input_used = true;
        }
*/
    }
}
