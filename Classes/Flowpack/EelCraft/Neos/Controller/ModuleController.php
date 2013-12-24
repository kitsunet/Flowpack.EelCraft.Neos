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
	 * @Flow\Inject
	 * @var \Flowpack\EelCraft\Neos\TypoScript\SavedRenderingContext
	 */
	protected $savedRenderingContext;

	/**
	 * Index of module
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function indexAction($node = NULL) {
		$this->view->assign('node', $node);

		if($node !== NULL) {
			$possibleContexts = $this->renderAndFindContext($node);
			ksort($possibleContexts);
			$this->view->assign('possibleContexts', $possibleContexts);
		}
	}

	/**
	 * @param string $eelExpression
	 */
	public function evaluateExpressionsAction($eelExpression = NULL) {
		$this->view->assign('eelExpression', $eelExpression);

		if (!$this->savedRenderingContext->isInitialized()) {
			$this->addFlashMessage('You cannot evaluate Expressions without selecting a context', '', Message::SEVERITY_NOTICE);
			$this->redirect('index');
		}

		$this->view->assign('selectedContext', array(
			'typoScriptPath' => $this->savedRenderingContext->getTypoScriptPath(),
			'context' => $this->savedRenderingContext->getContext()
		));

		if ($eelExpression !== NULL) {
			$eelExpression = $this->cleanExpression($eelExpression);
			$evaluationResult = $this->evaluateEelExpression($eelExpression, $this->savedRenderingContext->getContext());
			$this->view->assign('evaluationResult', $evaluationResult);
		}
	}

	/**
	 *
	 */
	public function clearContextAction() {
		$this->savedRenderingContext->clear();
		$this->forward('index');
	}

	/**
	 * @param NodeInterface $node
	 * @param string $typoScriptPath
	 */
	public function lockContextAction($node, $typoScriptPath) {
		$foundContexts = $this->renderAndFindContext($node);

		if (!isset($foundContexts[$typoScriptPath])) {
			$this->addFlashMessage('The selected TypoScriptPath to get a Context is not available!', '', Message::SEVERITY_ERROR);
		} else {
			$this->savedRenderingContext->setTypoScriptPathAndContext($typoScriptPath, $foundContexts[$typoScriptPath]);
		}

		$this->forward('evaluateExpressions');
	}

	/**
	 * @param NodeInterface $node
	 * @return array
	 */
	protected function renderAndFindContext($node) {
		// This is ugly but that way the ContextCollectingRuntime can catch when $node is used during rendering.
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
		return $runtime->getCollectedContexts();
	}

	/**
	 * Evaluate an Eel expression
	 *
	 * @param string $expression The Eel expression to evaluate
	 * @param array $currentContext
	 * @return mixed The result of the evaluated Eel expression
	 */
	protected function evaluateEelExpression($expression, $currentContext) {
		$contextVariables = array_merge($this->defaultContextVariables, $currentContext);

		$contextVariables['q'] = function ($element) {
			if (is_array($element) || $element instanceof \Traversable) {
				return new FlowQuery($element);
			} else {
				return new FlowQuery(array($element));
			}
		};

		$context = new \TYPO3\Eel\ProtectedContext($contextVariables);
		$context->whitelist('q');
		try {
			$value = $this->eelEvaluator->evaluate($expression, $context);
		} catch (ParserException $parserException) {
			$this->addFlashMessage($parserException->getMessage(), 'Your EEL Expression could not be parsed', Message::SEVERITY_ERROR);
			$value = NULL;
		} catch (\Exception $exception) {
			$this->addFlashMessage($exception->getMessage(), 'Your EEL Expression could not be evaluated.', Message::SEVERITY_WARNING);
			$value = NULL;
		}


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