<?php

class Response_Format {

  protected $_responses = [
    'html' => null,
    'json' => null,
    'xml'  => null,
    'txt'  => null,
  ];

  public function __call($method_name, $args)
  {
    if ($args === NULL || ! isset($args[0]))
    {
      if (is_callable($this->_responses[$method_name]))
      {
        $this->_responses[$method_name]();
      }
      return;
    }
    $this->_responses[$method_name] = $args[0];
  }

}