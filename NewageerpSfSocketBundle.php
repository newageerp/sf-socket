<?php
namespace Newageerp\SfSocket;

use Newageerp\SfSocket\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class NewageerpSfSocketBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new Extension();
    }
}
