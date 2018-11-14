#!/usr/bin/php
<?php

function assertEqual($expect, $value, $message = null) {
  if ($expect !== $value) {
    throw new Exception("values are not equal: ". $message);
  }
}

class SomeTestCase {
  public function testSomething() {
    assertEqual(true, false);
  }

  public function testSomethingElse() {
    assertEqual(1, 1);
  }
}


class RunPHPRunner {
  public function run($obj) {
    $methods = $this->getTestMethods($obj);
    foreach ($methods as $method) {
      $obj->$method();
    }
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
