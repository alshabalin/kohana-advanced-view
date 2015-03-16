<?php

class Advanced_JsonBuileder {

  protected $collection = NULL;

  public function collection($collection, Closure $closure)
  {
    $this->collection = [];

    foreach ($collection as $item)
    {
      $json = new JsonBuilder;
      $closure($item, $json);
      $this->collection[] = $json->as_array();
    }

    return $this;
  }

  protected $json = [];

  public function extract($object, $fields = NULL)
  {
    if (is_object($object) and method_exists($object, 'as_array'))
    {
      $object = $object->as_array();
    }

    if ($fields === NULL)
    {
      $this->json = $object + $this->json;
    }
    else
    {
      if (is_string($fields))
      {
        $fields = array_slice(func_get_args(), 1);
      }

      $this->json = Arr::extract($object, $fields) + $this->json;
    }

    return $this;
  }

  public function __get($key)
  {
    return $this->json[$key];
  }

  public function __set($key, $value)
  {
    $this->json[$key] = $value;
  }

  public function __isset($key)
  {
    return isset($this->json[$key]);
  }

  public function __unset($key)
  {
    unset($this->json[$key]);
  }

  public function __toString()
  {
    return $this->render();
  }

  public function send()
  {
    echo $this->render();
  }


  public function set($key, $value = NULL)
  {
    $this->json[$key] = $value;
    return $this;
  }

  public function as_array()
  {
    return $this->json;
  }


  public function render()
  {
    if ($this->collection === NULL)
    {
      return json_encode($this->json, JSON_PP);
    }
    else
    {
      return json_encode($this->collection, JSON_PP);
    }
  }

}
