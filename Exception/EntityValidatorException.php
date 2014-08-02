<?php
namespace Magice\Bundle\RestBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class EntityValidatorException extends \InvalidArgumentException
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    public function setErrors(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getErrors()
    {
        return $this->errors;
    }
}