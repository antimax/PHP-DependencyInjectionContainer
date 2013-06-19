<?php

interface IA
{
}

class A implements IA
{
    private $b = null;

    public function __construct(B $b)
    {
        $this->b = $b;
    }
}

class B
{
    private $c = null;

    public function __construct(C $c)
    {
        $this->c = $c;
    }
}

class  C
{
}

require_once 'DiContainer/Container.php';

$container = new DiContainer\Container();
$container->RegisterType('IA', 'A');
$instance = $container->Resolve('IA');

print_r($instance);
