<?php

/**
 * Advanced View Engine with HAML templates
 *
 * Uses MtHaml
 *
 * @package Advanced_View
 * @author  Alexei Shabalin <mail@alshablin.com>
 */
class Advanced_View extends Kohana_View {

  public static $cacheable = FALSE;

  protected $_is_partial   = FALSE;
  protected $_is_inline    = FALSE;
  protected $_viewfile     = '';
  protected $_origfile     = '';
  protected $_basedir      = './';
  protected $_format       = 'html';
  protected $_inline_haml  = '';

  protected static $_last_basedir = NULL;

  /**
   * $view = View::factory('template');
   * $view = View::factory(['action' => 'template']);
   *
   */
  public static function factory($options = NULL, array $data = NULL)
  {
    return new View($options, $data);
  }


  public function render($file = NULL, array $data = NULL)
  {
    Kohana::$profiling === TRUE && $bm = Profiler::start('View', __FUNCTION__);

    if ($file !== NULL)
    {
      $this->set_filename($file);
    }

    if (is_array($data) || $data instanceof Traversable)
    {
      $this->set($data);
    }

    if ( ! $this->_file || ! is_file($this->_file))
    {
      throw new View_Exception('The requested view :file could not be found', [
        ':file' => $this->_origfile,
      ]);
    }

    $result = View::capture($this->_file, $this->_data);

    isset($bm) && Profiler::stop($bm);

    return $result;
  }


  protected $_compile_dir = NULL;

  public function __construct($options = NULL, array $data = NULL)
  {
    Kohana::$profiling === TRUE && $bm = Profiler::start('View', __FUNCTION__);

    $this->_compile_dir = APPPATH . 'cache/haml/';

    if ( ! is_dir($this->_compile_dir))
    {
      mkdir($this->_compile_dir, 0777, TRUE);
    }

    parent::__construct($options, $data);

    isset($bm) && Profiler::stop($bm);
  }

  public function viewfile_exists($file, $basedir = NULL)
  {
    $file = strtolower($file);

    if ($basedir !== NULL && strpos($file, '/') === FALSE)
    {
      $file = rtrim($basedir) . '/' . $file;
    }

    return Kohana::find_file('views', $file, $this->_format . '.haml') !== FALSE ||
           Kohana::find_file('views', $file, $this->_format . '.php') !== FALSE;
  }

  public function set_filename($file)
  {
    Kohana::$profiling === TRUE && $bm = Profiler::start('View', __FUNCTION__);

    $this->_import_options($file);

    if ($this->_viewfile)
    {

      if (($path = Kohana::find_file('views', $this->_viewfile, $this->_format . '.haml')) === FALSE)
      {
        if (($path = Kohana::find_file('views', $this->_viewfile, $this->_format . '.php')) !== FALSE)
        {
          $this->_file = $path;
        }
        else if (is_string($file) && ($path = Kohana::find_file('views', $file)) !== FALSE)
        {
          $this->_file = $path;
        }
        isset($bm) && Profiler::stop($bm);
        return $this;
      }

      $this->_file = $this->_get_compiled_haml($path);
      isset($bm) && Profiler::stop($bm);
      return $this;
    }

    if ($this->_is_inline)
    {
      $this->_file = $this->_get_compiled_inline_haml($this->_inline_haml);
    }

    isset($bm) && Profiler::stop($bm);
 
    return $this;
  }

  public function change_format($format)
  {
    return $this->set_filename(['format' => $format]);
  }

  public function does_exist()
  {
    return is_file($this->_file);
  }


