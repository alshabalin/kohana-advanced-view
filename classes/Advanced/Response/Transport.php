<?php

trait Advanced_Response_Transport {

  public function share($key, $value = NULL)
  {
    View::set_global($key, $value);
  }

  public function share_bind($key, & $value)
  {
    View::bind_global($key, $value);
  }

  public function __get($key)
  {
    return $this->response->view->$key;
  }

  public function __set($key, $value)
  {
    $this->response->view->set($key, $value);
  }

  public function __isset($key)
  {
    return isset($this->response->view->$key);
  }

  public function __unset($key)
  {
    unset($this->response->view->$key);
  }


  public function set($key, $value = NULL)
  {
    $this->response->view->set($key, $value);
    return $this;
  }

  public function bind($key, & $value)
  {
    $this->response->view->bind($key, $value);
    return $this;
  }

}
