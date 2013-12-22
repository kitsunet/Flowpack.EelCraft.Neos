<?php
namespace Flowpack\EelCraft\Neos\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.EelCraft.Neos".*
 *                                                                        *
 *                                                                        */

use Flowpack\EelCraft\Neos\Aspects\ContextWatcherAspect;
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
	 * @Flow\Inject
	 * @var ContextWatcherAspect
	 */
	protected $contextWatcher;

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

		$this->contextWatcher->setNodePath($node->getPath());

		// This is ugly but that way the contextWatcher can catch when $node is used during rendering.
		$view = new \TYPO3\Neos\View\TypoScriptView();
		$this->controllerContext->getRequest()->setFormat('html');
		$view->setControllerContext($this->controllerContext);
		$view->assign('value', $node);
		$view->render();
		$runtime = ObjectAccess::getProperty($view, 'typoScriptRuntime', TRUE);
		$this->defaultContextVariables = ObjectAccess::getProperty($runtime, 'defaultContextVariables', TRUE);

		$collectedContexts = $this->contextWatcher->getCollectedContexts();

		foreach ($collectedContexts as $key => $contextEnvironment) {
			try {
				$result = $this->evaluateEelExpression($eelExpression, $contextEnvironment['context'], $contextEnvironment['arguments']['contextObject']);
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

}

?>