  protected function _import_options($options)
  {
    if (is_array($options))
    {
      foreach ($options as $option => $value)
      {
        switch ($option)
        {
          case 'partial':
            $this->_is_partial = TRUE;
            /* no break, follows down */
          case 'action':
          case 'template':
          case 'file':
            $file = $this->_origfile = strtolower((string)$value);
            break;
          case 'inline':
          case 'haml':
            $this->_is_inline = TRUE;
            $this->_inline_haml = (string)$value;
            $this->_viewfile = '';
            break;
          case 'locals':
            if (is_array($value) || $value instanceof Traversable)
            {
              $this->set($value);
            }
            break;
          case 'format':
            $this->_format = strtolower($value);
            break;
          case 'basedir':
            $this->_basedir = static::$_last_basedir = strtolower(rtrim((string)$value, '/') . '/');
            break;
          case 'layout':
            View::layout($value);
            break;
        }
      }
    }
    else
    {
      $file = $this->_origfile = (string)$options;
    }

    if (isset($file))
    {
      $this->_viewfile = $this->prepare_view_filename($file);
    }
  }

  protected function prepare_view_filename($file)
  {
    if (preg_match('#^(?<path>(?<root>/)?.*)?(?<file>\b[^/]+)$#', $file, $match))
    {
      if ($this->_is_partial)
      {
        $match['file'] = '_' . $match['file'];;
      }

      if ($match['path'])
      {
        if ($match['root'])
        {
          $this->_root = TRUE;
        }
        $file = $match['path'] . $match['file'];
      }
      else
      {
        $file = $this->_basedir . $match['file'];
      }
    }
    return $file;
  }


  protected static $_layout  = NULL;
  protected static $_layouts = [];

  public static function begin()
  {
    static $first_called;

    if (empty($first_called))
    {
      $first_called       = TRUE;
      static::$_layouts[] = NULL;
    }
    else
    {
      static::$_layouts[] = static::$_layout;
      static::$_layout    = NULL;
    }

    ob_start();
  }

  public static function end()
  {
    static::$_layout_pull[] = ob_get_clean();
    $layout = static::$_layout;
    static::$_layout = array_pop(static::$_layouts);
    return $layout;
  }

  public static function layout($layout = NULL, $overwrite = FALSE)
  {
    if ($layout === NULL)
    {
      return static::$_layout;
    }

    if ($overwrite || static::$_layout === NULL)
    {
      static::$_layout = strtolower($layout);
    }
  }



  protected static function _haml_compiler()
  {
    static $_haml_compiler;

    if ($_haml_compiler === NULL)
    {
      $_haml_compiler = new MtHaml\Environment('php', [], [
        'markdown' => new MtHaml\Filter\Markdown\Parsedown(new Parsedown),
      ]);
    }
    return $_haml_compiler;
  }

  protected function _get_compiled_haml($path)
  {
    $compiled_file = $this->_compile_dir . md5($path);

    if ( ! static::$cacheable || ! is_file($compiled_file) || filemtime($compiled_file) <= filemtime($path))
    {
      $haml = file_get_contents($path);

      file_put_contents($compiled_file, $this->_compile_haml($haml, $path));
    }

    return $compiled_file;
  }

  protected function _get_compiled_inline_haml($haml)
  {
    $compiled_file = $this->_compile_dir . '_inline_' . md5($haml);

    if ( ! static::$cacheable || ! is_file($compiled_file))
    {
      file_put_contents($compiled_file, $this->_compile_haml($haml));
    }

    return $compiled_file;
  }

