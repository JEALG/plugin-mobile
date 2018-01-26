<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

header('Content-Type: application/json');

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
global $jsonrpc;
GLOBAL $_USER_GLOBAL;
if (!is_object($jsonrpc)) {
	throw new Exception(__('JSONRPC object not defined', __FILE__), -32699);
}

$params = $jsonrpc->getParams();
$PluginToSend = mobile::PluginToSend();
log::add('mobile', 'debug', 'Appel API Mobile > '.$jsonrpc->getMethod());

if ($jsonrpc->getMethod() == 'sync') {

	log::add('mobile', 'debug', 'Demande de Sync');
	
	//RDK//
	$rdk = null;
	if(jeedom::version() >= '3.2.0'){
		log::add('mobile', 'debug', 'Demande du RDK');
		$rdk = config::genKey();
		$registerDevice = $_USER_GLOBAL->getOptions('registerDevice', array());
		if (!is_array($registerDevice)) {
            $registerDevice = array();
        }
        $registerDevice[sha512($rdk)] = array();
        $registerDevice[sha512($rdk)]['datetime'] = date('Y-m-d H:i:s');
        $registerDevice[sha512($rdk)]['ip'] = getClientIp();
        $registerDevice[sha512($rdk)]['session_id'] = session_id();
        $_USER_GLOBAL->setOptions('registerDevice', $registerDevice);
        $_USER_GLOBAL->save();
        log::add('mobile', 'debug', 'RDK :'.$rdk);
	}
	//RDK//
	
	$sync_new = mobile::change_cmdAndeqLogic(mobile::discovery_cmd($PluginToSend),mobile::discovery_eqLogic($PluginToSend));
	//log::add('mobile', 'debug', 'Sync cmd et eqlogics > '.json_encode($sync_new));
	$eqLogics = $sync_new[1];
	$cmds = $sync_new[0];
	
	log::add('mobile', 'debug', 'IQ TYPE > '.$params['Iq']);
	
	$objects = mobile::delete_object_eqlogic_null(mobile::discovery_object(),$eqLogics['eqLogics']);
	
	if($params['Iq'] != '' || $params['Iq'] != null || $params['Iq'] != 'undefined' || $params['Iq'] != 'null'){
		log::add('mobile', 'debug', 'IQ disponible');
		if(isset($params['notificationProvider']) || $params['notificationProvider'] != ''){
			log::add('mobile', 'debug', 'notificationProvider Disponible');
			$mobileEqLogic = eqLogic::byLogicalId($params['Iq'],'mobile');
			if(is_object($mobileEqLogic)){
				$nameEqlogic = $mobileEqLogic->getName();
				log::add('mobile', 'debug', 'EqLogic dispo');
				$arn = $mobileEqLogic->getConfiguration('notificationArn', null);
				$arnMobile = substr($params['notificationProvider'], 1, -1);
				if($arn == null){
					log::add('mobile', 'debug', 'arn null dans la configuration > '.$arn);
					$mobileEqLogic->setConfiguration('notificationArn',$arnMobile);
					$mobileEqLogic->save();
				}else{
					log::add('mobile', 'debug', 'arn NON null dans la configuration > '.$arn);
					if($arn != $arnMobile){
						$mobileEqLogic->setConfiguration('notificationArn',$arnMobile);
						$mobileEqLogic->save();
					}
				}
			}else{
				log::add('mobile', 'debug', 'EqLogic Non dispo');
				$platform = $params['platform'];
				$nameEqlogic = $platform.'-'.config::genKey(3);
				$user = user::byHash($params['apikey']);
				$userId = $user->getId();
				$mobile = new eqLogic;
				$mobile->setEqType_name('mobile');
				$mobile->setName($nameEqlogic);
				$mobile->setConfiguration('type_mobile',$platform);
				$mobile->setConfiguration('affect_user',$userId);
				$mobile->setConfiguration('validate',no);
				$mobile->setConfiguration('notificationArn',substr($params['notificationProvider'],1,-1));
				$mobile->setIsEnable(1);
				$key = config::genKey(32);
				$mobile->setLogicalId($key);
				$params['Iq'] = $key;
				$mobile->save();
				
			}
		}
		if($rdk == null){
			$config = array('datetime' => getmicrotime(),'Iq' => $params['Iq'],'NameMobile' => $mobileEqLogic);
		}else{
			$config = array('datetime' => getmicrotime(),'Iq' => $params['Iq'],'NameMobile' => $mobileEqLogic,'rdk' => $rdk);
		}
		$sync_array = array(
			'eqLogics' => $eqLogics['eqLogics'],
			'cmds' => $cmds['cmds'],
			'objects' => $objects,
			'scenarios' => mobile::discovery_scenario(),
			'messages' => mobile::discovery_message(),
			'plans' => mobile::discovery_plan(),
			'config' => $config
		);
	}else{
		$platform = $params['platform'];
		$nameEqlogic = $platform.'-'.config::genKey(3);
		$user = user::byHash($params['apikey']);
		$userId = $user->getId();
		$mobile = new eqLogic;
		$mobile->setEqType_name('mobile');
		$mobile->setName($nameEqlogic);
		$mobile->setConfiguration('type_mobile',$platform);
		$mobile->setConfiguration('affect_user',$userId);
		$mobile->setConfiguration('validate',no);
		$mobile->setIsEnable(1);
		$key = config::genKey(32);
		$mobile->setLogicalId($key);
		$mobile->save();
		if($rdk == null){
			$config = array('datetime' => getmicrotime(),'Iq' => $key,'NameMobile' => $mobileEqLogic);
		}else{
			$config = array('datetime' => getmicrotime(),'Iq' => $key,'NameMobile' => $mobileEqLogic,'rdk' => $rdk);
		}
		$sync_array = array(
			'eqLogics' => $eqLogics['eqLogics'],
			'cmds' => $cmds['cmds'],
			'objects' => $objects,
			'scenarios' => mobile::discovery_scenario(),
			'messages' => mobile::discovery_message(),
			'plans' => mobile::discovery_plan(),
			'config' => $config
		);
	}
	
	
	$jsonrpc->makeSuccess($sync_array);
}


