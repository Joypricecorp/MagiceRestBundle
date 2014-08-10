<?php
namespace Magice\Bundle\RestBundle\Responder;

use FOS\RestBundle\View\View;

abstract class AbstractResponder
{
    public $data;

    public function setView(View $view)
    {
        $this->data = $view->getData();

        $view->setData($this);
    }
}