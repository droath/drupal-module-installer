<?php

namespace Droath\DrupalModuleInstaller;

/**
 * Interface \Droath\DrupalModuleInstaller\BinaryInterface.
 */
interface BinaryInterface {

  /**
   * Install Drupal modules.
   *
   * @param array $modules
   *   An array of modules to install.
   *
   * @return self
   *   Return current object.
   */
  public function install(array $modules);

  /**
   * Uninstall Drupal modules.
   *
   * @param array $modules
   *   An array of modules to install.
   *
   * @return self
   *   Return current object.
   */
  public function uninstall(array $modules);

}
