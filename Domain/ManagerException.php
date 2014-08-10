<?php
namespace Magice\Bundle\RestBundle\Domain;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ManagerException extends \RuntimeException
{
    private $violations;

    public function setViolations(ConstraintViolationListInterface $violationList)
    {
        $this->violations = $violationList;
    }

    public function getErrors()
    {
        return $this->violations;
    }
}