<?php namespace DiContainer\Interception;


class DecoratorClassComposer implements IDecoratorClassComposer
{
    public function Compose(\ReflectionClass $decoratedTypeReflection, $decoratorClassName)
    {
        if (!is_string($decoratorClassName) || empty($decoratorClassName)) {
            throw new \InvalidArgumentException('$decoratorClassName should be non-empty string');
        }

        $composedMethods = array();
        foreach ($decoratedTypeReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $composedMethods[] = $this->ComposeMethod($method);
        }

        $methods = implode("\n\n", $composedMethods);

        return <<<EOC
class {$decoratorClassName} implements {$decoratedTypeReflection->getName()}
{
    private \$decoratedInstance = null;

    private \$interceptor = null;

    private \$invocationFactory = null;

    public function __construct(\$decoratedInstance, \DiContainer\Interception\IInterceptor \$interceptor, \DiContainer\Interception\IInvocationFactory \$invocationFactory)
    {
        if (!is_object(\$decoratedInstance)) {
            throw new \InvalidArgumentException('\$instance should be an instance of an object');
        }

        \$this->decoratedInstance = \$decoratedInstance;
        \$this->interceptor = \$interceptor;
        \$this->invocationFactory = \$invocationFactory;
    }

{$methods}

    private function ExecuteInterceptor(\$methodName, \$params)
    {
        \$invocation = \$this->invocationFactory->Create(\$this->decoratedInstance, \$methodName, \$params);
        \$this->interceptor->Intercept(\$invocation);
        return \$invocation->GetResult();
    }
}
EOC;
    }

    private function ComposeMethod(\ReflectionMethod $methodReflection)
    {
        $paramsForMethodSignature = array();
        $paramsForInterceptorExecution = array();

        foreach ($methodReflection->getParameters() as $param) {
            $paramDefinition = "\${$param->getName()}";

            if ($param->isPassedByReference()) {
                $paramDefinition = '&' . $paramDefinition;
            }
            $paramsForInterceptorExecution[] = $paramDefinition;

            if ($param->isDefaultValueAvailable()) {
                $default_value = $param->getDefaultValue();
                if (is_null($default_value)) {
                    $paramDefinition .= ' = null';
                } elseif (is_numeric($default_value)) {
                    $paramDefinition .= " = {$default_value}";
                } elseif (is_bool($default_value)) {
                    $paramDefinition .= ' = ' . ($default_value ? 'true' : 'false');
                } else {
                    $paramDefinition .= " = '{$default_value}'";
                }
            }

            if ($param->isArray()) {
                $paramDefinition = 'array ' . $paramDefinition;
            } elseif ($paramTypeReflection = $param->getClass()) {
                $paramDefinition = $paramTypeReflection->getName() . ' ' . $paramDefinition;
            }
            $paramsForMethodSignature[] = $paramDefinition;
        }

        $methodSignature = implode(', ', $paramsForInterceptorExecution);
        $interceptionExecution = implode(', ', $paramsForMethodSignature);

        return <<<EOM
    public function {$methodReflection->getName()}({$methodSignature})
    {
        return \$this->ExecuteInterceptor(__FUNCTION__, array($interceptionExecution));
    }
EOM;
    }
}