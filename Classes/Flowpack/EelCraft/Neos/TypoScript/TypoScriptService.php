<?php
namespace Flowpack\EelCraft\Neos\TypoScript;

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

class TypoScriptService extends \TYPO3\Neos\Domain\Service\TypoScriptService {

	public $nodePath;

	/**
	 * @Flow\Inject(setting="typoScript.autoInclude", package="TYPO3.Neos")
	 * @var array
	 */
	protected $autoIncludeConfiguration = array();

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $currentSiteNode
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return ContextCollectingRuntime
	 */
	public function createRuntime(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $currentSiteNode, \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$typoScriptObjectTree = $this->getMergedTypoScriptObjectTree($currentSiteNode);
		$typoScriptRuntime = new ContextCollectingRuntime($typoScriptObjectTree, $controllerContext);
		$typoScriptRuntime->setNodePath($this->nodePath);
		return $typoScriptRuntime;
	}
}
