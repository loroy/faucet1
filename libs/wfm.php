<?php

// WaterfallManager.net 1.01
// by WaterfallManager.net NeedIfFindIt https://bitcointalk.org/index.php?action=profile;u=391838
// working demo at http://WaterfallManager.net/demo/

class wfm {
    var $version=101;
    var $wfm_sid='wfm';
    var $ixvar='';
    var $url='https://waterfallmanager.net/api/v1/';
    var $wfm_settings=array();

    public $options = array(
        'disable_curl' => false,
        'force_ipv4' => false
    );

    public function __construct($connection_options=null) {
        global $sql;
        // check if wfm already installed
        $wfm_settings_array = $sql->query("SELECT `name`, `value` FROM `Faucetinabox_Settings` WHERE `name` LIKE 'wfm_%';")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($wfm_settings_array as $k=>$v) {
            $this->wfm_settings[$v['name']]=$v['value'];
        }
        //
        if (count($this->wfm_settings)==0) {
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_enabled', `value`='off';");
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_visit_check', `value`='off';");
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_js', `value`='';");
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_jsv', `value`='0';");
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_credits', `value`='0';");
            $sql->exec("INSERT INTO Faucetinabox_Settings SET `name`='wfm_apikey', `value`='';");
            $sql->exec("CREATE TABLE `Faucetinabox_WFM` (
                                     `Faucetinabox_WFM_id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `Faucetinabox_WFM_IP` varchar(45) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_uri` varchar(256) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_ref` varchar(1024) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_method` varchar(5) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_sid` varchar(256) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_time` int NOT NULL DEFAULT '0'
                      );");
            $sql->exec("ALTER TABLE `Faucetinabox_WFM` ADD KEY `Faucetinabox_WFM_IP` (`Faucetinabox_WFM_IP`);");
            $sql->exec("CREATE TABLE `Faucetinabox_WFM_Log` (
                                     `Faucetinabox_WFM_Log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `Faucetinabox_WFM_Log_time` int(11) NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_Log_rid` bigint(20) unsigned NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_Log_IP` varchar(45) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_address` varchar(50) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_address_ref` varchar(50) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_suggestion` enum('allow','deny') NOT NULL DEFAULT 'deny',
                                     `Faucetinabox_WFM_Log_reason` varchar(250) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_country_code` varchar(3) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_country` varchar(64) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_asn` int(11) NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_Log_asn_name` varchar(128) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_host` varchar(128) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_http_ref` varchar(256) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_useragent` varchar(256) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_info` varchar(128) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_Log_avgrwd` decimal(16,8) NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_Log_session_id` varchar(50) NOT NULL DEFAULT '',
                                     PRIMARY KEY (`Faucetinabox_WFM_Log_id`),
                                     KEY `Faucetinabox_WFM_Log_time` (`Faucetinabox_WFM_Log_time`),
                                     KEY `Faucetinabox_WFM_Log_suggestion` (`Faucetinabox_WFM_Log_suggestion`),
                                     KEY `Faucetinabox_WFM_Log_session_id` (`Faucetinabox_WFM_Log_session_id`)
                      );");
            $sql->exec("CREATE TABLE `Faucetinabox_WFM_vLog` (
                                     `Faucetinabox_WFM_vLog_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                     `Faucetinabox_WFM_vLog_time` int(11) NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_vLog_IP` varchar(45) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_suggestion` enum('allow','deny') NOT NULL DEFAULT 'deny',
                                     `Faucetinabox_WFM_vLog_reason` varchar(250) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_country_code` varchar(3) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_country` varchar(64) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_asn` int(11) NOT NULL DEFAULT '0',
                                     `Faucetinabox_WFM_vLog_asn_name` varchar(128) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_host` varchar(128) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_useragent` varchar(256) NOT NULL DEFAULT '',
                                     `Faucetinabox_WFM_vLog_session_id` varchar(50) NOT NULL DEFAULT '',
                                    PRIMARY KEY (`Faucetinabox_WFM_vLog_id`),
                                    KEY `Faucetinabox_WFM_vLog_time` (`Faucetinabox_WFM_vLog_time`)
                      );");
            $sql->exec("ALTER TABLE `Faucetinabox_WFM_Log` ADD INDEX(`Faucetinabox_WFM_Log_suggestion`);");
            // reload load settings
            $wfm_settings_array = $sql->query("SELECT `name`, `value` FROM `Faucetinabox_Settings` WHERE `name` LIKE 'wfm_%';")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($wfm_settings_array as $k=>$v) {
                $this->wfm_settings[$v['name']]=$v['value'];
            }
        }

        // return if not enabled
        if ($this->wfm_settings['wfm_enabled']!='on') {
            return true;
        }

        // delete older than 30 min - for better performance execute every ~10 request
        if (mt_rand(0, 10)==5) {
            $sql->exec("DELETE FROM `Faucetinabox_WFM` WHERE Faucetinabox_WFM_time<".(time()-1800).";");
        }
        // insert the current one
        $request_uri='';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $request_uri=$_SERVER['REQUEST_URI'];
        }
        $http_referer='';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $http_referer=$_SERVER['HTTP_REFERER'];
        }
        $request_method='';
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $request_method=$_SERVER['REQUEST_METHOD'];
        }
        // save to 30 min log
        if (basename($_SERVER['SCRIPT_FILENAME'])=='index.php') {
            $q=$sql->prepare("INSERT INTO `Faucetinabox_WFM` SET
                                                `Faucetinabox_WFM_IP`=?,
                                                `Faucetinabox_WFM_uri`=?,
                                                `Faucetinabox_WFM_ref`=?,
                                                `Faucetinabox_WFM_method`=?,
                                                `Faucetinabox_WFM_sid`=?,
                                                `Faucetinabox_WFM_time`=?
                                            ;");
            $q->execute(array(getIP(), trim($request_uri), trim($http_referer), trim($request_method), md5(session_id()), time()));
        }
        if (isset($_POST)&&(is_array($_POST))&&(count($_POST)>0)&&(!empty($_SESSION['wfm_sid']))) {
            $this->wfm_sid=$_SESSION['wfm_sid'];
        } else {
            $this->wfm_sid=randHash(rand(25,35));
            $_SESSION['wfm_sid']=$this->wfm_sid;
        }
        if($connection_options) {
            $this->options = array_merge($this->options, $connection_options);
        }
    }

    private function getHost() {
        if(array_key_exists('HTTP_HOST', $_SERVER)) {
            return $_SERVER['HTTP_HOST'];
        } else {
            return 'Unknown';
        }
    }

    public function __execPHP($url, $params = array()) {
        $params['m']='fopen';
        $opts = array(
                                    'http' => array(
                                        'method' => 'POST',
                                        'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n".'Referer: '.$this->getHost()."\r\n",
                                        'content' => http_build_query($params)
                                    ),
                                    'ssl' => array(
                                        'verify_peer' => false
                                    )
                                );
        $ctx = stream_context_create($opts);
        $fp = fopen($url, 'rb', null, $ctx);
        $response = stream_get_contents($fp);
        if($response && !$this->options['disable_curl']) {
            $this->curl_warning = true;
        }
        fclose($fp);
        return $response;
    }

    public function __execCURL($url, $params = array()) {
        $params['m']='curl';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_REFERER, $this->getHost());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($this->options['force_ipv4']) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        $response = curl_exec($ch);
        if(!$response) {
            $response = $this->__execPHP($url, $params);
        }
        curl_close($ch);

        return $response;
    }

    public function is_visit_check_valid() {
        global $sql;

        $stop_recording=false;
        // return if not enabled
        if ($this->wfm_settings['wfm_enabled']!='on') {
            return true;
        }
        // return if not enabled
        if ($this->wfm_settings['wfm_visit_check']!='on') {
            return true;
        }
        // prepare wfm_data
        $wfm_data=array();
        $wfm_data['ip']=getIP();
        $wfm_data['apikey']=$this->wfm_settings['wfm_apikey'];
        // send to Waterfall Manager
        if (empty($_SESSION['wfm_visit_check_response_'.trim(getIP())])) {
            if ($this->options['disable_curl']) {
                $response_string=$this->__execPHP($this->url.'ip/', $wfm_data);
            } else {
                $response_string=$this->__execCURL($this->url.'ip/', $wfm_data);
            }
            $response=json_decode($response_string, true);
            if (empty($response)) {
                file_put_contents('tmp/'.time().'-'.getIP().'.txt', $response_string);
            }
            if ((!empty($response['credits']))&&((int)$response['credits']>1)) {
                $_SESSION['wfm_visit_check_response_'.trim(getIP())]=$response;
            }
        } else {
            $response=$_SESSION['wfm_visit_check_response_'.trim(getIP())];
            $stop_recording=true;
        }

        // Delete the log that is older than a day - for better performance execute every ~20 requests
        if (mt_rand(0, 20)==5) {
            $sql->exec("DELETE FROM `Faucetinabox_WFM_vLog` WHERE Faucetinabox_WFM_vLog_time<".(time()-86400).";");
        }

        if ($response===false) {
            // Error connecting - allow
            return true;
        } else {
            if (!$stop_recording) {
                // got response
                // save credits
                if (isset($response['credits'])) {
                    $sql->exec("UPDATE Faucetinabox_Settings SET `value`='".(int)$response['credits']."' WHERE `name`='wfm_credits';");
                }
                // Log the request/response
                $q=$sql->prepare("INSERT INTO `Faucetinabox_WFM_vLog` SET
                                               Faucetinabox_WFM_vLog_time=?,
                                               Faucetinabox_WFM_vLog_IP=?,
                                               Faucetinabox_WFM_vLog_suggestion=?,
                                               Faucetinabox_WFM_vLog_reason=?,
                                               Faucetinabox_WFM_vLog_country_code=?,
                                               Faucetinabox_WFM_vLog_country=?,
                                               Faucetinabox_WFM_vLog_asn=?,
                                               Faucetinabox_WFM_vLog_asn_name=?,
                                               Faucetinabox_WFM_vLog_host=?,
                                               Faucetinabox_WFM_vLog_useragent=?,
                                               Faucetinabox_WFM_vLog_session_id=?
                                  ;");
                    $q->execute(array(
                                               time(),
                                               trim(getIP()),
                                               trim(!empty($response['suggestion'])?$response['suggestion']:''),
                                               trim(!empty($response['reason'])?$response['reason']:''),
                                               trim(!empty($response['country']['code'])?$response['country']['code']:''),
                                               trim(!empty($response['country']['country'])?$response['country']['country']:''),
                                               trim(!empty($response['asn']['asn'])?$response['asn']['asn']:'0'),
                                               trim(!empty($response['asn']['name'])?substr($response['asn']['name'], 0, 128):''),
                                               trim(!empty($response['hostnames'][0])?$response['hostnames'][0]:''),
                                               trim(!empty($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:''),
                                               session_id().'-'.getUniqueRequestID()
                                 ));
            }
            switch ($response['suggestion']) {
                case 'allow':
                    // good IP, No credits, Bad API Key
                    return true;
                break;
                case 'deny':
                    // BAD IP
                ?>
<html>
    <head>
        <title></title>
    </head>
    <body>
<?php
                    echo $response['reason'];
                    ?>
    </body>
</html>
<?php
                    exit;
                break;
                default:
                    // Error connecting - allow
                    return true;
                break;
            }
        }
    }

    public function is_valid(&$return_exact_error) {
        global $antibotlinks, $data, $sql;

        // return if not enabled
        if ($this->wfm_settings['wfm_enabled']!='on') {
            return true;
        }

        $server_data=$_SERVER;

        unset($server_data['PATH']);
        unset($server_data['SystemRoot']);
        unset($server_data['COMSPEC']);
        unset($server_data['PATHEXT']);
        unset($server_data['WINDIR']);
        unset($server_data['SERVER_SIGNATURE']);
        unset($server_data['DOCUMENT_ROOT']);
        unset($server_data['CONTEXT_DOCUMENT_ROOT']);
        unset($server_data['SERVER_ADMIN']);
        unset($server_data['SCRIPT_FILENAME']);
        unset($server_data['SERVER_ADDR']);
        unset($server_data['SERVER_PORT']);
        unset($server_data['SCRIPT_NAME']);
        unset($server_data['PHP_SELF']);
        unset($server_data['HTTP_CONNECTION']);
        unset($server_data['HTTP_ACCEPT_ENCODING']);

        // prepare wfm_data
        $wfm_data=array();
        if (!empty($_POST[$this->wfm_sid])) {
            $wfm_data['j']=$_POST[$this->wfm_sid];
        } else {
            $wfm_data['j']='';
        }
        $wfm_data['s']=json_encode($server_data);
        $wfm_data['p']=getIP();
        $wfm_data['a']=$_POST['address'];
        if (!empty($_POST['adcopy_response'])) {
            $wfm_data['adcopy_response']=$_POST['adcopy_response'];
        }
        if (!empty($_GET['r'])) {
            $wfm_data['r']=$_GET['r'];
        }
        // send WFM version
        $wfm_data['version']=$this->version;
        $wfm_data['wfm_jsv']=$this->wfm_settings['wfm_jsv'];
        // send WFM api key
        $wfm_data['apikey']=$this->wfm_settings['wfm_apikey'];
        // send reverse proxy setting
        $reverse_proxy = $sql->query("SELECT `value` FROM `Faucetinabox_Settings` WHERE `name` = 'reverse_proxy'")->fetch();
        $wfm_data['rp']=$reverse_proxy[0];
        // HTTP REFERER
        $http_referer='';
        // send browse log
        $browse_log=array();
        $q=$sql->prepare("SELECT `Faucetinabox_WFM_uri` as uri, `Faucetinabox_WFM_ref` as ref, `Faucetinabox_WFM_method` as method,`Faucetinabox_WFM_sid` as sid,`Faucetinabox_WFM_time` as time FROM `Faucetinabox_WFM` WHERE `Faucetinabox_WFM_IP`=? AND Faucetinabox_WFM_time>".(time()-1800)." ORDER BY Faucetinabox_WFM_id ASC;");
        $q->execute(array(trim(getIP())));
        while ($wfm_installed_row=$q->fetch(PDO::FETCH_ASSOC)) {
            if ((empty($http_referer))&&(!empty($wfm_installed_row['ref']))&&(strpos($wfm_installed_row['ref'], $_SERVER['HTTP_HOST'])===false)) {
                $http_referer=$wfm_installed_row['ref'];
                if (strlen($http_referer)>250) {
                    $http_referer=substr($http_referer, 0, 250);
                }
            }
            $browse_log[]=$wfm_installed_row;
        }
        $wfm_data['bl']=json_encode($browse_log);
        // antibotlinks universe used (if any)
        if ((isset($antibotlinks->version))&&(isset($_SESSION['antibotlinks']['universe']))) {
            $wfm_data['antibotlinks']=json_encode(array('version'=>$antibotlinks->version, 'universe'=>$_SESSION['antibotlinks']['universe']));
        }
        // get the timer
        $wfm_settings_query=$sql->query("SELECT name, value FROM Faucetinabox_Settings WHERE name='timer' LIMIT 1;");
        if ($wfm_settings_row=$wfm_settings_query->fetch(PDO::FETCH_ASSOC)) {
            $timer=$wfm_settings_row['value'];
        }
        // get the min/average reward
        $reward_min=0;
        $reward_average=0;
        $settings_rewards='100*100';
        $wfm_settings_query=$sql->query("SELECT name, value FROM Faucetinabox_Settings WHERE name='rewards' LIMIT 1;");
        if ($wfm_settings_row=$wfm_settings_query->fetch(PDO::FETCH_ASSOC)) {
            $settings_rewards=$wfm_settings_row['value'];
        }
        $rewards = explode(',', $settings_rewards);
        $total_weight = 0;
        $nrewards = array();
        foreach($rewards as $reward) {
            $reward = explode("*", trim($reward));
            if(count($reward) < 2) {
                $reward[1] = $reward[0];
                $reward[0] = 1;
            }
            $total_weight += intval($reward[0]);
            $nrewards[] = $reward;
        }
        $rewards = $nrewards;
        if(count($rewards) > 0) {
            foreach($rewards as $r) {
                $chance_per = 100 * $r[0]/$total_weight;
                if($chance_per < 0.1) {
                    $chance_per = '0.1';
                } else {
                    $chance_per = round(floor($chance_per*10)/10, 1);
                }
                if (strpos($r[1], '-')) {
                    // range 1-2
                    $average_array=explode('-', $r[1]);
                    if ($average_array[1]>$average_array[0]) {
                        if (($average_array[0]<$reward_min)||($reward_min==0)) {
                            $reward_min=$average_array[0];
                        }
                        $avgerage=($average_array[1]-$average_array[0])/2+$average_array[0];
                    } else {
                        $avgerage=($average_array[0]-$average_array[1])/2+$average_array[1];
                        if (($average_array[1]<$reward_min)||($reward_min==0)) {
                            $reward_min=$average_array[0];
                        }
                    }
                } else {
                    // final number
                    $avgerage=$r[1];
                    if (($r[1]<$reward_min)||($reward_min==0)) {
                        $reward_min=$r[1];
                    }
                }
                $reward_average+=$avgerage*$chance_per;
            }
        }
        $reward_average=round($reward_average/100);
        $wfm_data['timer']=$timer;
        $wfm_data['min']=$reward_min;
        $wfm_data['avg']=$reward_average;
        $wfm_data['currency']=$data['currency'];
        // send to Waterfall Manager
        if ($this->options['disable_curl']) {
            $response=$this->__execPHP($this->url, $wfm_data);
        } else {
            $response=$this->__execCURL($this->url, $wfm_data);
        }
        $response=json_decode($response, true);
        if ($response===false) {
            // not able to connect to WFM
            $return_exact_error='Unable to connect to WFM! Please try again in a minute!';
            return false;
        } else {
            // got response
            // save credits
            if (isset($response['credits'])) {
                $sql->exec("UPDATE Faucetinabox_Settings SET `value`='".(int)$response['credits']."' WHERE `name`='wfm_credits';");
            } else {
                $response['credits']=0;
            }
            // check if uppdate needed
            if ((!empty($response['update']))&&(!empty($response['update_version']))) {
                $q=$sql->prepare("UPDATE Faucetinabox_Settings SET `value`=? WHERE `name`='wfm_js';");
                $q->execute(array(trim($response['update'])));
                $sql->exec("UPDATE Faucetinabox_Settings SET `value`='".(int)$response['update_version']."' WHERE `name`='wfm_jsv';");
            }

            if (empty($response['rid'])) {
                $response['rid']=0;
            }

            // Delete the log that is older than a day - for better performance execute every ~20 requests
            if (mt_rand(0, 20)==5) {
                $sql->exec("DELETE FROM `Faucetinabox_WFM_Log` WHERE Faucetinabox_WFM_Log_time<".(time()-86400).";");
            }

            // Log the request/response
            $q=$sql->prepare("INSERT INTO `Faucetinabox_WFM_Log` SET
                                                        Faucetinabox_WFM_Log_time=?,
                                                        Faucetinabox_WFM_Log_rid=?,
                                                        Faucetinabox_WFM_Log_IP=?,
                                                        Faucetinabox_WFM_Log_address=?,
                                                        Faucetinabox_WFM_Log_address_ref=?,
                                                        Faucetinabox_WFM_Log_suggestion=?,
                                                        Faucetinabox_WFM_Log_reason=?,
                                                        Faucetinabox_WFM_Log_country_code=?,
                                                        Faucetinabox_WFM_Log_country=?,
                                                        Faucetinabox_WFM_Log_asn=?,
                                                        Faucetinabox_WFM_Log_asn_name=?,
                                                        Faucetinabox_WFM_Log_host=?,
                                                        Faucetinabox_WFM_Log_http_ref=?,
                                                        Faucetinabox_WFM_Log_useragent=?,
                                                        Faucetinabox_WFM_Log_info=?,
                                                        Faucetinabox_WFM_Log_avgrwd=?,
                                                        Faucetinabox_WFM_Log_session_id=?
                                                    ;");
            $q->execute(array(
                                                time(),
                                                (int)$response['rid'],
                                                trim(getIP()),
                                                trim($_POST['address']),
                                                trim(!empty($_GET['r'])?$_GET['r']:''),
                                                trim(!empty($response['suggestion'])?$response['suggestion']:''),
                                                trim(!empty($response['reason'])?$response['reason']:''),
                                                trim(!empty($response['country']['code'])?$response['country']['code']:''),
                                                trim(!empty($response['country']['country'])?$response['country']['country']:''),
                                                trim(!empty($response['asn']['asn'])?(int)$response['asn']['asn']:'0'),
                                                trim(!empty($response['asn']['name'])?substr($response['asn']['name'], 0, 128):''),
                                                trim(!empty($response['hostnames'][0])?$response['hostnames'][0]:''),
                                                $http_referer,
                                                trim(!empty($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:''),
                                                trim(!empty($response['info'])?$response['info']:''),
                                                $reward_average,
                                                session_id().'-'.getUniqueRequestID()
                                                ));

            if (empty($response['suggestion'])) {
                $response['suggestion']='';
            }
            // check if eligable
            switch ($response['suggestion']) {
                case 'allow':
                    return true;
                break;
                case 'deny':
                    $return_exact_error=$response['reason'];
                    return false;
                break;
                default:
                    $return_exact_error='We experienced a temporary error. Please try again in a minute!';
                    return false;
                break;
            }
        }
        // nobody will ever get here
        return true;
    }

    public function get_js() {
        global $data;

        // return if not enabled
        if ($this->wfm_settings['wfm_enabled']!='on') {
            return true;
        }
        // send the JS
        if ($data['page']=='eligible') {
            ?>
<script type="text/javascript">
var wfm_sid='<?php echo $this->wfm_sid; ?>';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 <?php echo $this->wfm_settings['wfm_js']; ?>

</script>
<?php
        }
    }

    public function admin_config_top() {
        global $sql, $currency, $session_prefix;
        if(array_key_exists("$session_prefix-logged_in", $_SESSION)) {
            // save_settings
            if (isset($_POST['save_settings'])) {
                if (!isset($_POST['wfm_enabled'])) {
                    $_POST['wfm_enabled']='';
                }
                if (!isset($_POST['wfm_visit_check'])) {
                    $_POST['wfm_visit_check']='';
                }
            }
            // wfm ajax call
            if (!empty($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'ajax_wfm':
                        // send to Waterfall Manager
                        $wfm_data=array();
                        // send WFM api key
                        $wfm_data['apikey']=$this->wfm_settings['wfm_apikey'];
                        $wfm_data['rid']=(int)$_POST['rid'];
                        $wfm_data['msg']=$_POST['msg'];
                        if ($this->options['disable_curl']) {
                            $response=$this->__execPHP($this->url.'report/', $wfm_data);
                        } else {
                            $response=$this->__execCURL($this->url.'report/', $wfm_data);
                        }
                        $response=json_decode($response, true);
                        if ($response!==false) {
                            echo $response['reason'];
                        }
                        exit;
                    break;
                    case 'ajax_wfm_get_claim_log':
                        if (empty($_POST['last_id'])) {
                            $last_id=0;
                        } else {
                            $last_id=(int)$_POST['last_id'];
                        }
                        //
                        $wfm_log_response=array();
                        $wfm_log_response['log']=array();
                        $maxid=$last_id;
                        // count all requests
                        $count_all_requests=0;
                        $wfm_log_query=$sql->query("SELECT count(Faucetinabox_WFM_Log_id) count_all_requests FROM Faucetinabox_WFM_Log WHERE Faucetinabox_WFM_Log_time>".(time()-86400).";");
                        if ($wfm_log_row=$wfm_log_query->fetch(PDO::FETCH_ASSOC)) {
                            $wfm_log_response['count_all_requests']=$wfm_log_row['count_all_requests'];
                        }
                        // count allowed requests
                        $wfm_log_response['count_denied_requests']=0;
                        $wfm_log_response['sum_denied_rwd']=0;
                        $wfm_log_response['allowed_requests']=0;
                        $wfm_log_response['allowed_requests_percent']=0;
                        $wfm_log_response['denied_requests']=0;
                        $wfm_log_response['denied_requests_percent']=0;
                        $wfm_log_query=$sql->query("SELECT count(Faucetinabox_WFM_Log_id) count_denied_requests, sum(Faucetinabox_WFM_Log_avgrwd) as sum_denied_rwd FROM Faucetinabox_WFM_Log WHERE Faucetinabox_WFM_Log_suggestion!='allow' AND Faucetinabox_WFM_Log_time>".(time()-86400).";");
                        if ($wfm_log_row=$wfm_log_query->fetch(PDO::FETCH_ASSOC)) {
                            $wfm_log_response['count_denied_requests']=$wfm_log_row['count_denied_requests'];
                            $wfm_log_response['sum_denied_rwd']=$wfm_log_row['sum_denied_rwd'];
                            $wfm_log_response['currency_name']='satoshi';
                            if($currency!='DOGE') {
                                $wfm_log_response['sum_denied_rwd']=(int)$wfm_log_response['sum_denied_rwd'];
                            } else {
                                $wfm_log_response['currency_name']='DOGE';
                            }
                        }
                        if ($wfm_log_response['count_all_requests']*($wfm_log_response['count_all_requests']-$wfm_log_response['count_denied_requests'])!=0) {
                            $wfm_log_response['allowed_requests']=$wfm_log_response['count_all_requests']-$wfm_log_response['count_denied_requests'];
                            $wfm_log_response['allowed_requests_percent']=round(100/$wfm_log_response['count_all_requests']*($wfm_log_response['count_all_requests']-$wfm_log_response['count_denied_requests']), 2);
                        }
                        if ($wfm_log_response['count_all_requests']*$wfm_log_response['count_denied_requests']!=0) {
                            $wfm_log_response['denied_requests']=$wfm_log_response['count_denied_requests'];
                            $wfm_log_response['denied_requests_percent']=round(100/$wfm_log_response['count_all_requests']*$wfm_log_response['count_denied_requests'], 2);
                        }

                        $wfm_log_query=$sql->query("SELECT
                                                      Faucetinabox_WFM_Log_id as WFM_Log_id,
                                                      Faucetinabox_WFM_Log_rid as WFM_Log_rid,
                                                      Faucetinabox_WFM_Log_time as WFM_Log_time,
                                                      Faucetinabox_WFM_Log_IP as WFM_Log_IP,
                                                      Faucetinabox_WFM_Log_address as WFM_Log_address,
                                                      Faucetinabox_WFM_Log_address_ref as WFM_Log_address_ref,
                                                      Faucetinabox_WFM_Log_suggestion as WFM_Log_suggestion,
                                                      Faucetinabox_WFM_Log_reason as WFM_Log_reason,
                                                      Faucetinabox_WFM_Log_country_code as WFM_Log_country_code,
                                                      Faucetinabox_WFM_Log_country as WFM_Log_country,
                                                      Faucetinabox_WFM_Log_asn as WFM_Log_asn,
                                                      Faucetinabox_WFM_Log_asn_name as WFM_Log_asn_name,
                                                      Faucetinabox_WFM_Log_host as WFM_Log_host,
                                                      Faucetinabox_WFM_Log_http_ref as WFM_Log_http_ref,
                                                      Faucetinabox_WFM_Log_useragent as WFM_Log_useragent,
                                                      Faucetinabox_WFM_Log_info as WFM_Log_info
                                                    FROM
                                                      Faucetinabox_WFM_Log
                                                    WHERE
                                                      Faucetinabox_WFM_Log_id>".(int)$last_id."
                                                    AND
                                                      Faucetinabox_WFM_Log_time>".(time()-86400)."
                                                    ORDER BY
                                                      Faucetinabox_WFM_Log_id
                                                    ASC;");
                        while ($wfm_log_row=$wfm_log_query->fetch(PDO::FETCH_ASSOC)) {
                            if ($wfm_log_row['WFM_Log_id']>$maxid) {
                                $maxid=$wfm_log_row['WFM_Log_id'];
                            }
                            unset($wfm_log_row['WFM_Log_id']);
                            $wfm_log_row['WFM_Log_time']='<b>'.date('Y.m.d', $wfm_log_row['WFM_Log_time']).'</b><br />'.date('H:i:s', $wfm_log_row['WFM_Log_time']);
                            $wfm_log_row['WFM_Log_address']=htmlspecialchars($wfm_log_row['WFM_Log_address']);
                            $wfm_log_row['WFM_Log_address_ref']=htmlspecialchars($wfm_log_row['WFM_Log_address_ref']);
                            $wfm_log_row['WFM_Log_suggestion']=htmlspecialchars($wfm_log_row['WFM_Log_suggestion']);
                            $wfm_log_row['WFM_Log_country']=htmlspecialchars($wfm_log_row['WFM_Log_country']);
                            $wfm_log_row['WFM_Log_asn_name']=htmlspecialchars($wfm_log_row['WFM_Log_asn_name']);
                            $wfm_log_row['WFM_Log_host']=htmlspecialchars($wfm_log_row['WFM_Log_host']);
                            $wfm_log_row['WFM_Log_useragent']=htmlspecialchars($wfm_log_row['WFM_Log_useragent']);
                            $wfm_log_row['WFM_Log_info']=htmlspecialchars($wfm_log_row['WFM_Log_info']);
                            $wfm_log_response['log'][]=$wfm_log_row;
                        }
                        $wfm_log_response['last_id']=$maxid;
                        echo json_encode($wfm_log_response);
                        exit;
                    break;
                    case 'ajax_wfm_get_visit_log':
                        if (empty($_POST['last_id'])) {
                            $last_id=0;
                        } else {
                            $last_id=(int)$_POST['last_id'];
                        }
                        //
                        $wfm_log_response=array();
                        $wfm_log_response['log']=array();
                        $maxid=$last_id;
                        $wfm_log_query=$sql->query("SELECT
                                                      Faucetinabox_WFM_vLog_id as WFM_vLog_id,
                                                      Faucetinabox_WFM_vLog_time as WFM_vLog_time,
                                                      Faucetinabox_WFM_vLog_IP as WFM_vLog_IP,
                                                      Faucetinabox_WFM_vLog_suggestion as WFM_vLog_suggestion,
                                                      Faucetinabox_WFM_vLog_reason as WFM_vLog_reason,
                                                      Faucetinabox_WFM_vLog_country_code as WFM_vLog_country_code,
                                                      Faucetinabox_WFM_vLog_country as WFM_vLog_country,
                                                      Faucetinabox_WFM_vLog_asn as WFM_vLog_asn,
                                                      Faucetinabox_WFM_vLog_asn_name as WFM_vLog_asn_name,
                                                      Faucetinabox_WFM_vLog_host as WFM_vLog_host,
                                                      Faucetinabox_WFM_vLog_useragent as WFM_vLog_useragent
                                                    FROM
                                                      Faucetinabox_WFM_vLog
                                                    WHERE
                                                      Faucetinabox_WFM_vLog_id>".(int)$last_id."
                                                    AND
                                                      Faucetinabox_WFM_vLog_time>".(time()-86400)."
                                                    ORDER BY
                                                      Faucetinabox_WFM_vLog_id
                                                    DESC
                                                    LIMIT 2500
                                                    ;");
                        while ($wfm_log_row=$wfm_log_query->fetch(PDO::FETCH_ASSOC)) {
                            if ($wfm_log_row['WFM_vLog_id']>$maxid) {
                                $maxid=$wfm_log_row['WFM_vLog_id'];
                            }
                            unset($wfm_log_row['WFM_vLog_id']);
                            $wfm_log_row['WFM_vLog_time']='<b>'.date('Y.m.d', $wfm_log_row['WFM_vLog_time']).'</b><br />'.date('H:i:s', $wfm_log_row['WFM_vLog_time']);
                            $wfm_log_row['WFM_vLog_suggestion']=htmlspecialchars($wfm_log_row['WFM_vLog_suggestion']);
                            $wfm_log_row['WFM_vLog_country']=htmlspecialchars($wfm_log_row['WFM_vLog_country']);
                            $wfm_log_row['WFM_vLog_asn_name']=htmlspecialchars($wfm_log_row['WFM_vLog_asn_name']);
                            $wfm_log_row['WFM_vLog_host']=htmlspecialchars($wfm_log_row['WFM_vLog_host']);
                            $wfm_log_row['WFM_vLog_useragent']=htmlspecialchars($wfm_log_row['WFM_vLog_useragent']);
                            $wfm_log_response['log'][]=$wfm_log_row;
                        }
                        // reverse the array
                        $wfm_log_response['log']=array_reverse($wfm_log_response['log']);

                        $wfm_log_response['last_id']=$maxid;
                        echo json_encode($wfm_log_response);
                        exit;
                    break;
                }
            }
        }
    }

    public function admin_config() {
        global $sql, $page, $currency, $session_prefix;

        foreach ($this->wfm_settings as $wfm_settings_k=>$wfm_settings_v) {
            if(in_array($wfm_settings_k, ['wfm_enabled'])) {
                $wfm_settings_v = $wfm_settings_v == 'on' ? 'checked' : '';
            }
            if(in_array($wfm_settings_k, ['wfm_visit_check'])) {
                $wfm_settings_v = $wfm_settings_v == 'on' ? 'checked' : '';
            }
            $page = str_replace('<:: '.$wfm_settings_k.' ::>', $wfm_settings_v, $page);
        }

        $wfm_log='<div id="wfm_claim_log" style="max-height:500px;overflow-y:scroll;">...</div>';
        $wfm_log.='
<script tyle="text/javascript">
var wfm_claim_last_id=0;
var wfm_claim_data=[];
var wfm_claim_active=false;

function wfm_claim_loop() {
    $.post(\''.basename($_SERVER['PHP_SELF']).'\', {action:\'ajax_wfm_get_claim_log\', last_id:wfm_claim_last_id, csrftoken:\''.$_SESSION["$session_prefix-csrftoken"].'\'})
        .done(function(jsonData) {
            if (jsonData!=\'\') {
                var data=JSON.parse(jsonData);
                wfm_claim_last_id=data[\'last_id\'];
                for (var z=0;z<data[\'log\'].length;z++) {
                    wfm_claim_data[wfm_claim_data.length]=data[\'log\'][z];
                }
                var data_string=\'\';

                data_string+=\'<table style="border:1px solid #AAAAAA;font-size:10px;width:100%;">\';
                data_string+=\'<tr style="background-color:#EEEEEE;font-weight:bold;">\';
                data_string+=\'<td><b>Allowed Requests:</b> \'+data[\'allowed_requests\']+\' (\'+data[\'allowed_requests_percent\']+\'%)</td>\';
                data_string+=\'<td><b>Denied Requests:</b> \'+data[\'denied_requests\']+\' (\'+data[\'denied_requests_percent\']+\'%)</td>\';
                data_string+=\'<td><b>Total Requests (used credits):</b> \'+data[\'count_all_requests\']+\'</td>\';
                data_string+=\'<td><b>Saved \'+data[\'currency_name\']+\' (estimated):</b> \'+data[\'sum_denied_rwd\']+\'</td>\';
                data_string+=\'</tr>\';
                data_string+=\'</table><br />\';

                data_string+=\'<table style="border:1px solid #AAAAAA;font-size:10px;width:100%;">\';
                data_string+=\'<tr style="background-color:#EEEEEE;font-weight:bold;">\';
                data_string+=\'<td>Date<br />Time</td>\';
                data_string+=\'<td></td>\';
                data_string+=\'<td>IP<br />Host</td>\';
                data_string+=\'<td>Address<br />REF Address</td>\';
                data_string+=\'<td>Suggestion<br />Message</td>\';
                data_string+=\'<td>Country Code<br />Country</td>\';
                data_string+=\'<td>ASN<br />ASN Name</td>\';
                data_string+=\'</tr>\';
                for (var z=wfm_claim_data.length-1;z>=0;z--) {
                    if (wfm_claim_data[z][\'WFM_Log_http_ref\']==\'\') {
                        wfm_claim_data[z][\'WFM_Log_http_ref\']=\'direct or coming from https\';
                    }
                    var wfm_row_css=\'\';
                    if (wfm_claim_data[z][\'WFM_Log_suggestion\']==\'allow\') {
                        wfm_row_css=\'background-color:#DDFFDD;\';
                    } else {
                        wfm_row_css=\'background-color:#FFDDDD;\';
                    }
                    data_string+=\'<tr style="border-top:1px solid #AAAAAA;\'+wfm_row_css+\'">\';
                    data_string+=\'<td><b>\'+wfm_claim_data[z][\'WFM_Log_time\']+\'</td>\';
                    data_string+=\'<td><a href="#" title="Report to WaterfallManager.net" style="color:#222280;" class="c_report" rel="\'+wfm_claim_data[z][\'WFM_Log_rid\']+\'"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4AkGEiImmPY4TAAAAB1pVFh0Q29tbWVudAAAAAAAQ3JlYXRlZCB3aXRoIEdJTVBkLmUHAAADX0lEQVQ4y22TT0yTdxzGn9+P9u37vv2XtqOhshSabCyOQ9fiYdOLGa06vGC2dRgMyQ6LWRrtgUg8eFJPElEYJ5Mly+LY7GFBhRVhTl1TU0PmH2oX8M8a0WrpHA5a+vft77vTkhH3nJ/Pc/o8DK+nJRyJDHV4vSG9wdDKOAcnyqbm56PjZ84MA3jxGuH3+xkA9B84MPbT3BzdOH6cLnV20g2bjRJWK/3i9dKtkydpZnaWPtu/fxQAurq62KaRLw8dSsSjUZqSZXHf5aL89u206vfTX8EgLbe00OMtW+iqwSB+i0YpMjSU+JdrAoDefftGP9+9+5P1vj4qtrWxxYMH8dBmw5KiILO6ikKpBLPZjHank/0xPk4fRCLusqo6Fu7diwGA65uJ72nSYhG3m5vpx95eEkRUJ6IqERWJKLu+TnPXr9O3PT205PfTtCyLmdlZAtDC+wcGjjT/noahUmFWWUbB4UAdQHVjA1q5DBmArakJO3fswMfT05j3emFvNJgpHscX4fAQd3d0hCpXrsCkqqhoGtonJrCUSoEDeLW8jMudnXiaSKCmaWDlMj48exZrjQYKFy/Cs3VriDeAVt2zZ1B1OkhCgDkcsLnd0EkSHsdieOvRI/w8OAjS6QAApefPYeIc7MkT6PX6Vl6v1cAZg0kIOIXAmscDm9WKWr2OdwcGQIuL6EsmITEGoSjIHD4Mk90OAGiQAC8WClmdywWrpsFSKqEQCMAAQDQaUBQF7Q4HDJzjQSyGBZ8Pb965g3K9DvJ4UFxbz/IXmUx0Y9s2GCsVPK3V8E5/P6Bp+DudRvzcORhkGZBlvDp2DO+trEDPOYrlMngwiIephSifmpw8dcvlgqaqJKkqiiMjyNy9i5VkEm+MjqIqSVAANJ0+jdrLlxCaBpIkWmxrw4Xz3w0DALq7u8fGjh6lhk4nyGKhAmOUMxopryh0P52m1LVrlAyHKW8y0QPOxYXhYQoEg6ObVN61d29iZHCQCg6HaKgqVa1WqtrttCxJVAOI9Hoqms3ihxMnqDcU2qyy1+tlN+Pxr4uc22/v2fN+2e2GUQjIpRIUWcafHW/jZnAXzn/Uwy79Gv8qNnX5U5/Px3K5HNj/3Nm1MxA4YnQ6QwaTqZVzjlqplN3I56NXZ2ZOAcj9t/wPHSqKbPSxuiQAAAAASUVORK5CYII=" /></a></td>\';
                    data_string+=\'<td title="\'+wfm_claim_data[z][\'WFM_Log_useragent\']+\'"><b><a href="http://www.tcpiputils.com/browse/ip-address/\'+wfm_claim_data[z][\'WFM_Log_IP\']+\'" target="_blank" style="color:#5555AA;" title="View details about \'+wfm_claim_data[z][\'WFM_Log_IP\']+\' at tcpiputils.com">\'+wfm_claim_data[z][\'WFM_Log_IP\']+\'</a></b><br />\'+wfm_claim_data[z][\'WFM_Log_host\']+\'<br />\'+wfm_claim_data[z][\'WFM_Log_http_ref\']+\'</td>\';

                    data_string+=\'<td>\';
                    data_string+=\'<a href="https://faucethub.io/balance/\'+wfm_claim_data[z][\'WFM_Log_address\']+\'" target="_blank" style="color:#222280;" title="View at FaucetHub.io">FH</a>&nbsp;\';
                    data_string+=\'<a href="https://faucetsystem.com/check/\'+wfm_claim_data[z][\'WFM_Log_address\']+\'/" target="_blank" style="color:#222280;" title="View at FaucetSystem.com">FS</a>&nbsp;\';
                    data_string+=wfm_claim_data[z][\'WFM_Log_address\'];
                    if (wfm_claim_data[z][\'WFM_Log_address_ref\']!=\'\') {
                        data_string+=\'<br /><a href="https://faucethub.io/balance/\'+wfm_claim_data[z][\'WFM_Log_address_ref\']+\'" target="_blank" style="color:#5555AA;" title="View at FaucetHub.io">FH</a>&nbsp;\';
                        data_string+=\'<a href="https://faucetsystem.com/check/\'+wfm_claim_data[z][\'WFM_Log_address_ref\']+\'/" target="_blank" style="color:#5555AA;" title="View at FaucetSystem.com">FS</a>&nbsp;\';
                        data_string+=wfm_claim_data[z][\'WFM_Log_address_ref\'];
                    }
                    if (wfm_claim_data[z][\'WFM_Log_info\']!=\'\') {
                        data_string+=\' (\'+wfm_claim_data[z][\'WFM_Log_info\']+\')\';
                    }
                    data_string+=\'</td>\';

                    data_string+=\'<td><b>\'+wfm_claim_data[z][\'WFM_Log_suggestion\']+\'</b><br />\'+wfm_claim_data[z][\'WFM_Log_reason\']+\'</td>\';
                    data_string+=\'<td><b>\'+wfm_claim_data[z][\'WFM_Log_country_code\']+\'</b><br />\'+wfm_claim_data[z][\'WFM_Log_country\']+\'</td>\';
                    if (wfm_claim_data[z][\'WFM_Log_asn\']>0) {
                        data_string+=\'<td><b>\'+wfm_claim_data[z][\'WFM_Log_asn\']+\'</b><br />\'+wfm_claim_data[z][\'WFM_Log_asn_name\']+\'</td>\';
                    } else {
                        data_string+=\'<td></td>\';
                    }
                    data_string+=\'</tr>\';
                }
                data_string+=\'</table>\';
                $(\'#wfm_claim_log\').html(data_string);
                $(\'.c_report\').click(function(){
                    var report_msg=prompt(\'Please enter a message for WaterfallManager.net and press OK\', \'\');
                    if (report_msg==null) {
                        return false;
                    }
                    $.post(\''.basename($_SERVER['PHP_SELF']).'\', {action:\'ajax_wfm\', rid:$(this).attr(\'rel\'), msg:report_msg, csrftoken:\''.$_SESSION["$session_prefix-csrftoken"].'\'})
                        .done(function(data) {
                            if (data!=\'\') {
                                alert(data);
                            }
                        });
                    return false;
                });
            }
        });
    setTimeout(\'wfm_claim_loop();\', 30000);
    return false;
}

$(function(){
    $(\'#security\').on(\'mousemove\', function(){
        if (!wfm_claim_active) {
            wfm_claim_active=true;
            wfm_claim_loop();
        }
    });
});
</script>
';

        $wfm_visit_log='<div id="wfm_visit_log" style="max-height:500px;overflow-y:scroll;">...</div>';
        $wfm_visit_log.='
<script tyle="text/javascript">
var wfm_visit_last_id=0;
var wfm_visit_data=[];
var wfm_visit_active=false;

function wfm_visit_loop() {
    $.post(\''.basename($_SERVER['PHP_SELF']).'\', {action:\'ajax_wfm_get_visit_log\', last_id:wfm_visit_last_id, csrftoken:\''.$_SESSION["$session_prefix-csrftoken"].'\'})
        .done(function(jsonData) {
            if (jsonData!=\'\') {
                var data=JSON.parse(jsonData);
                wfm_visit_last_id=data[\'last_id\'];
                for (var z=0;z<data[\'log\'].length;z++) {
                    wfm_visit_data[wfm_visit_data.length]=data[\'log\'][z];
                }
                var data_string=\'\';
                data_string+=\'<table style="border:1px solid #AAAAAA;font-size:10px;width:100%;">\';
                data_string+=\'<tr style="background-color:#EEEEEE;font-weight:bold;">\';
                data_string+=\'<td>Date<br />Time</td>\';
                data_string+=\'<td>IP<br />Host</td>\';
                data_string+=\'<td>Suggestion<br />Message</td>\';
                data_string+=\'<td>Country Code<br />Country</td>\';
                data_string+=\'<td>ASN<br />ASN Name</td>\';
                data_string+=\'</tr>\';
                for (var z=wfm_visit_data.length-1;z>=0;z--) {
                    var wfm_row_css=\'\';
                    if (wfm_visit_data[z][\'WFM_vLog_suggestion\']==\'allow\') {
                        wfm_row_css=\'background-color:#DDFFDD;\';
                    } else {
                        wfm_row_css=\'background-color:#FFDDDD;\';
                    }
                    data_string+=\'<tr style="border-top:1px solid #AAAAAA;\'+wfm_row_css+\'">\';
                    data_string+=\'<td><b>\'+wfm_visit_data[z][\'WFM_vLog_time\']+\'</td>\';
                    data_string+=\'<td title="\'+wfm_visit_data[z][\'WFM_vLog_useragent\']+\'"><b><a href="http://www.tcpiputils.com/browse/ip-address/\'+wfm_visit_data[z][\'WFM_vLog_IP\']+\'" target="_blank" style="color:#5555AA;" title="View details about \'+wfm_visit_data[z][\'WFM_vLog_IP\']+\' at tcpiputils.com">\'+wfm_visit_data[z][\'WFM_vLog_IP\']+\'</a></b><br />\'+wfm_visit_data[z][\'WFM_vLog_host\']+\'</td>\';
                    data_string+=\'<td><b>\'+wfm_visit_data[z][\'WFM_vLog_suggestion\']+\'</b><br />\'+wfm_visit_data[z][\'WFM_vLog_reason\']+\'</td>\';
                    data_string+=\'<td><b>\'+wfm_visit_data[z][\'WFM_vLog_country_code\']+\'</b><br />\'+wfm_visit_data[z][\'WFM_vLog_country\']+\'</td>\';
                    if (wfm_visit_data[z][\'WFM_vLog_asn\']>0) {
                        data_string+=\'<td><b>\'+wfm_visit_data[z][\'WFM_vLog_asn\']+\'</b><br />\'+wfm_visit_data[z][\'WFM_vLog_asn_name\']+\'</td>\';
                    } else {
                        data_string+=\'<td></td>\';
                    }
                    data_string+=\'</tr>\';
                }
                data_string+=\'</table>\';
                $(\'#wfm_visit_log\').html(data_string);
            }
        });
    setTimeout(\'wfm_visit_loop();\', 30000);
    return false;
}

$(function(){
    $(\'#security\').on(\'mousemove\', function(){
        if (!wfm_visit_active) {
            wfm_visit_active=true;
            wfm_visit_loop();
        }
    });
});
</script>
';
        // replace wfm_log / wfm_visit_log
        $page = str_replace('<:: wfm_log ::>', $wfm_log, $page);
        $page = str_replace('<:: wfm_visit_log ::>', $wfm_visit_log, $page);
    }
}

?>