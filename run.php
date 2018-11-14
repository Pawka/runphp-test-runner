#!/usr/bin/php
<?php

class AssertionFailed extends Exception {}

function assertEqual($expect, $value, $message = null) {
  if ($expect !== $value) {
    throw new AssertionFailed("values are not equal: ". $message);
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
