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

class BackslashToUnderscoreViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	
	/**
	 * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
	 * @see AbstractViewHelper::isOutputEscapingEnabled()
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @param string $string
	 * @return string
	 */
	public function render($string = NULL) {
		if ($string === NULL) {
			$string = $this->renderChildren();
		}
		return str_replace('\\', '_', $string);
	}
}
?>