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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Exception as Exceptions;
use TYPO3\TypoScript\Exception;


class DummyRuntime extends \TYPO3\TypoScript\Core\Runtime {

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * The node path to find in collected contexts.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * @var array
	 */
	protected $collectedContexts = array();

	/**
	 * @Flow\Inject(setting="contextWatcher", package="Flowpack.EelCraft.Neos")
	 * @var array
	 */
	protected $collectionSettings;

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
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\Flow\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
		$settings = $configurationManager->getConfiguration('Settings', 'TYPO3.TypoScript');
		$this->settings = $settings;
		if (isset($this->settings['debugMode'])) {
			$this->debugMode = ($this->settings['debugMode'] === TRUE);
		}
	}

	/**
	 * Inject settings of this package
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {

	}

	/**
	 * Internal evaluation method of absolute $typoScriptpath
	 *
	 * @param string $typoScriptPath
	 * @param string $behaviorIfPathNotFound one of BEHAVIOR_EXCEPTION or BEHAVIOR_RETURNNULL
	 * @param mixed $contextObject the object which will be "this" in Eel expressions, if any.
	 * @throws Exceptions\MissingTypoScriptImplementationException
	 * @throws \Exception
	 * @throws Exceptions\RuntimeException
	 * @throws \Exception
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @throws Exceptions\MissingTypoScriptObjectException
	 * @throws Exceptions\RuntimeException
	 * @return mixed
	 */
	protected function evaluateInternal($typoScriptPath, $behaviorIfPathNotFound, $contextObject = NULL) {
		if ($this->collectionSettings['enable']) {
			$this->collectContext($typoScriptPath, $contextObject);
		}
		return parent::evaluateInternal($typoScriptPath, $behaviorIfPathNotFound, $contextObject);
	}

	/**
	 * @return array
	 */
	public function getDefaultContextVariables() {
		return parent::getDefaultContextVariables();
	}

	/**
	 * @param string $typoScriptPath
	 * @param $contextObject
	 */
	protected function collectContext($typoScriptPath, $contextObject = NULL) {
		if ($this->nodePath !== NULL && strpos($typoScriptPath, '_meta') === FALSE) {
			$currentContext = $this->getCurrentContext();
			if (isset($currentContext['node']) && ($currentContext['node'] instanceof NodeInterface) && (count($this->collectedContexts) < $this->collectionSettings['limit'])) {
				$node = $currentContext['node'];
				if ($node->getPath() === $this->nodePath) {
					if ($contextObject !== NULL) {
						$currentContext['this'] = $contextObject;
					}
					$this->collectedContexts[$typoScriptPath] = array(
						'arguments' => array(
							'typoScriptPath' => preg_replace('/<[^<>]*>/', '', $typoScriptPath),
							'contextObject' => $contextObject
						),
						'context' => $currentContext
					);
				}
			}
		}
	}

}