<?php
namespace Magice\Bundle\RestBundle\Responder;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Responder
{
    private $responders;

    public function __construct()
    {
        $this->responders = array();
    }

    /**
     * @param AbstractResponder $responder
     * @param                   $alias
     */
    public function add(AbstractResponder $responder, $alias)
    {
        $this->responders[$alias] = $responder;
    }

    /**
     * @param string $alias
     *
     * @return AbstractResponder
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function get($alias)
    {
        if (array_key_exists($alias, $this->responders)) {
            return $this->responders[$alias];
        }

        throw new ServiceNotFoundException('mg.rest.tag.responder:'.$alias);
    }
}