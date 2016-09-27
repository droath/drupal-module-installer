<?php

namespace Droath\DrupalModuleInstaller\Binary;

use Droath\DrupalModuleInstaller\Binary;
use Droath\DrupalModuleInstaller\BinaryInterface;

/**
 * Class \Droath\DrupalModuleInstaller\Binary\Drush.
 */
class Drush extends Binary implements BinaryInterface {

  /**
   * {@inheritdoc}
   */
  public function install(array $modules) {
    $this->command
      ->addFlag('y')
      ->addSubCommand('pm-enable');

    $this->addModuleParams($modules);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $modules) {
    $this->command
      ->addFlag('y')
      ->addSubCommand('pm-uninstall');

    $this->addModuleParams($modules);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheRebuild() {
    $this->command->addSubCommand('cache-rebuild');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateDatabase($clear_cache = FALSE) {
    $this->command
      ->addSubCommand('updatedb')
      ->addArgument('cache-clear', $clear_cache)
      ->addArgument('entity-updates', TRUE)
      ->addFlag('y');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDatabaseConnection() {
    $this->command
      ->addSubCommand('status')
      ->addSubCommand('bootstrap');

    return $this;
  }

  /**
   * Add modules as parameters to the binary command.
   *
   * @param array $modules
   *   An array of modules.
   *
   * @return self
   *   Return current object.
   */
  protected function addModuleParams(array $modules) {
    foreach ($modules as $module) {
      if (!isset($module)) {
        continue;
      }

      $this->command->addParam($module);
    }

    return $this;
  }

}
