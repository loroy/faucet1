<?php

// NastyHosts (log only) 1.00
// by WaterfallManager.net NeedIfFindIt https://bitcointalk.org/index.php?action=profile;u=391838
// working demo at http://WaterfallManager.net/demo/

class nh {
    public function __construct() {
    }

    public function admin_config_top() {
        global $sql, $currency, $session_prefix;
        if(array_key_exists("$session_prefix-logged_in", $_SESSION)) {
            // wfm ajax call
            if (!empty($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'ajax_nh_get_claim_log':
                        if (empty($_POST['last_id'])) {
                            $last_id=0;
                        } else {
                            $last_id=(int)$_POST['last_id'];
                        }
                        //
                        $nh_log_response=array();
                        $nh_log_response['log']=array();
                        $maxid=$last_id;

                        $nh_log_query=$sql->query("SELECT
                                                      Faucetinabox_NH_Log_id as NH_Log_id,
                                                      Faucetinabox_NH_Log_time as NH_Log_time,
                                                      Faucetinabox_NH_Log_IP as NH_Log_IP,
                                                      Faucetinabox_NH_Log_address as NH_Log_address,
                                                      Faucetinabox_NH_Log_address_ref as NH_Log_address_ref,
                                                      Faucetinabox_NH_Log_suggestion as NH_Log_suggestion,
                                                      Faucetinabox_NH_Log_reason as NH_Log_reason,
                                                      Faucetinabox_NH_Log_country_code as NH_Log_country_code,
                                                      Faucetinabox_NH_Log_country as NH_Log_country,
                                                      Faucetinabox_NH_Log_asn as NH_Log_asn,
                                                      Faucetinabox_NH_Log_asn_name as NH_Log_asn_name,
                                                      Faucetinabox_NH_Log_host as NH_Log_host,
                                                      Faucetinabox_NH_Log_useragent as NH_Log_useragent
                                                    FROM
                                                      Faucetinabox_NH_Log
                                                    WHERE
                                                      Faucetinabox_NH_Log_id>".(int)$last_id."
                                                    AND
                                                      Faucetinabox_NH_Log_time>".(time()-86400)."
                                                    ORDER BY
                                                      Faucetinabox_NH_Log_id
                                                    DESC
                                                    LIMIT 2500
                                                    ;");
                        while ($nh_log_row=$nh_log_query->fetch(PDO::FETCH_ASSOC)) {
                            if ($nh_log_row['NH_Log_id']>$maxid) {
                                $maxid=$nh_log_row['NH_Log_id'];
                            }
                            unset($nh_log_row['NH_Log_id']);
                            $nh_log_row['NH_Log_time']='<b>'.date('Y.m.d', $nh_log_row['NH_Log_time']).'</b><br />'.date('H:i:s', $nh_log_row['NH_Log_time']);
                            $nh_log_row['NH_Log_address']=htmlspecialchars($nh_log_row['NH_Log_address']);
                            $nh_log_row['NH_Log_address_ref']=htmlspecialchars($nh_log_row['NH_Log_address_ref']);
                            $nh_log_row['NH_Log_suggestion']=htmlspecialchars($nh_log_row['NH_Log_suggestion']);
                            $nh_log_row['NH_Log_country']=htmlspecialchars($nh_log_row['NH_Log_country']);
                            $nh_log_row['NH_Log_asn_name']=htmlspecialchars($nh_log_row['NH_Log_asn_name']);
                            $nh_log_row['NH_Log_host']=htmlspecialchars($nh_log_row['NH_Log_host']);
                            $nh_log_row['NH_Log_useragent']=htmlspecialchars($nh_log_row['NH_Log_useragent']);
                            $nh_log_response['log'][]=$nh_log_row;
                        }
                        // reverse the array
                        $nh_log_response['log']=array_reverse($nh_log_response['log']);

                        $nh_log_response['last_id']=$maxid;
                        echo json_encode($nh_log_response);
                        exit;
                    break;
                }
            }
        }
    }

    public function admin_config() {
        global $sql, $page, $session_prefix;

        $nh_log='<div id="nh_claim_log" style="max-height:500px;overflow-y:scroll;">...</div>';
        $nh_log.='
<script tyle="text/javascript">
var nh_claim_last_id=0;
var nh_claim_data=[];
var nh_claim_active=false;

function nh_claim_loop() {
    $.post(\''.basename($_SERVER['PHP_SELF']).'\', {action:\'ajax_nh_get_claim_log\', last_id:nh_claim_last_id, csrftoken:\''.$_SESSION["$session_prefix-csrftoken"].'\'})
        .done(function(jsonData) {
            if (jsonData!=\'\') {
                var data=JSON.parse(jsonData);
                nh_claim_last_id=data[\'last_id\'];
                for (var z=0;z<data[\'log\'].length;z++) {
                    nh_claim_data[nh_claim_data.length]=data[\'log\'][z];
                }
                var data_string=\'\';

                data_string+=\'<table style="border:1px solid #AAAAAA;font-size:10px;width:100%;">\';
                data_string+=\'<tr style="background-color:#EEEEEE;font-weight:bold;">\';
                data_string+=\'<td>Date<br />Time</td>\';
                data_string+=\'<td>IP<br />Host</td>\';
                data_string+=\'<td>Address<br />REF Address</td>\';
                data_string+=\'<td>Suggestion<br />Message</td>\';
                data_string+=\'<td>Country Code<br />Country</td>\';
                data_string+=\'<td>ASN<br />ASN Name</td>\';
                data_string+=\'</tr>\';
                for (var z=nh_claim_data.length-1;z>=0;z--) {
                    var nh_row_css=\'\';
                    if (nh_claim_data[z][\'NH_Log_suggestion\']==\'allow\') {
                        nh_row_css=\'background-color:#DDFFDD;\';
                    } else {
                        nh_row_css=\'background-color:#FFDDDD;\';
                    }
                    data_string+=\'<tr style="border-top:1px solid #AAAAAA;\'+nh_row_css+\'">\';
                    data_string+=\'<td><b>\'+nh_claim_data[z][\'NH_Log_time\']+\'</td>\';
                    data_string+=\'<td title="\'+nh_claim_data[z][\'NH_Log_useragent\']+\'"><b><a href="http://www.tcpiputils.com/browse/ip-address/\'+nh_claim_data[z][\'NH_Log_IP\']+\'" target="_blank" style="color:#5555AA;" title="View details about \'+nh_claim_data[z][\'NH_Log_IP\']+\' at tcpiputils.com">\'+nh_claim_data[z][\'NH_Log_IP\']+\'</a></b><br />\'+nh_claim_data[z][\'NH_Log_host\']+\'</td>\';

                    data_string+=\'<td>\';
                    data_string+=\'<a href="https://faucethub.io/balance/\'+nh_claim_data[z][\'NH_Log_address\']+\'" target="_blank" style="color:#222280;" title="View at FaucetHub.io">FH</a>&nbsp;\';
                    data_string+=\'<a href="https://faucetsystem.com/check/\'+nh_claim_data[z][\'NH_Log_address\']+\'/" target="_blank" style="color:#222280;" title="View at FaucetSystem.com">FS</a>&nbsp;\';
                    data_string+=nh_claim_data[z][\'NH_Log_address\'];
                    if (nh_claim_data[z][\'NH_Log_address_ref\']!=\'\') {
                        data_string+=\'<br /><a href="https://faucethub.io/balance/\'+nh_claim_data[z][\'NH_Log_address_ref\']+\'" target="_blank" style="color:#5555AA;" title="View at FaucetHub.io">FH</a>&nbsp;\';
                        data_string+=\'<a href="https://faucetsystem.com/check/\'+nh_claim_data[z][\'NH_Log_address_ref\']+\'/" target="_blank" style="color:#5555AA;" title="View at FaucetSystem.com">FS</a>&nbsp;\';
                        data_string+=nh_claim_data[z][\'NH_Log_address_ref\'];
                    }
                    data_string+=\'</td>\';

                    data_string+=\'<td><b>\'+nh_claim_data[z][\'NH_Log_suggestion\']+\'</b><br />\'+nh_claim_data[z][\'NH_Log_reason\']+\'</td>\';
                    data_string+=\'<td><b>\'+nh_claim_data[z][\'NH_Log_country_code\']+\'</b><br />\'+nh_claim_data[z][\'NH_Log_country\']+\'</td>\';
                    if (nh_claim_data[z][\'NH_Log_asn\']>0) {
                        data_string+=\'<td><b>\'+nh_claim_data[z][\'NH_Log_asn\']+\'</b><br />\'+nh_claim_data[z][\'NH_Log_asn_name\']+\'</td>\';
                    } else {
                        data_string+=\'<td></td>\';
                    }
                    data_string+=\'</tr>\';
                }
                data_string+=\'</table>\';
                $(\'#nh_claim_log\').html(data_string);
            }
        });
    setTimeout(\'nh_claim_loop();\', 30000);
    return false;
}

$(function(){
    $(\'#security\').on(\'mousemove\', function(){
        if (!nh_claim_active) {
            nh_claim_active=true;
            nh_claim_loop();
        }
    });
});
</script>
';

        $page = str_replace('<:: nh_log ::>', $nh_log, $page);
    }
}

?>