<?php

class Advanced_Response extends Kohana_Response {

  use Advanced_View_Transport;

  public function __construct(array $config = array())
  {
    // this ugly way to add new status, Kohana shame
    if ( ! isset(Response::$messages[422]))
    {
      Response::$messages[422] = 'Unprocessable Entity';
    }

    parent::__construct($config);
  }

  public $view;

  public function view($options = NULL)
  {
    if ($this->view === NULL || $options !== NULL)
    {
      $this->view = View::factory($options);
    }
    return $this->view;
  }

  public function body($content = NULL)
  {
    if ($content === NULL)
    {
      if ($this->_body === '' && in_array($this->_status, [200, 403, 404]))
      {
        $this->_body = $this->view->render();
      }

      return $this->_body;
    }

    return parent::body($content);
  }

  public function no_content()
  {
    return $this->status(204);
  }



}
