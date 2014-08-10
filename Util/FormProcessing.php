<?php
namespace Magice\Bundle\RestBundle\Util;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class FormProcessing
{
    /**
     * @var Form
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var object
     */
    protected $classData;

    public function init(Request $request, Form $form, $classData)
    {
        $this->request   = $request;
        $this->form      = $form;
        $this->classData = $classData;
    }

    /**
     * Process data value to submit
     *
     * @param string $name
     * @param array  $submitData
     *
     * @return mixed
     */
    public function process($name, array $submitData)
    {
        return $submitData[$name];
    }

    /**
     * Prepare your data before send to submit form
     *
     * @param array $submitData
     *
     * @return array
     */
    public function beforeSubmit(array $submitData)
    {
        return $submitData;
    }
}