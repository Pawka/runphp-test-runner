#!/usr/bin/php
<?php

class AssertionFailed extends Exception {}

class Mock {

  private $obj;

  public function __construct($obj) {
    $this->object = $obj;
  }

  public function __call($name, $args) {
    $refl = new ReflectionMethod($this->object, $name);
    if (!$refl->isPublic()) {
      $refl->setAccessible(true);
    }
    return $refl->invokeArgs($this->object, $args);
  }
}

abstract class RunPHPTestCase {

  protected final function assertEqual($expect, $value, $message = null) {
    if ($expect !== $value) {
      if (!$message) {
        $message = sprintf("expected `%s` got `%s`.",
          var_export($expect, true),
          var_export($value, true));
      }
      throw new AssertionFailed("values are not equal: ". $message);
    }
  }

  protected final function mock($obj) {
    return new Mock($obj);
  }
}

class SomeService {
  public function returnOne() {
    return 1;
  }

  private function returnTwo() {
    return 2;
  }
}

class SomeTestCase extends RunPHPTestCase {
  public function testSomething() {
    $this->assertEqual(true, false);
  }

  public function testSomethingElse() {
    $this->assertEqual(1, 1);
  }

  public function testReturnTwo() {
    $service = $this->mock(new SomeService);
    $this->assertEqual(2, $service->returnTwo());
  }
}


class RunPHPRunner {
  public function run($obj) {
    $this->log("Running tests...");
    $this->log(sprintf("%s:", get_class($obj)));
    $methods = $this->getTestMethods($obj);
    foreach ($methods as $method) {
      try {
        $obj->$method();
        $this->log(sprintf("[+] %s", $method));
      } catch (AssertionFailed $e) {
        $this->log(sprintf("[-] %s: %s", $method, $e->getMessage()));
      }
    }
  }

  private function log($message) {
    echo $message . PHP_EOL;
  }

  private function getTestMethods($obj) {
    $methods = array_filter(
      get_class_methods($obj),
      function($method) {
        return preg_match('/^test[A-Z0-9]/', $method);
      }
    );

    return $methods;
  }
}

$testCase = new SomeTestCase;
$runner = new RunPHPRunner;
$runner->run($testCase);
