<?php namespace DiContainer\Interception;

class Invocation implements IInvocation
{
    private $isProceeded = false;

    private $result = null;

    private $methodReflection = null;

    private $decoratedTypeReflection = null;

    private $decoratedInstance = null;

    private $namedParams = array();

    private $params = array();

    public function __construct($decoratedInstance, \ReflectionMethod $methodReflection, array $params, \ReflectionClass $decoratedTypeReflection)
    {
        if (!is_object($decoratedInstance)) {
            throw new \InvalidArgumentException('$decoratedInstance should be an object');
        }

        if (!is_subclass_of($decoratedInstance, $decoratedTypeReflection->getName(), false)) {
            throw new \InvalidArgumentException('$interceptorSelectors should only contain implementations of a \'DiContainer\Interception\IInerceptorSelector\'');
        }

        if (count($methodReflection->getParameters()) < count($params)) {
            throw new \InvalidArgumentException("\$params conains more values than number of parameters that method '{$methodReflection->getName()}' of type '{$decoratedTypeReflection->getName()}' has");
        }

        $namedParams = array();
        $reflectionParameters = $methodReflection->getParameters();
        $range = min(count($params), count($reflectionParameters));
        for ($i = 0; $i < $range; $i++) {
            $namedParams[$reflectionParameters[$i]->getName()] = & $params[$i];
        }

        $this->params = $params;
        $this->namedParams = $namedParams;
        $this->methodReflection = $methodReflection;
        $this->decoratedTypeReflection = $decoratedTypeReflection;
        $this->decoratedInstance = $decoratedInstance;
    }

    public function Proceed()
    {
        $this->result = call_user_func_array(array($this->decoratedInstance, $this->methodReflection->getName()), $this->params);
        $this->isProceeded = true;
    }

    public function GetDecoratedType()
    {
        return $this->decoratedTypeReflection;
    }

    function GetDecoratedInstance()
    {
        return $this->decoratedInstance;
    }

    public function GetMethod()
    {
        return $this->methodReflection;
    }

    public function &GetMethodParameters()
    {
        return $this->namedParams;
    }

    public function &GetResult()
    {
        if (!$this->isProceeded) {
            throw new \Exception("Method GetResult() called on non-poceeded invocation");
        }

        return $this->result;
    }
}