<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003, 2004, 2005 Kasper Skaarhoj (kasper@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * templavoila module cm2
 *
 * $Id$
 *
 * @author        Kasper Skaarhoj <kasper@typo3.com>
 * @co-author    Robert Lemke <robert@typo3.org>
 */

// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require(dirname(__FILE__) . '/conf.php');
require($BACK_PATH . 'init.php');
$LANG->includeLLFile('EXT:templavoila/cm2/locallang.xml');

/**
 * Class for displaying color-marked-up version of FlexForm XML content.
 *
 * @author    Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_templavoila
 */
class tx_templavoila_cm2 extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	// External, static:
	var $option_linenumbers = TRUE; // Showing linenumbers if true.

	// Internal, GPvars:
	var $viewTable = array(); // Array with tablename, uid and fieldname
	var $returnUrl = ''; // (GPvar "returnUrl") Return URL if the script is supplied with that.

	/**
	 * Main function, drawing marked up XML.
	 *
	 * @return    void
	 */
	function main() {
		global $LANG, $BACK_PATH;

		// Check admin: If this is changed some day to other than admin users we HAVE to check if there is read access to the record being selected!
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			die('no access.');
		}

		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));

		// Draw the header.
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('EXT:templavoila/resources/templates/cm2_default.html');
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->divClass = '';

		// XML code:
		$this->viewTable = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('viewRec');

		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->viewTable['table'], $this->viewTable['uid']); // Selecting record based on table/uid since adding the field might impose a SQL-injection problem; at least the field name would have to be checked first.
		if (is_array($record)) {

			// Set current XML data:
			$currentXML = $record[$this->viewTable['field_flex']];

			// Clean up XML:
			$cleanXML = '';
			if ($GLOBALS['BE_USER']->isAdmin()) {
				if ('tx_templavoila_flex' == $this->viewTable['field_flex']) {
					$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
					if ($record['tx_templavoila_flex']) {
						$cleanXML = $flexObj->cleanFlexFormXML($this->viewTable['table'], 'tx_templavoila_flex', $record);

						// If the clean-button was pressed, save right away:
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('_CLEAN_XML')) {
							$dataArr = array();
							$dataArr[$this->viewTable['table']][$this->viewTable['uid']]['tx_templavoila_flex'] = $cleanXML;

							// Init TCEmain object and store:
							$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
							$tce->stripslashes_values = 0;
							$tce->start($dataArr, array());
							$tce->process_datamap();

							// Re-fetch record:
							$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->viewTable['table'], $this->viewTable['uid']);
							$currentXML = $record[$this->viewTable['field_flex']];
						}
					}
				}
			}

			if (md5($currentXML) != md5($cleanXML)) {
				// Create diff-result:
				$t3lib_diff_Obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\DiffUtility');
				$diffres = $t3lib_diff_Obj->makeDiffDisplay($currentXML, $cleanXML);

				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'\TYPO3\CMS\Core\Messaging\FlashMessage',
					$LANG->getLL('needsCleaning', 1),
					'',
					\TYPO3\CMS\Core\Messaging\FlashMessage::INFO
				);
				$xmlContentMarkedUp = $flashMessage->render();

				$xmlContentMarkedUp .= '<table border="0">
					<tr class="bgColor5 tableheader">
						<td>' . $LANG->getLL('current', 1) . '</td>
					</tr>
					<tr>
						<td>' . $this->markUpXML($currentXML) . '<br/><br/></td>
					</tr>
					<tr class="bgColor5 tableheader">
						<td>' . $LANG->getLL('clean', 1) . '</td>
					</tr>
					<tr>
						<td>' . $this->markUpXML($cleanXML) . '</td>
					</tr>
					<tr class="bgColor5 tableheader">
						<td>' . $LANG->getLL('diff', 1) . '</td>
					</tr>
					<tr>
						<td>' . $diffres . '
						<br/><br/><br/>

						<form action="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') . '" method="post">
							<input type="submit" value="' . $LANG->getLL('cleanUp', 1) . '" name="_CLEAN_XML" />
						</form>

						</td>
					</tr>
				</table>

				';
			} else {
				$xmlContentMarkedUp = '';
				if ($cleanXML) {
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'\TYPO3\CMS\Core\Messaging\FlashMessage',
						$LANG->getLL('XMLclean', 1),
						'',
						\TYPO3\CMS\Core\Messaging\FlashMessage::OK
					);
					$xmlContentMarkedUp = $flashMessage->render();
				}
				$xmlContentMarkedUp .= $this->markUpXML($currentXML);
			}

			$this->content .= $this->doc->section('', $xmlContentMarkedUp, 0, 1);
		}

		// Add spacer:
		$this->content .= $this->doc->spacer(10);

		$docHeaderButtons = $this->getDocHeaderButtons();
		$docContent = array(
			'CSH' => $docHeaderButtons['csh'],
			'CONTENT' => $this->content
		);

		$content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$content .= $this->doc->moduleBody(
			array(),
			$docHeaderButtons,
			$docContent
		);
		$content .= $this->doc->endPage();

		// Replace content with templated content
		$this->content = $content;
	}

	/**
	 * Prints module content.
	 *
	 * @return    void
	 */
	function printContent() {
		echo $this->content;
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return    array        Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_txtemplavoilaCM1', '', $this->backPath),
			'back' => '',
			'shortcut' => $this->getShortcutButton(),
		);

		// Back
		if ($this->returnUrl) {
			$backIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back');
			$buttons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', TRUE) . '">' .
				$backIcon .
				'</a>';
		}

		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return    string        HTML representiation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

		return $result;
	}

	/**
	 * Mark up XML content
	 *
	 * @param    string        XML input
	 *
	 * @return    string        HTML formatted output, marked up in colors
	 */
	function markUpXML($str) {

		// Make instance of syntax highlight class:
		$hlObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_syntaxhl');

		// Check which document type, if applicable:
		if (strstr(substr($str, 0, 100), '<T3DataStructure')) {
			$title = 'Syntax highlighting <T3DataStructure> XML:';
			$formattedContent = $hlObj->highLight_DS($str);
		} elseif (strstr(substr($str, 0, 100), '<T3FlexForms')) {
			$title = 'Syntax highlighting <T3FlexForms> XML:';
			$formattedContent = $hlObj->highLight_FF($str);
		} else {
			$title = 'Unknown format:';
			$formattedContent = '<span style="font-style: italic; color: #666666;">' . htmlspecialchars($str) . '</span>';
		}

		// Check line number display:
		if ($this->option_linenumbers) {
			$lines = explode(chr(10), $formattedContent);
			foreach ($lines as $k => $v) {
				$lines[$k] = '<span style="color: black; font-weight:normal;">' . str_pad($k + 1, 4, ' ', STR_PAD_LEFT) . ':</span> ' . $v;
			}
			$formattedContent = implode(chr(10), $lines);
		}

		// Output:
		return '
			<h3>' . htmlspecialchars($title) . '</h3>
			<pre class="ts-hl">' . $formattedContent . '</pre>
			';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm2/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/cm2/index.php']);
}


// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_templavoila_cm2');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
