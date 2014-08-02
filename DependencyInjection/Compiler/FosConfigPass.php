<?php
namespace Magice\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FosConfigPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $bd)
    {
        // override twig controller exception
        // to not set (but still can set):
        // twig:
        //      exception_controller: xxx
        $bd->getDefinition('twig.controller.exception')
            ->setClass('Magice\Bundle\RestBundle\Controller\ExceptionController')
            ->addMethodCall('setContainer', array(new Reference('service_container')));

        $bd->getDefinition('fos_rest.view.exception_wrapper_handler')
            ->setClass('Magice\Bundle\RestBundle\Util\ExceptionWrapper');

        // override jms/seri
        if ($bd->hasDefinition('jms_serializer.camel_case_naming_strategy')) {
            $bd->getDefinition('jms_serializer.camel_case_naming_strategy')
                ->setClass('JMS\Serializer\Naming\IdenticalPropertyNamingStrategy');
        }

        // viewhandler
        $bd->getDefinition('fos_rest.view_handler')
            ->setClass('Magice\Bundle\RestBundle\Util\ViewHandler')
            ;
    }
}