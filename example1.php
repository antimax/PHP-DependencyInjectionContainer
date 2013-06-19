<?php

interface IA
{
}

interface IB
{
}

class A implements IA
{
    private $dependency = null;

    public function __construct(IB $dep)
    {
        $this->dependency = $dep;
    }
}

class  B implements IB
{
}

require_once 'DiContainer/Container.php';

$container = new DiContainer\Container();
$container->RegisterInstance('IB', new B());
$container->RegisterType('IA', 'A');

$instance = $container->Resolve('IA');
print_r($instance);
