<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');

/**
 * Inserts the search result box
 *
 * @author	Oliver Kliebisch
 * @copyright	2008-2010 Oliver Kliebisch
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     net.hawkes.threadadd.quicksearch
 * @subpackage  system.event.listener
 * @category    Burning Board
 */
class QuickSearchListener implements EventListener {
	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!$eventObj->preview) {
			WCF::getTPL()->assign('board', $eventObj->board);
			WCF::getTPL()->append('additionalInformationFields', WCF::getTPL()->fetch('quickSearch'));
		}
	}
}
?>