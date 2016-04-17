<?php
namespace Flowpack\EelCraft\Neos\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.EelCraft.Neos".*
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

class FlowQueryViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	
	/**
	 * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
	 * @see AbstractViewHelper::isOutputEscapingEnabled()
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @param string $operation
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery
	 * @return mixed
	 */
	public function render($operation = 'get', \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery = NULL) {
		if ($flowQuery === NULL) {
			$flowQuery = $this->renderChildren();
		}
		return $flowQuery->__call($operation, array());
	}
}

?>