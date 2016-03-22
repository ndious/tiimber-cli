<?php

namespace Tiimber\Cli;

class PathResolver
{
  public function resolve($dir)
  {
    if (strpos($dir, 'vendor/ndufreche/tiimber-cli')) {
      $dir = dirname(dirname(dirname($dir)));
    }

    return $dir;
  }
}