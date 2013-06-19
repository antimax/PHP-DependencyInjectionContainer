<?php

require_once 'DiContainer/Container.php';

use DiContainer\Interception;

interface IStream
{
    function Read();

    function Write(&$stream);
}

class Stream implements IStream
{
    private $buffer = null;

    public function Read()
    {
        return $this->buffer;
    }

    public function Write(&$stream)
    {
        $this->buffer = $stream;
        $stream = 'xxxx';
    }
}

class StreamInterceptor implements Interception\IInterceptor
{
    public function Intercept(Interception\IInvocation $invocation)
    {
        if ($invocation->GetMethod()->getName() == 'Write') {
            /** @var $instance IStream */
            $instance = $invocation->GetDecoratedInstance();

            $params = & $invocation->GetMethodParameters();

            if ($instance->Read() == $params['stream']) {
                throw new Exception('You can not set the value whict is already set');
            }

            $params['stream'] = 'yyy';
        }

        $invocation->Proceed();
    }
}

class StreamInterceptorSelector implements Interception\IInerceptorSelector
{
    public function GetInterceptors(\ReflectionClass $decoratedType)
    {
        if ($decoratedType->getName() != 'IStream') {
            return array();
        }

        return array(new StreamInterceptor());
    }
}


$container = new DiContainer\Container(array(), array(new StreamInterceptorSelector()));
/** @var $stream IStream */
$stream = $container->RegisterType('IStream', 'Stream')->Resolve('IStream');

$value = 'asss';
$stream->Write($value);
print_r($stream);
print $value;