  protected function _compile_haml($haml, $path = '(inline)')
  {
    $haml = preg_replace_callback('#^(?<spaces>[ \t]*)(?<tag>[^ ].*)?(?<op>==|&=|!=|=|!~|~)\s*(?<command>render|yield\?|yield|link_to|stylesheet_link_tag|javascript_include_tag|image_tag|video_tag)(?:\s+(?<args>.*))?\s*$#mU', 
      function($match) {
        $args    = trim(Arr::get($match, 'args'));
        $command = trim(Arr::get($match, 'command'));
        $spaces  = Arr::get($match, 'spaces');
        $tag     = (string)Arr::get($match, 'tag');
        $op      = Arr::get($match, 'op');
        $op      = $op === '=' ? '!=' : $op;
        switch ($command)
        {
          case 'render':  return "{$spaces}{$tag}{$op} View::adjust_indent((string)View::factory(['partial' => {$args}, 'basedir' => '{$this->_basedir}', 'format' => '{$this->_format}'], get_defined_vars()), '{$spaces}', " . ($tag !== '' ? 'TRUE' : 'FALSE') . ")";
          case 'yield':   return "{$spaces}{$tag}{$op} View::adjust_indent(View::get_content({$args}), '{$spaces}', " . ($tag !== '' ? 'TRUE' : 'FALSE') . ")";
          case 'yield?':  return "{$spaces}{$tag}{$op} View::adjust_indent(View::get_content({$args}, TRUE), '{$spaces}', " . ($tag !== '' ? 'TRUE' : 'FALSE') . ")";
          case 'link_to': return "{$spaces}{$tag}{$op} View::link_to({$args})";

          // kohana-advanced-assets required
          case 'stylesheet_link_tag':    return "{$spaces}{$tag}{$op} Assets::stylesheet_link_tag({$args})";
          case 'javascript_include_tag': return "{$spaces}{$tag}{$op} Assets::javascript_include_tag({$args})";
          case 'image_tag': return "{$spaces}{$tag}{$op} Assets::image_tag({$args})";
          case 'video_tag': return "{$spaces}{$tag}{$op} Assets::video_tag({$args})";
        }
      }, $haml);

    $haml = preg_replace_callback('#^(?<spaces>[ \t]*)\-\s*(?<command>content_for|cached_fragment|layout|form_for)\s+(?<args>.*)\s*$#mU', 
      function($match) {
        $spaces  = Arr::get($match, 'spaces');
        $command = Arr::get($match, 'command');
        $args    = trim(Arr::get($match, 'args'));
        switch ($command)
        {
          case 'content_for':     return "{$spaces}- while (View::content_for({$args}))";
          case 'form_for':        return "{$spaces}- while (View::form_for('{$spaces}', {$args}))";
          case 'cached_fragment': return "{$spaces}- while (View::cached_fragment({$args}))";
          case 'layout':          return "{$spaces}- View::layout({$args}, TRUE)";
        }
      }, $haml);

    return $this->get_header_string() .
           static::_haml_compiler()->compileString($haml, $path) . 
           $this->get_eof_string();
  }

  protected $_header_string = NULL;

  protected function get_header_string()
  {
    return '<?php /* ' . ($this->_viewfile ?: 'custom inline haml') . ' compiled at ' . date('Y-m-d H:i:s') . ' */ ?>' . "\n" . 
           "<?php View::begin(); ?>\n" .
           $this->_header_string;
  }


  protected $_eof_string = NULL;

  protected function get_eof_string()
  {
    return "<?php \$layout = View::end(); ?>\n" . 
           "<?php if (\$layout) {\n  echo (string)View::factory(['template' => \$layout, 'basedir' => 'layouts/', 'format' => '{$this->_format}'], get_defined_vars()); \n}\nelse \n{\n  echo View::get_content(); \n} ?>\n";
  }




  /**
   * Pads a block with spaces
   * @param  string  $content      Generated well-formed HTML code
   * @param  string  $spaces       String of spaces to pad each line of `$content`
   * @param  boolean $inside_block Indicates if `$content` is inside an other HTML tag
   * @return string                Padded with apropriate spaces HTML code
   */
  public static function adjust_indent($content, $spaces = '', $inside_block = FALSE)
  {
    if (isset(static::$_buffer_pull[0]))
    {
      $spaces = substr($spaces, 0, -2 * count(static::$_buffer_pull));
    }

    $content = rtrim($content);

    if (FALSE === strpos($content, "\n"))
    {
      return $content;
    }

    if ($inside_block)
    {
      return "\n  " . $spaces . str_replace("\n", "\n  " . $spaces, $content) . "\n" . $spaces;
    }

    return str_replace("\n", "\n" . $spaces, $content);
  }


