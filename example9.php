<?php

require_once 'DiContainer/Container.php';

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

class A implements IA
{
    private $b = null;
    private $c = null;

    public function __construct(IB $b, IC $c)
    {
        $this->b = $b;
        $this->c = $c;
    }
}

class B implements IB
{
    private $d = null;

    function __construct(ID $d)
    {
        $this->d = $d;
    }
}

class C implements IC
{
}


class D implements ID
{
}

class Configurator1 implements DiContainer\IConfigurator
{
    public function Configure(DiContainer\Container $container)
    {
        $container->RegisterType('IA', 'A')
            ->RegisterType('IB', 'B')
            ->RegisterType('IC', 'C');
    }
}

class Configurator2 implements DiContainer\IConfigurator
{
    public function Configure(DiContainer\Container $container)
    {
        $container->RegisterType('ID', 'D');
    }
}

$container = new DiContainer\Container(array(new Configurator1(), new Configurator2()));
$instance = $container->Resolve('IA');

print_r($instance);
