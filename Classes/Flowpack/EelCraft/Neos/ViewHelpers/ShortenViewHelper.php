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
use TYPO3\Flow\Utility\Unicode\Functions;

class ShortenViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @param string $string
	 * @param integer $maxLength
	 * @return string
	 */
	public function render($string = NULL, $maxLength = 120) {
		if ($string === NULL) {
			$string = $this->renderChildren();
		}
		if (Functions::strlen($string) > $maxLength) {
			$firstPart = Functions::substr($string, 0, intval($maxLength / 2) - 1);
			$secondPart = Functions::substr($string, -(intval($maxLength / 2)));

			$string = $firstPart . '…' . $secondPart;
		}

		return $string;
	}
}
?>