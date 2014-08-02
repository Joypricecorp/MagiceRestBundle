<?php
namespace Magice\Bundle\RestBundle\Annotation;

/**
 * View annotation class.
 * @Annotation
 * @Target({"METHOD","CLASS"})
 */
class View extends \FOS\RestBundle\Controller\Annotations\View
{
    protected $responder;
    protected $headerId;

    /**
     * @param mixed $responder
     */
    public function setResponder($responder)
    {
        $this->responder = $responder;
    }

    /**
     * @return mixed
     */
    public function getResponder()
    {
        return $this->responder;
    }

    /**
     * Pattern: headerId="HEADER-ID", headerId={"HEADER-ID", "id"}
     * @param array|string|null $headerId
     */
    public function setHeaderId($headerId)
    {
        $this->headerId = $headerId;
    }

    public function getHeaderId()
    {
        return $this->headerId;
    }
}