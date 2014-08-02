<?php
namespace Magice\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ViewResponseListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $bd)
    {
        $class = 'Magice\Bundle\RestBundle\EventListener\ViewResponseListener';
        $bd->getDefinition('fos_rest.view_response_listener')->setClass($class);
    }
}