<?php
namespace Magice\Bundle\RestBundle\Responder;

use FOS\RestBundle\View\View;

class Sencha extends AbstractResponder
{
    public $message = null;
    public $code = 200;
    public $success = true;

    public function setView(View $view)
    {
        $this->data = $view->getData();
        $this->code = $view->getStatusCode();

        $view->setData($this);
    }
}