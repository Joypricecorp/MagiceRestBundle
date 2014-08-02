<?php

namespace Magice\Bundle\RestBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController as BaseResourceController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ResourceController extends BaseResourceController
{
    public function deleteAction(Request $request)
    {
        $response = parent::deleteAction($request);

        if ($response->getStatusCode() === 204) {
            $response->headers->set('Location', $this->redirectHandler->redirectToIndex()->getTargetUrl());
        }

        return $response;
    }
}
