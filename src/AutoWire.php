<?php

declare(strict_types=1);

namespace PrimativeAutoWirer;


use PrimativeAutoWirer\Exceptions\AutowireException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class AutoWire
{
    /**
     * @throws AutowireException
     * @throws ReflectionException
     */
    public function autoWire(string $class): ?object
    {
        $reflection = $this->getReflection($class);
        $reflectionConstructor = $reflection->getConstructor();

        // If the class doesn't have a constructor we can just instantiate it right away
        if (!$reflectionConstructor) {
            return $reflection->newInstance();
        }

        /**
         * @var ReflectionParameter[] $constructorParams
         */
        $constructorParams = [];

        // Iterate through any constructor parameters
        foreach ($reflectionConstructor->getParameters() as $reflectionConstructorParam) {
            // We can autowire class dependencies by looking at their types
            $paramTypeReflection = $reflectionConstructorParam->getType();

            // If the constructor param has no type, we can't autowire it
            if (!$paramTypeReflection) {
                throw new AutowireException("Class '$class' constructor param '" .
                    $reflectionConstructorParam->getName() .
                    "' type - unable to autowire");
            }
            // Create a reflection of the dependency
            $paramReflection = $this->getReflection($paramTypeReflection->getName());
            // Grab the dependencies constructor
            $paramReflectionConstructor = $paramReflection->getConstructor();

            // If the dependency has a constructor run it through autoWire()
            // Otherwise create an instance directly
            $constructorParams[] = $paramReflectionConstructor ? $this->autoWire($paramTypeReflection->getName()) : $paramReflection->newInstance();
        }

        return $reflection->newInstanceArgs($constructorParams);
    }

    /**
     * Helper to create a ReflectionClass
     *
     * Makes sure the target class is able to be instantiated otherwise throws an AutowireException
     *
     * @param string $class
     *
     * @return ReflectionClass
     *
     * @throws AutowireException
     * @throws ReflectionException
     */
    private function getReflection(string $class): ReflectionClass
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new AutowireException("Class $class cannot be instantiated");
        }

        return $reflection;
    }
}