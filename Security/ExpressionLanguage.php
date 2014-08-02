<?php
namespace Magice\Bundle\RestBundle\Security;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register(
            'is_granted',
            function ($attributes, $object = null) {
                return '$security_context->isGranted($attributes, $object)';
            },
            function (array $variables, $attributes, $object = null) {
                return $variables['security_context']->isGranted($attributes, $object);
            }
        );
    }

    /**
     * Evaluate an expression.
     *
     * @param Expression|string $expression The expression to compile
     * @param array             $values     An array of values
     *
     * @return string The result of the evaluation of the expression
     */
    public static function evaluation($expression, $values = array())
    {
        return (new self())->evaluate($expression, $values);
    }
}