<?php

namespace Tiimber\Cli;

use Tiimber\Cli\Application;

class PathResolver
{
  public function resolve($dir)
  {
    if (strpos($dir, 'vendor/ndufreche/tiimber-cli')) {
      $dir = dirname(dirname(dirname($dir)));
    }

    return $dir;
  }

  public function resolveProjectName()
  {
    $appDir = $this->getAppDir();
    $folders = scandir($appDir);
    foreach ($folders as $folder) {
      if (!in_array($folder, ['.', '..']) && is_dir($appDir . DIRECTORY_SEPARATOR . $folder)) {
        return $folder;
      }
    }
  }

  public function getRootDir()
  {
    Application::getBaseDir() . DIRECTORY_SEPARATOR;
  }

  public function getAppDir()
  {
    return Application::getBaseDir() . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR;
  }

  public function getTplDir()
  {
    return Application::getBaseDir() . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR;
  }

  public function getConfDir()
  {
    return Application::getBaseDir() . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
  }

  public function getRouteDir()
  {
    return $this->getConfDir() . 'routes' . DIRECTORY_SEPARATOR;
  }

  public function getResourceDir()
  {
    return Application::getBaseDir() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR;
  }

  public function getLayoutDir()
  {
    return $this->getTplDir() . DIRECTORY_SEPARATOR . 'Layouts' . DIRECTORY_SEPARATOR;
  }
}