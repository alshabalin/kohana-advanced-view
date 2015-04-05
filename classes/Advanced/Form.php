<?php

class Advanced_Form extends Kohana_Form {

  protected static $model      = NULL;
  protected static $model_name = NULL;
  protected static $model_id   = NULL;

  public static function model($model, array $attributes = NULL)
  {
    static::$model      = $model;
    static::$model_name = $model->object_name();
    static::$model_id   = $model->pk();

    $prefix  = 'edit_';
    $postfix = '_' . static::$model_id;

    if ( ! $model->loaded())
    {
      $prefix  = 'new_';
      $postfix = '';
    }

    if ( ! isset($attributes['class']))
    {
      $attributes['class'] = $prefix . static::$model_name;
    }

    if ( ! isset($attributes['id']))
    {
      $attributes['id'] = $prefix . static::$model_name . $postfix;
    }

    if (isset($attributes['url']))
    {
      $url = $attributes['url'];
    }
    else
    {
      $url = Route::url_for($model);
    }

    return Form::open($url, $attributes);
  }

  public static function input($name, $value = NULL, array $attributes = NULL)
  {
    if (static::$model)
    {
      if (isset(static::$model->table_columns()[$name]))
      {
        switch (Arr::get($attributes, 'type'))
        {
          case 'checkbox':
          case 'radio':
            $attributes['checked'] = (static::$model->{$name} == $value);
            break;

          case '':
          case 'text':
          case 'hidden':
          case 'email':
          case 'url':
          case 'search':
          case 'date':
          case 'datetime':
          case 'time':
          case 'month':
          case 'week':
          case 'color':
          case 'number':
          case 'range':
            $value = static::$model->{$name};
            break;
        }

        $attributes['id'] = static::$model_name . '_' . URL::title($name);
        $name             = static::$model_name . '[' . $name . ']';
      }
    }
    return parent::input($name, $value, $attributes);
  }

  public static function textarea($name, $body = '', array $attributes = NULL, $double_encode = TRUE)
  {
    if (static::$model)
    {
      if (isset(static::$model->table_columns()[$name]))
      {
        $body             = View::preserve(static::$model->{$name});
        $attributes['id'] = static::$model_name . '_' . URL::title($name);
        $name             = static::$model_name . '[' . $name . ']';
      }
    }
    return parent::textarea($name, $body, $attributes, $double_encode);
  }

  public static function select($name, array $options = NULL, $selected = NULL, array $attributes = NULL)
  {
    if (static::$model)
    {
      if (isset(static::$model->table_columns()[$name]))
      {
        $selected         = static::$model->{$name};
        $attributes['id'] = static::$model_name . '_' . URL::title($name);
        $name             = static::$model_name . '[' . $name . ']';
      }
    }
    return parent::select($name, $options, $selected, $attributes);
  }


}
