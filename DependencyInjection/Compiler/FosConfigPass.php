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
                ->setClass($bd->getParameter('magice.rest.serializer_naming_strategy'));

            $bd->setParameter(
                'jms_serializer.cache_naming_strategy.class',
                $bd->getParameter('magice.rest.serializer_naming_strategy')
            );
        }

        // viewhandler
        $bd->getDefinition('fos_rest.view_handler')
            ->setClass('Magice\Bundle\RestBundle\Util\ViewHandler')
        ;

        // view listener
        $class = 'Magice\Bundle\RestBundle\EventListener\ViewResponseListener';
        $bd->getDefinition('fos_rest.view_response_listener')->setClass($class);
    }
}