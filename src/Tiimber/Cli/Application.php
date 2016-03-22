<?php

namespace Tiimber\Cli;

use Symfony\Component\Console\Application as SfConsoleApplication;

use Tiimber\Cli\PathResolver;

class Application extends SfConsoleApplication
{
  private static $instance;

  private $dir;

  public function __construct()
  {
    self::$instance = $this;
    parent::__construct();
  }

  public function setBaseDir($dir)
  {
    $this->dir = (new PathResolver)->resolve($dir);
  }

  public static function getBaseDir()
  {
    return self::$instance->dir;
  }
}