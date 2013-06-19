<?php namespace DiContainer\Interception;

class InvocationFactory implements IInvocationFactory
{
    private $decoratedTypeReflection = null;

    public function __construct(\ReflectionClass $decoratedTypeReflection)
    {
        $this->decoratedTypeReflection = $decoratedTypeReflection;
    }

    public function Create($decoratedInstance, $methodName, array $params)
    {
        if (!is_object($decoratedInstance)) {
            throw new \InvalidArgumentException('$decoratedInstance should be an object');
        }

        if (!is_string($methodName) || empty($methodName)) {
            throw new \InvalidArgumentException('$methodName should be an non-empty string');
        }

        // We are decorating public interface
        /** @var \ReflectionMethod[] $methods */
        $methods = $this->decoratedTypeReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getName() == $methodName) {
                return new Invocation($decoratedInstance, $method, $params, $this->decoratedTypeReflection);
            }
        }

        throw new \InvalidArgumentException("\$methodName contains invalid method name. Such method does not exist in public interface of type {$this->decoratedTypeReflection->getName()}");
    }
}
