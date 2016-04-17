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

/**
 * Class VariableInfoViewHelper
 * @package Flowpack\EelCraft\Neos\ViewHelpers
 */
class VariableInfoViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	
	/**
	 * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
	 * @see AbstractViewHelper::isOutputEscapingEnabled()
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * @param mixed $variable
	 * @param string $as
	 * @return mixed
	 */
	public function render($variable, $as = 'variableInformation') {
		$reflectionService = $this->objectManager->get('TYPO3\Flow\Reflection\ReflectionService');

		$variableInformation = array(
			'isScalarValue' => FALSE
		);

		$variableInformation['baseType'] = $this->determineVariableType($variable);
		if ($variableInformation['baseType'] !== 'array' && $variableInformation['baseType'] !== 'object') {
			$variableInformation['isScalarValue'] = TRUE;
		}

		if ($variableInformation['baseType'] === 'object') {
			$variableInformation['gettableProperties'] = \TYPO3\Flow\Reflection\ObjectAccess::getGettablePropertyNames($variable);
			$variableInformation['className'] = $reflectionService->getClassNameByObject($variable);
		}

		$this->templateVariableContainer->add($as, $variableInformation);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove($as);

		return $output;
	}

	protected function determineVariableType($value) {
		if (is_object($value)) {
			return 'object';
		} elseif (is_string($value)) {
			return 'string';
		} elseif (is_integer($value)) {
			return 'integer';
		} elseif (is_bool($value)) {
			return 'boolean';
		} elseif (is_float($value)) {
			return 'float';
		} elseif (is_array($value)) {
			return 'array';
		} elseif ($value === NULL) {
			return 'null';
		}
	}
}
?>