<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2006  Robert Lemke (robert@typo3.org)
 *  All rights reserved
 *
 *  script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Submodule 'records' for the templavoila page module
 *
 * $Id$
 *
 * @author     Dmitry Dulepov <dmitry@typo3.org>
 */

// Need List lables for delete confirmation
$LANG->includeLLFile('EXT:lang/locallang_mod_web_list.xml');

/**
 * Extension of standard List module
 *
 * @author        Dmitry Dulepov <dmitry@typo3.org>
 * @package        TYPO3
 * @subpackage    tx_templavoila
 */
class tx_templavoila_mod1_recordlist extends localRecordList {

	var $pObj;

	/**
	 * Prepares object to run.
	 *
	 * @param    object &$pObj Parent object (mod1/index.php)
	 *
	 * @return    void
	 */
	function start(&$pObj) {
		$this->pObj = & $pObj;
		$GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] = 1; // enable extended view
		$this->script = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/index.php';

		return parent::start($this->pObj->rootElementUid_pidForContent,
			'', //$this->pObj->MOD_SETTINGS['recordsView_table'],
			intval($this->pObj->MOD_SETTINGS['recordsView_start']));
	}

	/**
	 * Creates the button with link to either forward or reverse
	 *
	 * @param    string        Type: "fwd" or "rwd"
	 * @param    integer        Pointer
	 * @param    string        Table name
	 *
	 * @return    string
	 */
	function fwd_rwd_HTML($type, $pointer, $table = '') {
		$content = '';
		switch ($type) {
			case 'fwd':
				$href = $this->returnUrl . '&SET[recordsView_start]=' . ($pointer - $this->iLimit) . '&SET[recordsView_table]=' . $table;
				$content = '<a href="' . htmlspecialchars($href) . '">' .
					\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-up') .
					'</a> <i>[1 - ' . $pointer . ']</i>';
				break;
			case 'rwd':
				$href = $this->returnUrl . '&SET[recordsView_start]=' . $pointer . '&SET[recordsView_table]=' . $table;
				$content = '<a href="' . htmlspecialchars($href) . '">' .
					\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-down') .
					'</a> <i>[' . ($pointer + 1) . ' - ' . $this->totalItems . ']</i>';
				break;
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_recordlist.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/mod1/class.tx_templavoila_mod1_recordlist.php']);
}
