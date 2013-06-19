<?php

namespace Interfaces
{
    interface IA
    {
    }

    interface IB
    {
    }

    interface IC
    {
    }

    interface ID
    {
    }
}

namespace Implementations
{
    class A implements \Interfaces\IA
    {
        private $b = null;
        private $c = null;

        public function __construct(\Interfaces\IB $b, \Interfaces\IC $c)
        {
            $this->b = $b;
            $this->c = $c;
        }
    }

    class B implements \Interfaces\IB
    {
        private $d = null;

        function __construct(\Interfaces\ID $d)
        {
            $this->d = $d;
        }
    }

    class C implements \Interfaces\IC
    {
    }


    class D implements \Interfaces\ID
    {
    }
}

namespace
{
    require_once 'DiContainer/Container.php';

    $container = new DiContainer\Container();
    $container->RegisterTypeMappingRule('~^Interfaces\\\I(.+)~', 'Implementations\\\${1}');
    $instance = $container->Resolve('Interfaces\IA');

    print_r($instance);
}
