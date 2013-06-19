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

    public function __construct(IB $b)
    {
        $this->b = $b;
    }
}

class B implements IB
{
    public $c = null;

    public function __construct(IC $c)
    {
        $this->c = $c;
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
    public $a = null;

    public function __construct(IA $a)
    {
        $this->a = $a;
    }
}


$container = new DiContainer\Container();
$container->RegisterType('IA', 'A')
    ->RegisterType('IB', 'B')
    ->RegisterType('IC', 'C')
    ->RegisterType('ID', 'D');

$instance = $container->Resolve('IA');


