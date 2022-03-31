<?php

declare(strict_types=1);

namespace PrimativeAutoWirer;


use PrimativeAutoWirer\Exceptions\AutowireException;
use ReflectionClass;
use ReflectionException;

class AutoWire
{
    /**
     * @throws AutowireException|ReflectionException
     */
    public function autoWire(string $class): ?object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new AutowireException("Class $class cannot be instantiated");
        }

        $reflectionConstructor = $reflection->getConstructor();

        // If the class doesn't have a constructor we can just instantiate it right away
        if (!$reflectionConstructor) {
            return $reflection->newInstance();
        }

        $reflectionConstructorParams = $reflectionConstructor->getParameters();

        // Instantiate any params the class needs
        $params = [];
        foreach ($reflectionConstructorParams as $reflectionConstructorParam) {
            $type = $reflectionConstructorParam->getType();
            // If the constructor param has no type, we can't autowire it
            if (!$type) throw new AutowireException("Class $class constructor missing param type - unable to autowire");

            $paramReflection = new ReflectionClass($type->getName());
            $paramReflectionConstructor = $paramReflection->getConstructor();

            // If the param has a constructor run it through autoWire()
            // Otherwise create an instance
            $params[] = $paramReflectionConstructor ? $this->autoWire($type->getName()) : $paramReflection->newInstance();
        }

        return $reflection->newInstanceArgs($params);
    }
}