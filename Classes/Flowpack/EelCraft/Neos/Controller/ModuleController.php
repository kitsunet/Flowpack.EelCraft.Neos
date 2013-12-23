<?php
namespace Flowpack\EelCraft\Neos\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.EelCraft.Neos".*
 *                                                                        *
 *                                                                        */

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\ParserException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;
use TYPO3\Eel\FlowQuery\FlowQuery;

class ModuleController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var CompilingEvaluator
	 */
	protected $eelEvaluator;

	/**
	 * Retrieved from the TypoScript Runtime
	 * @var array
	 */
	protected $defaultContextVariables = array();

	/**
	 * Index of module
	 *
	 * @param NodeInterface $node
	 * @param string $eelExpression
	 * @return void
	 */
	public function indexAction($node = NULL, $eelExpression = NULL) {
		$this->view->assignMultiple(array(
			'node' => $node,
			'eelExpression' => $eelExpression
		));

		if ($node !== NULL && $eelExpression !== NULL) {
			$this->view->assign('evaluationData', $this->evaluate($node, $eelExpression));
		}
	}

	/**
	 * Get all registered node types
	 *
	 * @param NodeInterface $node
	 * @param string $eelExpression
	 * @return array
	 */
	protected function evaluate($node = NULL, $eelExpression = NULL) {
		$eelExpression = $this->cleanExpression($eelExpression);

		// This is ugly but that way the contextWatcher can catch when $node is used during rendering.
		$q = new FlowQuery(array($node));
		$document = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$view = new \TYPO3\Neos\View\TypoScriptView();
		$controllerContextForRendering = $this->createControllerContextForRendering();
		$view->setControllerContext($controllerContextForRendering);
		$view->assign('value', $document);
		$typoScriptService = new \Flowpack\EelCraft\Neos\TypoScript\TypoScriptService();
		$typoScriptService->nodePath = $node->getPath();
		ObjectAccess::setProperty($view, 'typoScriptService', $typoScriptService, TRUE);
		$view->setTypoScriptPath('root');
		 $view->render();

		$runtime = ObjectAccess::getProperty($view, 'typoScriptRuntime', TRUE);
		$collectedContexts = $runtime->getCollectedContexts();
		krsort($collectedContexts);
		foreach ($collectedContexts as $key => $contextEnvironment) {
			try {
				$result = $this->evaluateEelExpression($eelExpression, $contextEnvironment['context'], (isset($contextEnvironment['arguments']['contextObject']) ? $contextEnvironment['arguments']['contextObject'] : NULL));
				$collectedContexts[$key]['evaluationResult'] = $result;
			} catch (ParserException $parserException) {
				$this->addFlashMessage($parserException->getMessage(), 'Your EEL Expression could not be parsed', Message::SEVERITY_ERROR);
				return array();
			} catch (\Exception $exception) {
				$this->addFlashMessage($exception->getMessage(), 'Your EEL Expression could not be evaluated.', Message::SEVERITY_WARNING);
				return array();
			}
		}

		return $collectedContexts;
	}


	/**
	 * Evaluate an Eel expression
	 *
	 * @param string $expression The Eel expression to evaluate
	 * @param array $currentContext
	 * @param AbstractTypoScriptObject $contextObject An optional object for the "this" value inside the context
	 * @return mixed The result of the evaluated Eel expression
	 */
	protected function evaluateEelExpression($expression, $currentContext, AbstractTypoScriptObject $contextObject = NULL) {
		$contextVariables = array_merge($this->defaultContextVariables, $currentContext);

		$contextVariables['q'] = function ($element) {
			if (is_array($element) || $element instanceof \Traversable) {
				return new FlowQuery($element);
			} else {
				return new FlowQuery(array($element));
			}
		};

		$contextVariables['this'] = $contextObject;

		$context = new \TYPO3\Eel\ProtectedContext($contextVariables);
		$context->whitelist('q');
		$value = $this->eelEvaluator->evaluate($expression, $context);

		return $value;
	}

	/**
	 * We silently strip the TypoScript wrapper for EEL "${}" from the actual EEL expression.
	 *
	 * @param $originalExpression
	 * @return string
	 */
	protected function cleanExpression($originalExpression) {
		if (preg_match(\TYPO3\Eel\Package::EelExpressionRecognizer, $originalExpression, $matches)) {
			$expression = $matches[1];
		} else {
			$expression = $originalExpression;
		}

		return trim($expression);
	}

	/**
	 * @return \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected function createControllerContextForRendering() {
		$httpRequest = \TYPO3\Flow\Http\Request::createFromEnvironment();

		/** @var \TYPO3\Flow\Mvc\ActionRequest $request */
		$request = $httpRequest->createActionRequest();
		$request->setControllerObjectName('TYPO3\Neos\Controller\Frontend\NodeController');
		$request->setFormat('html');

		$uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$uriBuilder->setRequest($request);

		return new \TYPO3\Flow\Mvc\Controller\ControllerContext(
			$request,
			new \TYPO3\Flow\Http\Response(),
			new \TYPO3\Flow\Mvc\Controller\Arguments(array()),
			$uriBuilder
		);
	}
}

?>