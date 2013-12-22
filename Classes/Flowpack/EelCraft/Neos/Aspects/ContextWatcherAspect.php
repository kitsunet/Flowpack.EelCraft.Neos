<?php
namespace Flowpack\EelCraft\Neos\Aspects;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ContextWatcherAspect {

	/**
	 * The node path to find in collected contexts.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * @var array
	 */
	protected $collectedContexts;

	/**
	 * @Flow\Inject(setting="contextWatcher.limit", package="Flowpack.EelCraft.Neos")
	 * @var
	 */
	protected $collectionLimit;

	/**
	 * @param string $nodePath
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * @return string
	 */
	public function getNodePath() {
		return $this->nodePath;
	}

	/**
	 * @return void
	 */
	public function resetCollectedContexts() {
		$this->collectedContexts = array();
	}

	/**
	 * @return array
	 */
	public function getCollectedContexts() {
		return $this->collectedContexts;
	}

	/**
	 * @Flow\Around("method(TYPO3\TypoScript\Core\Runtime->evaluateInternal()) && setting(Flowpack.EelCraft.Neos.contextWatcher.enable)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function collectContext(JoinPointInterface $joinPoint) {
		if ($this->nodePath !== NULL) {
			/**
			 * @var \TYPO3\TypoScript\Core\Runtime $runtime
			 */
			$runtime = $joinPoint->getProxy();
			$currentContext = $runtime->getCurrentContext();

			if (isset($currentContext['node']) && $currentContext['node'] instanceof NodeInterface && count($this->collectedContexts) < $this->collectionLimit) {
				$node = $currentContext['node'];
				if ($node->getPath() === $this->nodePath) {
					$this->collectedContexts[] = array(
						'arguments' => $joinPoint->getMethodArguments(),
						'context' => $currentContext
					);
				}
			}
		}

		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

}
