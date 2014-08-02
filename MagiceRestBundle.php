<?php

namespace Magice\Bundle\RestBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Magice\Bundle\RestBundle\DependencyInjection\Compiler;

class MagiceRestBundle extends Bundle
{
    public function build(ContainerBuilder $bd)
    {
        $bd->addCompilerPass(new Compiler\FosConfigPass());
        $bd->addCompilerPass(new Compiler\ResponderPass());
    }
}
