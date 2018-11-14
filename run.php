#!/usr/bin/php
<?php

class AssertionFailed extends Exception {}

class MockMethod {

  private $return;

  public function return($value) {
    $this->return = $value;
  }

  /**
   * Probably better not to make this method public.
   */
  public function getValue() {
    return $this->return;
  }
}

class Mock {

  private $obj;
  private $mockedMethods = [];

  public function __construct($obj) {
    $this->object = $obj;
  }

  public function __call($name, $args) {
    if (isset($this->mockedMethods[$name])) {
      return $this->mockedMethods[$name]->getValue();
    }

    $refl = new ReflectionMethod($this->object, $name);
    if (!$refl->isPublic()) {
      $refl->setAccessible(true);
    }
    return $refl->invokeArgs($this->object, $args);
  }

  public function on($method) {
    $this->mockedMethods[$method] = new MockMethod;

    return $this->mockedMethods[$method];
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

class SomeModel {
  public function getName() {
    return "Vilnius PHP";
  }
}

class SomeService {
  public $model;

  public function callModel() {
    return $this->model->getName();
  }

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

  public function testCallModel() {
    $mock = $this->mock(new SomeModel);
    $mock
      ->on('getName')
      ->return("Kaunas PHP");

    $service = new SomeService;
    $service->model = $mock;
    $this->assertEqual("Kaunas PHP", $service->callModel());
  }
}


class RunPHPRunner {
  public function run($obj) {
    $this->log("Running tests...");
    $this->log(sprintf("%s:", get_class($obj)));
    xdebug_start_code_coverage(
      XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    $methods = $this->getTestMethods($obj);
    foreach ($methods as $method) {
      try {
        $obj->$method();
        $this->log(sprintf("[+] %s", $method));
      } catch (AssertionFailed $e) {
        $this->log(sprintf("[-] %s: %s", $method, $e->getMessage()));
      }
    }

    $coverage = xdebug_get_code_coverage();
    xdebug_stop_code_coverage();
    $this->coverage($coverage, $obj);
  }

  private function coverage($coverage, $obj) {
    $refl = new \ReflectionClass($obj);
    $file = $refl->getFileName();

    $covered = count(array_filter(
      $coverage[$file], function($val) { return $val === 1; }));
    $uncovered = count(array_filter(
      $coverage[$file], function($val) { return $val === -1; }));

    $this->log(sprintf("Coverage: %.2f%%\n", 100*$covered/($covered+$uncovered)));
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
