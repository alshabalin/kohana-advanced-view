<?php

class Advanced_Flash {

  protected static $_message = NULL;

  protected static $_type = NULL;

  public static function get()
  {
    if (static::$_message === NULL)
    {
      static::$_message = Session::instance()->get_once('flash_message');
    }

    Flash::type();

    return static::$_message;
  }

  public static function type()
  {
    if (static::$_type === NULL)
    {
      static::$_type = Session::instance()->get_once('flash_type');
    }

    return static::$_type;
  }


  public static function message($message)
  {
    Session::instance()->set('flash_message', $message);
    Session::instance()->set('flash_type', 'message');
  }

  public static function info($message)
  {
    Session::instance()->set('flash_message', $message);
    Session::instance()->set('flash_type', 'info');
  }

  public static function success($message)
  {
    Session::instance()->set('flash_message', $message);
    Session::instance()->set('flash_type', 'success');
  }

  public static function error($message)
  {
    Session::instance()->set('flash_message', $message);
    Session::instance()->set('flash_type', 'error');
  }

  public static function warning($message)
  {
    Session::instance()->set('flash_message', $message);
    Session::instance()->set('flash_type', 'warning');
  }

}
