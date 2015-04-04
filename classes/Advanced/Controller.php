<?php

class Advanced_Controller extends Kohana_Controller {

  use Advanced_Response_Transport;

  protected $_layout = NULL;

  protected $_default_layout = 'application';

  public $format = NULL;

  public function render($template = NULL, array $data = NULL)
  {
    if (is_array($template))
    {
      if (isset($template['json']))
      {
        $object = $template['json'];
        $this->response->headers('Content-Type', 'application/json; charset=' . Kohana::$charset);
        if (isset($template['status']))
        {
          $this->response->status($template['status']);
        }
        $this->response->body(json_encode($object), JSON_UU);
        return;
      }
      else if (isset($template['xml']))
      {
        $object = $template['xml'];
        $this->response->headers('Content-Type', 'text/xml; charset=' . Kohana::$charset);
        if (isset($template['status']))
        {
          $this->response->status($template['status']);
        }
        return;
      }
      else if (isset($template['body']))
      {
        $this->response->body($template['body']);
        return;
      }
      else if (isset($template['format']))
      {
        $format = $template['format'];
        $mime   = File::mime_by_ext($format) ?: 'text/html';
        $this->response->headers('Content-Type', $mime . '; charset=' . Kohana::$charset);
      }

      if ($this->_layout && ! isset($template['layout']))
      {
        $template['layout'] = $this->_layout;
      }
    }
    else
    {
      if ($this->_layout)
      {
        $this->response->view->layout($this->_layout);
      }
    }

    $this->response->body($this->response->view->render($template, $data));
  }

  public function redirect_to($url, $flash = NULL)
  {
    if ($url instanceof ORM)
    {
      $url = Route::url_for($url);
    }
    if ($flash !== NULL)
    {
      Flash::message($flash);
    }
    $this->redirect($url);
  }

  public function redirect_back($flash = NULL)
  {
    $this->redirect_to(getenv('HTTP_REFERER'), $flash);
  }


  protected $_before_action = NULL;

  protected $_after_action = NULL;


  public function before()
  {
    $directory  = $this->request->directory();
    $controller = $this->request->controller();
    $action     = $this->request->action();
    $format     = $this->_detect_response_format();
    $mime       = File::mime_by_ext($format) ?: 'text/html';

    $this->response->view([
      'basedir'   => $controller,
      'directory' => $directory,
      'action'    => $action,
      'format'    => $format,
    ]);

    $this->response->view->set([
      'basedir'    => $controller,
      'directory'  => $directory,
      'controller' => $controller,
      'action'     => $action,
      'format'     => $format,
      'request'    => $this->request,
      'method'     => $this->request->method(),
      'secure'     => $this->request->secure(),
      'route'      => $this->request->route(),
      'route_name' => Route::name($this->request->route()),
      'params'     => $this->request->param(),
      'query'      => $this->request->query(),
    ]);


    $this->format = $format;

    if ($this->_layout === NULL)
    {
      $layout = strtolower($controller);

      if ($this->response->view->viewfile_exists($layout, 'layouts'))
      {
        $this->_layout = $layout;
      }
      else if ($this->response->view->viewfile_exists($this->_default_layout, 'layouts'))
      {
        $this->_layout = $this->_default_layout;
      }
    }

    $this->response->headers('Content-Type', $mime . '; charset=' . Kohana::$charset);

    if ($this->_layout)
    {
      View::layout($this->_layout);
    }

    $this->_before_action && $this->_call_method_for_action($this->_before_action);
  }

  public function after()
  {
    $this->_after_action && $this->_call_method_for_action($this->_after_action);
  }




  protected function _call_method_for_action($options = NULL)
  {
    if ($options !== NULL)
    {
      $options = (array)$options;
      if (isset($options[0]) && method_exists($this, $options[0]))
      {
        if ( ! isset($options[1]) || in_array($this->request->action(), (array)$options[1]))
        {
          $this->{$options[0]}();
        }
      }
    }
  }

  protected function _detect_response_format()
  {
    if ($format = $this->request->param('format'))
    {
      return $format;
    }
    else if ($format = $this->request->query('format'))
    {
      return $format;
    }
    // else if ($this->request->accept_type('application/json') >= 1)
    // {
    //   return 'json';
    // }
    // else if ($this->request->accept_type('application/xml') >= 1 || $this->request->accept_type('text/xml') >= 1)
    // {
    //   return 'xml';
    // }
    else if ($this->request->requested_with())
    {
      return 'json';
    }
    else
    {
      return 'html';
    }
  }

  protected function respond_to(Closure $closure)
  {
    $format = new Response_Format;
    $closure($format);

    call_user_func([$format, $this->format]);
  }


}
