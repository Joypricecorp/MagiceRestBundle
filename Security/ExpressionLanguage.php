<?php
namespace Magice\Bundle\RestBundle\Security;

use Sensio\Bundle\FrameworkExtraBundle\Security\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();
    }

    /**
     * Evaluate an expression.
     *
     * @param \Symfony\Component\ExpressionLanguage\Expression|string $expression The expression to compile
     * @param array             $values     An array of values
     *
     * @return string The result of the evaluation of the expression
     */
    public static function evaluation($expression, $values = array())
    {
        return (new self())->evaluate($expression, $values);
    }
}