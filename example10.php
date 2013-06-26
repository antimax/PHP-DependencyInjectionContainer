<?php

namespace Interfaces
{
    interface IA
    {
    }
}

namespace Implementations
{
    class A implements \Interfaces\IA
    {
    }
}

namespace
{
    require_once 'DiContainer/Container.php';
    require_once 'DiContainer/IniBasedConfigurator.php';

    interface IA
    {
    }

    interface IB
    {
    }

    class A implements IA
    {
        private $foo;

        function __construct($foo)
        {
            $this->foo = $foo;
        }
    }

    class B implements IB
    {
        private $bar;

        function __construct($bar)
        {
            $this->bar = $bar;
        }
    }

    $container = new DiContainer\Container(array(new DiContainer\IniBasedConfigurator('container.ini')));
    print_r($container->Resolve('Interfaces\IA'));
    print_r($container->Resolve('IA'));
    print_r($container->Resolve('IB'));
}