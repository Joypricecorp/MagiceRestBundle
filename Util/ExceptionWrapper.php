<?php

namespace Magice\Bundle\RestBundle\Util;

use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Wraps an exception into the FOSRest exception format
 */
class ExceptionWrapper implements ExceptionWrapperHandlerInterface
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed
     */
    private $errors;

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function wrap($data)
    {
        $this->code    = $data['status_code'];
        $this->message = $data['message'];

        if (isset($data['errors'])) {
            $errors = $data['errors'];

            if ($errors instanceof Form) {
                $this->errors = $this->getFormErrors($errors);
            } elseif ($errors instanceof ConstraintViolationListInterface) {
                $this->errors = $this->getConstraintErrors($errors);
            } else {
                $this->errors = $errors;
            }
        }

        return $this;
    }

    public function getConstraintErrors(ConstraintViolationListInterface $constraint)
    {
        $errors = array();

        /**
         * @var ConstraintViolationInterface $const
         */
        foreach ($constraint as $const) {
            $errors[$const->getPropertyPath()][] = $const->getMessage();
        }

        return $errors;
    }

    public function getFormErrors(Form $form)
    {
        $errors = array();

        /**
         * @var Form $child
         */
        foreach ($form as $child) {
            if ($child->isSubmitted() && $child->isValid()) {
                continue;
            }

            $iterator = $child->getErrors(true, true);

            if (0 === count($iterator)) {
                continue;
            }

            foreach ($iterator as $error) {
                $errors[$child->getName()][] = $error;
            }
        }

        return $errors;
    }
}