// Eqlogic byId
if ($jsonrpc->getMethod() == 'cmdsbyEqlogicID') {
	log::add('mobile', 'debug', 'Interogation du module id:'.$params['id'].' Pour les cmds');
	$sync_new = mobile::change_cmdAndeqLogic(mobile::discovery_cmd($PluginToSend),mobile::discovery_eqLogic($PluginToSend));
	$cmds = $sync_new[0];
	$i = 0;
	$commandes = $cmds['cmds'];
	$cmdAPI = array();
	foreach($commandes as $cmd){
        if(isset($cmd["eqLogic_id"])){
                if($cmd["eqLogic_id"] != $params['id']){
                        unset($commandes[$i]);
                }else{
	                array_push($cmdAPI, $commandes[$i]);
                }
        }
        $i++;   
    }
        log::add('mobile', 'debug', 'Commande > '.json_encode($cmdAPI));
        //$retourApi = eqLogic::byId($params['id']);
        //$cmdAPI = $retourApi->getCmd();
        
        //log::add('mobile', 'debug', 'Commande Normal > '.json_encode($cmdAPI));
	$jsonrpc->makeSuccess($cmdAPI);
}

if ($jsonrpc->getMethod() == 'Iq') {
	$platform = $params['platform'];
	$user = user::byHash($params['apikey']);
	$userId = $user->getId();
	$mobile = new eqLogic;
	$mobile->setEqType_name('mobile');
	$mobile->setName($platform.'-'.config::genKey(3));
	$mobile->setConfiguration('type_mobile',$platform);
	$mobile->setConfiguration('affect_user',$userId);
	$mobile->setConfiguration('validate',no);
	$mobile->setIsEnable(1);
	$key = config::genKey(32);
	$mobile->setLogicalId($key);
	$mobile->save();
	$jsonrpc->makeSuccess($key);	
}

if($jsonrpc->getMethod() == 'IqValidation'){
	$mobile = eqLogic::byLogicalId($params['Iq'],'mobile');
	if(is_object($mobile)){
		$mobile->setConfiguration('validate',yes);
		$mobile->save();
		$jsonrpc->makeSuccess('validate');
	}else{
		$jsonrpc->makeSuccess('not_iq');
	}
}
if ($jsonrpc->getMethod() == 'version') {
	$mobile_update = update::byLogicalId('mobile');
	$jsonrpc->makeSuccess($mobile_update->getLocalVersion());	
}
if ($jsonrpc->getMethod() == 'event') {
	$eqLogic = eqLogic::byId($params['eqLogic_id']);
	if (!is_object($eqLogic)) {
		throw new Exception(__('EqLogic inconnu : ', __FILE__) . $params['eqLogic_id']);
	}
	$cmd = $eqLogic->getCmd(null, $params['cmd_logicalId']);
	if (!is_object($cmd)) {
		throw new Exception(__('Cmd inconnu : ', __FILE__) . $params['cmd_logicalId']);
	}
	$cmd->event($params['value']);
	$jsonrpc->makeSuccess();
}
// ASK
if($jsonrpc->getMethod() == 'askText'){
	log::add('mobile', 'debug', 'arriver reponse ask Textuel depuis le mobile > '.$params['Iq']);
	$mobile = eqLogic::byLogicalId($params['Iq'],'mobile');
	log::add('mobile', 'debug', 'mobile >'.json_encode($mobile));
	if(is_object($mobile)){
		$askCasse = config::byKey('askCasse','mobile', false);
		$textCasse = $params['text'];
		if($askCasse == false){
			$textCasse = strtolower($params['text']);
		}
		log::add('mobile', 'debug', 'Mobile bien trouvé casse -> '.$askCasse.' text : '.$textCasse);
		$cmd = $mobile->getCmd(null, 'notif');
		log::add('mobile', 'debug', 'IQ > '.$params['Iq'].' demande cmd > '.$cmd->getId());
		if ($cmd->askResponse($textCasse)) {
			log::add('mobile', 'debug', 'ask bien trouvé réponse validée');
			$jsonrpc->makeSuccess();
		}
	}
}

throw new Exception(__('Aucune demande', __FILE__));
?>