  public static function preserve($text)
  {
    return str_replace("\n", '&#x000A;', $text);
  }


  /**
   * Content buffers
   */

  protected static $_tbuffers    = [];
  protected static $_buffers     = [];
  protected static $_buffer_pull = [];
  protected static $_layout_pull = [];

  public static function content_for($id, $overwrite = TRUE)
  {
    if ( ! $overwrite && isset(static::$_buffers[$id]))
    {
      $id .= '-default';
    }

    if (isset(static::$_tbuffers[$id]) && static::$_tbuffers[$id] === TRUE)
    {
      $buffer_id = array_pop(static::$_buffer_pull);
      static::$_buffers[$id] = rtrim(ob_get_clean());
      unset(static::$_tbuffers[$id]);
      return FALSE;
    }

    static::$_tbuffers[$id]  = TRUE;
    static::$_buffer_pull[] = $id;
    ob_start();
    return true;
  }

  public static function get_content($id = NULL, $yield_layout = FALSE)
  {
    if ($id === NULL)
    {
      return array_pop(static::$_layout_pull);
    }
    return isset(static::$_buffers[$id]) ? static::$_buffers[$id] : ($yield_layout ? array_pop(static::$_layout_pull) : '');
  }


  public static function link_to($text, $url, array $attributes = NULL)
  {
    if ($url instanceof ORM || is_array($url))
    {
      $url = Route::url_for($url, 'path');
    }
    return HTML::anchor($url, $text, $attributes);
  }

  /**
   * Form for a model
   */

  protected static $form_open = FALSE;

  public static function form_for($spaces, $model, array $attributes = NULL)
  {
    if (static::$form_open === FALSE)
    {
      echo Form::model($model, $attributes);
      ob_start();

      if (empty($attributes['method']))
      {
        $attributes['method'] = $model->loaded() ? 'PUT' : 'POST';
      }

      if ( ! empty($attributes['method']) && $attributes['method'] !== 'GET' && $attributes['method'] !== 'POST')
      {
        echo Form::hidden('_method', $attributes['method']) . "\n";
        $attributes['method'] = 'POST';
      }

      echo Form::hidden('utf8', '✓') . "\n";

      if ($attributes['method'] !== 'GET')
      {
        echo Form::hidden('authenticity_token', Security::token()) . "\n";
      }

      static::$form_open = TRUE;
    }
    else
    {
      echo View::adjust_indent(ob_get_clean(), $spaces, TRUE);
      echo Form::close() . "\n";
      static::$form_open = FALSE;
    }
    return static::$form_open;
  }


  /**
   * Cache
   */

  protected static $_cache_buffers     = [];
  protected static $_cache_buffer_pull = [];

  public static function cached_fragment($name, $lifetime = NULL, $i18n = NULL)
  {
    if (isset(static::$_cache_buffers[$name]) && static::$_cache_buffers[$name] === TRUE)
    {
      $buffer_id = array_pop(static::$_cache_buffer_pull);
      unset(static::$_cache_buffers[$name]);
      Fragment::save();
      return FALSE;
    }

    static::$_cache_buffers[$name]  = TRUE;
    static::$_cache_buffer_pull[] = $name;

    if (NULL != $lifetime)
    {
      $lifetime *= 60;
    }

    if (Fragment::load($name, $lifetime, $i18n))
    {
      $buffer_id = array_pop(static::$_cache_buffer_pull);
      unset(static::$_cache_buffers[$name]);
      return FALSE;
    }

    return TRUE;
  }



  /**
   * Syntax sugar
   *   View::share('user', $user);
   */

  public static function share($key, $value = NULL)
  {
    View::set_global($key, $value);
  }

  public static function share_bind($key, & $value)
  {
    View::bind_global($key, $value);
  }



}
