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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('3rdparty', 'qrcode/qrlib', 'php', 'mobile');

class mobile extends eqLogic {
	/*     * *************************Attributs****************************** */

	private static $_PLUGIN_COMPATIBILITY = array('openzwave', 'rfxcom');

	/*     * ***********************Methode static*************************** */

	/**************************************************************************************/
	/*                                                                                    */
	/*                        Permet d'installer les dépendances                          */
	/*                                                                                    */
	/**************************************************************************************/

	public static function updatemobile() {
		log::remove('mobile_update');
		$cmd = '/bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('mobile_update') . ' 2>&1 &';
		exec($cmd);
	}

	/**************************************************************************************/
	/*                                                                                    */
	/*            Permet de decouvrir tout les modules de la Jeedom compatible            */
	/*                                                                                    */
	/**************************************************************************************/

	public static function discovery($plugin = array()) {
		$return = array();
		foreach ($plugin as $plugin_type) {
			$eqLogics = eqLogic::byType($plugin_type, true);
			if (is_array($eqLogics)) {
				foreach ($eqLogics as $eqLogic) {
					$return[] = self::buildEqlogic($eqLogic);
				}
			}
		}
		return $return;
	}

	public static function buildEqlogic($_eqLogic) {
		$eqLogic_array = utils::o2a($_eqLogic);
		foreach ($_eqLogic->getCmd() as $cmd) {
			$eqLogic_array['cmd'][] = $cmd->exportApi();
		}
		return $eqLogic_array;
	}

	/**************************************************************************************/
	/*                                                                                    */
	/*                         Permet de creer le Json du QRCode                          */
	/*                                                                                    */
	/**************************************************************************************/

	public function getQrCode() {
		$request_qrcode = array(
			'eqLogic_id' => $this->getId(),
			'url_internal' => network::getNetworkAccess('internal'),
			'url_external' => network::getNetworkAccess('external'),
		);
		if ($this->getConfiguration('affect_user') != '') {
			$username = user::byId($this->getConfiguration('affect_user'));
			if (is_object($username)) {
				$request_qrcode['username'] = $username->getLogin();
				$request_qrcode['apikey'] = $username->getHash();
			}
		}
		if (!file_exists(dirname(__FILE__) . '/../../data')) {
			mkdir(dirname(__FILE__) . '/../../data');
		}
		QRcode::png(json_encode($request_qrcode), dirname(__FILE__) . '/../../data/qrcode.png', 'L', 4, 2);
		return 'plugins/mobile/data/qrcode.png?' . strtotime('now');
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}

class mobileCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
											 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
											public function dontRemoveCmd() {
											return true;
											}
											 */

	public function execute($_options = array()) {
		return false;
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
