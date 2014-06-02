<?php

namespace AF\Domain\Algorithm\Selection\TextKey;

use AF\Domain\Algorithm\InputSet;
use AF\Domain\Algorithm\Selection\TextKeySelectionAlgo;
use AF\Domain\CalculationException;
use Core_Exception_NotFound;
use Exec\Execution\Select;
use Exec\Provider\ValueInterface;
use TEC\Exception\InvalidExpressionException;
use TEC\Expression;

/**
 * @author matthieu.napoli
 * @author hugo.charbonnier
 * @author yoann.croizer
 */
class ExpressionSelectionAlgo extends TextKeySelectionAlgo implements ValueInterface
{
    /**
     * @var string
     */
    protected $expression;

    /**
     * {@inheritdoc}
     */
    public function execute(InputSet $inputSet)
    {
        $this->inputSet = $inputSet;

        // Construit l'arbre
        $tecExpression = new Expression($this->expression, Expression::TYPE_SELECT);

        $executionSelect = new Select($tecExpression);
        $results = $executionSelect->executeExpression($this);

        if (empty($results)) {
            throw new CalculationException('No result from the selection algorithm named ' . $this->ref);
        }

        // Renvoie le premier résultat (normalement il ne devrait y'en avoir qu'un seul)
        return reset($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForExecution($ref)
    {
        try {
            // Si l'opérande est le ref d'un algo, alors on renvoie le résultat de cet algo
            $algo = $this->getSet()->getAlgoByRef($ref);
            if ($algo instanceof TextKeySelectionAlgo) {
                return $algo->execute($this->inputSet);
            }
        } catch (Core_Exception_NotFound $e) {
        }

        // Sinon on renvoie le ref
        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function checkConfig()
    {
        $errors = parent::checkConfig();

        $tecExpression = new Expression($this->expression, Expression::TYPE_SELECT);
        $executionSelect = new Select($tecExpression);

        return array_merge($errors, $executionSelect->getErrors($this));
    }

    /**
     * {@inheritdoc}
     */
    public function checkValueForExecution($ref)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        $tecExpression = new Expression($this->expression, Expression::TYPE_SELECT);
        return $tecExpression->getAsString();
    }

    /**
     * @param string $expression
     * @throws InvalidExpressionException
     */
    public function setExpression($expression)
    {
        $tecExpression = new Expression($expression, Expression::TYPE_SELECT);
        $tecExpression->check();
        // Expression OK
        $this->expression = (string) $expression;
    }
}
