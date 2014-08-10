<?php
namespace Magice\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ResponderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('mg.rest.responder')) {
            return;
        }

        $definition = $container->getDefinition(
            'mg.rest.responder'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'mg.rest.tag.responder'
        );

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'add',
                    array(new Reference($id), $attributes["alias"])
                );
            }
        }
    }
}