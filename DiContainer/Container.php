<?php namespace DiContainer;

require_once 'IConfigurator.php';
require_once 'Interception/IDecoratorClassComposer.php';
require_once 'Interception/IInterceptor.php';
require_once 'Interception/IInterceptorSelector.php';
require_once 'Interception/IInvocation.php';
require_once 'Interception/IInvocationFactory.php';
require_once 'Interception/DecoratorClassComposer.php';
require_once 'Interception/Invocation.php';
require_once 'Interception/InvocationFactory.php';

use DiContainer\Interception\InvocationFactory;

class Container
{
    const CLASS_REFLECTION_CLASS_NAME = 'ReflectionClass';

    private $instancesMap = array();

    private $typesMap = array();

    private $parameterClosuresMap = array();

    private $singletonLifestyleTypes = array();

    private $interceptorSelectors = array();

    private $invocationFactoryCache = array();

    /**
     * @param IConfigurator[] $configurators
     * @param Interception\IInerceptorSelector[] $interceptorSelectors
     * @throws \InvalidArgumentException
     */
    public function __construct(array $configurators = array(), array $interceptorSelectors = array())
    {
        foreach ($configurators as $configurator) {
            if (!is_subclass_of($configurator, 'DiContainer\IConfigurator', false)) {
                throw new \InvalidArgumentException('$configuratos should only contain implementations of a \'DiContainer\IConfigurator\'');
            }

            $configurator->Configure($this);
        }

        foreach ($interceptorSelectors as $interceptorSelector) {
            if (!is_subclass_of($interceptorSelector, 'DiContainer\Interception\IInerceptorSelector', false)) {
                throw new \InvalidArgumentException('$interceptorSelectors should only contain implementations of a \'DiContainer\Interception\IInerceptorSelector\'');
            }
        }

        $this->interceptorSelectors = $interceptorSelectors;
    }

    public function RegisterParameterValue($parameterName, $parameterValue)
    {
        if (!is_string($parameterName) || empty($parameterName) || $parameterName{0} == '$') {
            throw new \InvalidArgumentException('$parameterName should be non-empty string that does not start with \'$\'');
        }

        $this->RegisterParameterCallback($parameterName, function (\ReflectionClass $classReflection) use ($parameterValue) {
            return $parameterValue;
        });

        return $this;
    }

    public function RegisterParameterCallback($parameterName, $callback)
    {
        if (!is_string($parameterName) || empty($parameterName) || $parameterName{0} == '$') {
            throw new \InvalidArgumentException('$parameterName should be non-empty string that does not start with \'$\'');
        }

        $callbackIsValid = false;
        try {
            $closureReflection = new \ReflectionFunction($callback);
            if ($closureReflection->getNumberOfParameters() == 1
                && !is_null($closureParams = $closureReflection->getParameters())
                && !is_null($paramClassReflection = $closureParams[0]->getClass())
                && $paramClassReflection->getName() == Container::CLASS_REFLECTION_CLASS_NAME
            ) {
                $callbackIsValid = true;
            }
        } catch (\ReflectionException $e) {
        }

        if (!$callbackIsValid) {
            throw new \InvalidArgumentException('$callback should be closure of a type function(\ReflectionClass $classReflection) {...}');
        }

        $this->parameterClosuresMap[$parameterName] = $callback;

        return $this;
    }

    public function RegisterInstance($abstractTypeName, $implementationInstance)
    {
        if (!is_string($abstractTypeName)
            || empty($abstractTypeName)
            || is_null($r = Container::CreateClassReflectionByTypeName($abstractTypeName))
            || !Container::RepresentsAbstractClass($r)
        ) {
            throw new \InvalidArgumentException('$abstractType should be non-empty string containing the full name of a declared abstract class or interface');
        }

        if (is_null($implementationInstance)
            || !is_object($implementationInstance)
            || !is_subclass_of($implementationInstance, $abstractTypeName, false)
        ) {
            throw new \InvalidArgumentException('$implementationInstance should be an instnace of an object implementing $abstractTypeName');
        }

        if (array_key_exists($abstractTypeName, $this->typesMap)) {
            throw new \InvalidArgumentException("Abstract type '{$abstractTypeName}' is already registered with container");
        }

        $this->instancesMap[$abstractTypeName] = $implementationInstance;

        return $this;
    }

    public function RegisterType($abstractTypeName, $implementationTypeName, $singletonLifestyle = false)
    {
        if (!is_string($abstractTypeName)
            || empty($abstractTypeName)
            || is_null($r = Container::CreateClassReflectionByTypeName($abstractTypeName))
            || !Container::RepresentsAbstractClass($r)
        ) {
            throw new \InvalidArgumentException('$abstractType should be non-empty string containing the full name of a declared abstract class or interface');
        }

        if (!is_string($implementationTypeName)
            || empty($implementationTypeName)
            || is_null($r = Container::CreateClassReflectionByTypeName($implementationTypeName))
            || !Container::RepresentsInstantiableClass($r)
            || !Container::ClassReferenceIsSubclassOf($r, $abstractTypeName)
        ) {
            throw new \InvalidArgumentException('$implementationTypeName should be non-empty string containing the full name of a declared class implementing $abstractTypeName');
        }

        if (array_key_exists($abstractTypeName, $this->instancesMap)) {
            throw new \InvalidArgumentException("Abstract type '{$abstractTypeName}' is already registered with container");
        }

        // saving implementation type class reflection
        $this->typesMap[$abstractTypeName] = $r;

        if ($singletonLifestyle) {
            $this->singletonLifestyleTypes[$abstractTypeName] = true;
        }

        return $this;
    }

