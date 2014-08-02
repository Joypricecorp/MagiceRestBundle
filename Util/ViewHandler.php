<?php
namespace Magice\Bundle\RestBundle\Util;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ViewHandler extends \FOS\RestBundle\View\ViewHandler
{
    /**
     * Create the Response from the view
     *
     * @param View   $view
     * @param string $location
     * @param string $format
     *
     * @return Response
     */
    public function createRedirectResponse(View $view, $location, $format)
    {
        $content = null;
        if (($view->getStatusCode() == Codes::HTTP_CREATED || $view->getStatusCode() == Codes::HTTP_ACCEPTED) && $view->getData() != null) {
            $response = $this->initResponse($view, $format);
        } else {
            $response = $view->getResponse();
            if ('html' === $format && isset($this->forceRedirects[$format])) {
                $redirect = new RedirectResponse($location);
                $content  = $redirect->getContent();
                $response->setContent($content);
            }
        }

        $code = isset($this->forceRedirects[$format])
            ? $this->forceRedirects[$format] : $this->getStatusCode($view, $content);

        $response->setStatusCode($code);
        $response->headers->set('Location', $location);
        return $response;
    }

    /**
     * Handles creation of a Response using either redirection or the templating/serializer service
     *
     * @param View    $view
     * @param Request $request
     * @param string  $format
     *
     * @return Response
     */
    public function createResponse(View $view, Request $request, $format)
    {
        $route    = $view->getRoute();
        $location = $route
            ? $this->getRouter()->generate($route, (array) $view->getRouteParameters(), true)
            : $view->getLocation();

        if ($location) {
            return $this->createRedirectResponse($view, $location, $format);
        }

        $response = $this->initResponse($view, $format);

        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', $request->getMimeType($format));
        }

        return $response;
    }

    /**
     * Initializes a response object that represents the view and holds the view's status code.
     *
     * @param View   $view
     * @param string $format
     *
     * @return Response
     */
    private function initResponse(View $view, $format)
    {
        $content = null;
        if ($this->isFormatTemplating($format)) {
            $content = $this->renderTemplate($view, $format);
        } elseif ($this->serializeNull || null !== $view->getData()) {
            $data       = $this->getDataFromView($view);
            $serializer = $this->getSerializer($view);
            if ($serializer instanceof SerializerInterface) {
                $context = $this->getSerializationContext($view);
                $content = $serializer->serialize($data, $format, $context);
            } else {
                $content = $serializer->serialize($data, $format);
            }
        }

        $response = $view->getResponse();
        $response->setStatusCode($this->getStatusCode($view, $content));

        if (null !== $content) {
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Gets a response HTTP status code from a View instance
     * By default it will return 200. However if there is a FormInterface stored for
     * the key 'form' in the View's data it will return the failed_validation
     * configuration if the form instance has errors.
     *
     * @param View  $view view instance
     * @param mixed $content
     *
     * @return int HTTP status code
     */
    protected function getStatusCode(View $view, $content = null)
    {
        $form = $this->getFormFromView($view);

        if ($form && $form->isBound() && !$form->isValid()) {
            return $this->failedValidationCode;
        }

        if ($view->getData() instanceof ConstraintViolationListInterface) {
            return $this->failedValidationCode;
        }

        if (200 !== ($code = $view->getStatusCode())) {
            return $code;
        }

        return null !== $content ? Codes::HTTP_OK : $this->emptyContentCode;
    }

    /**
     * Returns the data from a view. If the data is form with errors, it will return it wrapped in an ExceptionWrapper
     *
     * @param View $view
     *
     * @return mixed|null
     */
    private function getDataFromView(View $view)
    {
        if ($view->getData() instanceof ConstraintViolationListInterface) {
            return $this->errorWarp($view->getData());
        }

        $form = $this->getFormFromView($view);

        if (false === $form) {
            return $view->getData();
        }

        if ($form->isValid() || !$form->isSubmitted()) {
            return $form;
        }

        return $this->errorWarp($form);
    }

    private function errorWarp($errors)
    {
        /**
         * @var ExceptionWrapperHandlerInterface $exceptionWrapperHandler
         */
        $exceptionWrapperHandler = $this->container->get('fos_rest.view.exception_wrapper_handler');

        return $exceptionWrapperHandler->wrap(
            array(
                'status_code' => $this->failedValidationCode,
                'message'     => 'Validation Failed',
                'errors'      => $errors
            )
        );
    }
}