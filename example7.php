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
    public $b = null;
    public $c = null;

    public function __construct(IB $b, IC $c)
    {
        $this->b = $b;
        $this->c = $c;
    }
}

class B implements IB
{
    public $d = null;

    public function __construct(ID $d)
    {
        $this->d = $d;
    }
}

class C implements IC
{
    public $d = null;

    public function __construct(ID $d)
    {
        $this->d = $d;
    }
}

class D implements ID
{
}


$container = new DiContainer\Container();
$container->RegisterType('IA', 'A')
    ->RegisterType('IB', 'B')
    ->RegisterType('IC', 'C')
    ->RegisterType('ID', 'D', true);

$instance = $container->Resolve('IA');
print_r($instance);
print $instance->b->d === $instance->c->d ? 'TRUE' : 'FALSE';