    public function RegisterTypeMappingRule($abstractTypeNamePattern, $implementationTypeNameReplacement, $singletonLifestyle = false)
    {
        foreach (array_merge(get_declared_interfaces(), get_declared_classes()) as $abstractTypeName) {
            $implementationTypeName = preg_replace($abstractTypeNamePattern, $implementationTypeNameReplacement, $abstractTypeName);
            if (!is_null($implementationTypeName) && $implementationTypeName != $abstractTypeName) {
                $this->RegisterType($abstractTypeName, $implementationTypeName, $singletonLifestyle);
            }
        }

        return $this;
    }

    public function Resolve($typeName)
    {
        return $this->RecursiveResolve($typeName, array());
    }

    private function SetupInterception(\ReflectionClass $decoratedAbstractTypeReflection, $decoratedInstance)
    {
        foreach ($this->interceptorSelectors as $interceptorSelector) {
            foreach ($interceptorSelector->GetInterceptors($decoratedAbstractTypeReflection) as $interceptor) {
                $decoratorClassName = $decoratedAbstractTypeReflection->getName() . 'InterceptionDecorator';

                if (!class_exists($decoratorClassName, false)) {
                    $composer = new Interception\DecoratorClassComposer();
                    eval($composer->Compose($decoratedAbstractTypeReflection, $decoratorClassName));
                }

                $decoratorReflection = Container::CreateClassReflectionByTypeName($decoratorClassName);
                if (is_null($decoratorReflection)) {
                    throw new \Exception("Decorator class '$decoratorClassName' does not exist");
                }

                if (!array_key_exists($decoratorClassName, $this->invocationFactoryCache)) {
                    $this->invocationFactoryCache[$decoratorClassName] = new InvocationFactory($decoratedAbstractTypeReflection);
                }

                $decoratedInstance = $decoratorReflection->newInstance($decoratedInstance, $interceptor, $this->invocationFactoryCache[$decoratorClassName]);
            }
        }

        return $decoratedInstance;
    }

    private function RecursiveResolve($typeName, array $dependents)
    {
        if (!is_string($typeName) || empty($typeName)) {
            throw new \InvalidArgumentException('$typeName should be non-empty string');
        }

        // 0. Checking for cyclic dependecies
        if (in_array($typeName, $dependents)) {
            throw new \InvalidArgumentException("Cyclic dependency on the type '{$typeName}' detected");
        }

        // 1. instances
        if (array_key_exists($typeName, $this->instancesMap)) {
            return $this->instancesMap[$typeName];
        }

        // 2. types
        /** @var $classReflection \ReflectionClass */
        $classReflection = null;

        if (array_key_exists($typeName, $this->typesMap)) {
            $classReflection = $this->typesMap[$typeName];
        } else {
            $classReflection = Container::CreateClassReflectionForDeclaredInstantiableType($typeName);
        }

        if ($classReflection != null) {
            $constructor = $classReflection->getConstructor();

            // class does not have explicitly defined constructor or constructor does not have parameters
            if (is_null($constructor) || $constructor->getNumberOfParameters() < 1) {
                $instance = $classReflection->newInstance();
            } else {
                $constructorArguments = array();
                foreach ($constructor->getParameters() as $param) {
                    if ($param->getClass()) {
                        $constructorArguments[] = $this->RecursiveResolve($param->getClass()->getName(), array_merge($dependents, array($typeName)));
                    } else {
                        // This is scalar parameter.
                        // Might we have it registered with container?
                        if (array_key_exists($param->getName(), $this->parameterClosuresMap)) {
                            $constructorArguments[] = $this->parameterClosuresMap[$param->getName()]($classReflection);
                            // Might parameter have default value?
                            // But we need to consider the fact that PHP cannot determine default value for internal functions.
                        } elseif (!$constructor->isInternal() && $param->isOptional()) {
                            $constructorArguments[] = $param->getDefaultValue();
                        } else {
                            throw new \UnexpectedValueException("Scalar parameter '{$param->getName()}' of the '{$classReflection->getName()}' class constructor is not registered with the container");
                        }
                    }
                }

                $instance = $classReflection->newInstanceArgs($constructorArguments);
            }

            $instance = $this->SetupInterception(Container::CreateClassReflectionByTypeName($typeName), $instance);

            if (array_key_exists($typeName, $this->singletonLifestyleTypes)) {
                // singleton lifestyle - caching instance
                $this->instancesMap[$typeName] = $instance;
            } else {
                // caching type reflection object
                $this->typesMap[$typeName] = $classReflection;
            }

            return $instance;
        }

        throw new \InvalidArgumentException("Can not resolve the abstract class '$typeName' into an implementation");
    }

    private static function CreateClassReflectionByTypeName($typeName)
    {
        try {
            return new \ReflectionClass($typeName);
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    private static function CreateClassReflectionForDeclaredInstantiableType($typeName)
    {
        $reflection = Container::CreateClassReflectionByTypeName($typeName);

        return !is_null($reflection) && Container::RepresentsInstantiableClass($reflection) ? $reflection : null;
    }

    private static function RepresentsAbstractClass(\ReflectionClass $classReflection)
    {
        return $classReflection->isAbstract() || $classReflection->isInterface();
    }

    private static function RepresentsInstantiableClass(\ReflectionClass $classReflection)
    {
        return !Container::RepresentsAbstractClass($classReflection) && $classReflection->isInstantiable();
    }

    private static function ClassReferenceIsSubclassOf(\ReflectionClass $classReflection, $supertype)
    {
        return $classReflection->isSubclassOf($supertype);
    }
}