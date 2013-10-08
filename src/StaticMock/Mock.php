<?php

namespace StaticMock;


use MethodReplacer\ClassManager;
use phpDocumentor\Reflection\Exception;
use StaticMock\Exception\AssertionFailedException;
use StaticMock\Recorder\Counter;

class Mock {

    private $class_name;

    private $method_name;

    private $fake;

    private $shouldCalledCount;

    private $shouldPassedArgs;

    public function __construct($class_name)
    {
        $this->fake = new Fake();
        $this->class_name = $class_name;
    }

    public function __destruct()
    {
        try {
            $this->assertCalledCount();
            $this->assertPassedArguments();
        } finally {
            ClassManager::getInstance()->deregister($this->class_name, $this->method_name);
            Counter::getInstance()->clear($this->fake->hash());
        }
    }

    private static function createAssertionFaileMessage($expected, $actual)
    {
        return "Failed asserting that $actual matches expected $expected.";
    }

    private static function mkString(array $a) {
        return '(' . implode(', ', $a) . ')';
    }

    public function method($method_name)
    {
        $this->method_name = $method_name;
        $impl = $this->fake->getImplementation(null);
        ClassManager::getInstance()->register($this->class_name, $this->method_name, $impl);
        return $this;
    }

    public function andReturn($return_value)
    {
        $impl = $this->fake->getImplementation($return_value);
        ClassManager::getInstance()->register($this->class_name, $this->method_name, $impl);
    }

    public function times($count)
    {
        $this->shouldCalledCount = $count;
        return $this;
    }

    public function shouldReceive()
    {
        $this->shouldPassedArgs = func_get_args();
        return $this;
    }

    public function getCalledCount()
    {
        return $this->fake->count();
    }

    public function getPassedArguments()
    {
        return $this->fake->args();
    }

    private function assertCalledCount()
    {
        if ($this->shouldCalledCount) {
            if ($this->shouldCalledCount !== $this->getCalledCount()) {
                throw new AssertionFailedException(
                    self::createAssertionFaileMessage($this->getCalledCount(), $this->shouldCalledCount)
                );
            }
        }
    }

    private function assertPassedArguments()
    {
        if ($this->shouldPassedArgs) {
            if ($this->shouldPassedArgs !== $this->getPassedArguments()) {
                throw new AssertionFailedException(
                    self::createAssertionFaileMessage(
                        self::mkString($this->shouldPassedArgs),
                        self::mkString($this->getPassedArguments())
                    )
                );
            }
        }
    }
}
