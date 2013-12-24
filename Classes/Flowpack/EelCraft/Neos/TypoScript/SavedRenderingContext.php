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

/**
 * @Flow\Scope("session")
 */
class SavedRenderingContext {

	/**
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * @var string
	 */
	protected $typoScriptPath;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * @return array
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @return string
	 */
	public function getTypoScriptPath() {
		return $this->typoScriptPath;
	}

	/**
	 * @return boolean
	 */
	public function getInitialized() {
		return $this->initialized;
	}

	/**
	 * @return boolean
	 */
	public function isInitialized() {
		return $this->initialized;
	}

	/**
	 * @param string $typoScriptPath
	 * @param array $context
	 */
	public function setTypoScriptPathAndContext($typoScriptPath, $context) {
		$this->typoScriptPath = $typoScriptPath;
		$this->context = $context;
		$this->initialized = TRUE;
	}

	public function clear() {
		$this->typoScriptPath = NULL;
		$this->context = array();
		$this->initialized = FALSE;
	}

}