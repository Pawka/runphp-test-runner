#!/usr/bin/php
<?php

function assertEqual($expect, $value, $message = null) {
  if ($expect !== $value) {
    throw new Exception("values are not equal: ". $message);
  }
}

assertEqual(true, false);
