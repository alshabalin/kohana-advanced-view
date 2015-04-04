<?php

class HTML extends Kohana_HTML {

  public static function attributes(array $attributes = NULL)
  {
    // data-* attrubtes as an array
    if (isset($attributes['data']) and is_array($attributes['data']))
    {
      $attributes = HTML::data_attributes($attributes['data']) + $attributes;
      unset($attributes['data']);
    }

    // class as an array
    if (isset($attributes['class']) and is_array($attributes['class']))
    {
      $attributes['class'] = HTML::class_names($attributes['class']);
    }

    // id as an array
    if (isset($attributes['id']) and is_array($attributes['id']))
    {
      $attributes['id'] = HTML::id_names($attributes['id']);
    }

    // style as an array
    if (isset($attributes['style']) and is_array($attributes['style']))
    {
      $attributes['style'] = HTML::style_attributes($attributes['style']);
    }

    return parent::attributes($attributes);
  }

  public static function data_attributes(array $attributes = NULL)
  {
    $compiled = [];

    foreach ($attributes as $key => $value)
    {
      if (is_int($key))
      {
        $compiled['data-' . $value] = TRUE;
      }
      else
      {
        $compiled['data-' . $key] = $value;
      }
    }

    return $compiled;
  }

  public static function class_names(array $classes = NULL)
  {
    $compiled = [];

    foreach ($classes as $index => $class)
    {
      if (is_int($index))
      {
        $compiled[] = $class;
      }
      else if ($class)
      {
        $compiled[] = $index;
      }
    }

    return implode(' ', $compiled);
  }

  public static function id_names(array $ids = NULL)
  {
    $compiled = [];

    foreach ($ids as $index => $id)
    {
      if (is_int($index))
      {
        $compiled[] = $id;
      }
      else if ($id)
      {
        $compiled[] = $index;
      }
    }

    return implode('-', $compiled);
  }

  public static function style_attributes(array $styles = NULL)
  {
    $compiled = [];

    foreach ($styles as $key => $value)
    {
      $compiled[] = $key . ':' . $value;
    }

    return implode('; ', $compiled);
  }

}