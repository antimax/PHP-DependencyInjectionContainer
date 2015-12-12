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
		// example of 'PRE' behavior
        if ($invocation->GetMethod()->getName() == 'Write') { // if 'Write' method is being called
            /** @var $instance IStream */
            $instance = $invocation->GetDecoratedInstance(); // get original (non-decorated) instance

            $params = &$invocation->GetMethodParameters(); // get call parameters

            if ($instance->Read() == $params['stream']) { // barf, if orignal instance has this value set already
                throw new Exception('You can not set the value which is already set');
            }

            $params['stream'] = 'yyy'; // override call parameter value
        }

        $invocation->Proceed(); // proceed with the call
		
		// insert your 'POST' behavior here
    }
}

class StreamInterceptorSelector implements Interception\IInerceptorSelector
{
    public function GetInterceptors(\ReflectionClass $decoratedType)
    {
        if ($decoratedType->getName() == 'IStream') {
			return array(new StreamInterceptor());
        }

        return array();
    }
}


$container = new DiContainer\Container(array(), array(new StreamInterceptorSelector()));
/** @var $stream IStream */
$stream = $container->RegisterType('IStream', 'Stream')->Resolve('IStream');

$value = 'ssss';
$stream->Write($value);
print_r($stream);
print $value;
