<?php

namespace Droath\DrupalModuleInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Plugin\PluginInterface;

/**
 * Class \Droath\DrupalModuleInstaller\ComposerPlugin.
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface {

  /**
   * Composer IO.
   *
   * @var Composer\IO\IOInterface.
   */
  protected $io;

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Binary manager.
   *
   * @var \Droath\DrupalModuleInstaller\BinaryManager
   */
  protected $binaryManager;

  /**
   * Flag if package was updated.
   *
   * @var bool
   */
  protected $packageUpdated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->composer = $composer;
    $this->binaryManager = new BinaryManager($composer);
    $this->binaryManager->addBinaries($this->registerBinaries());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_UPDATE_CMD => 'onPostCMDUpdate',
      PackageEvents::POST_PACKAGE_UPDATE => 'onPostPackageUpdate',
      PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
      PackageEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageUninstall',
    ];
  }

  /**
   * Execute common commands after composer runs update or install.
   *
   * @param Composer\Script\Event $event
   *   A composer script event.
   */
  public function onPostCMDUpdate(Event $event) {
    if ($this->isPackageUpdated()) {
      $this->binaryExecutable()
        ->updateDatabase()
        ->execute();
    }

    $this->binaryExecutable()->cacheRebuild()->execute();
  }

  /**
   * React after a composer package has been updated.
   *
   * @param Composer\Installer\PackageEvent $event
   *   A compose package event.
   */
  public function onPostPackageUpdate(PackageEvent $event) {
    $this->setPackageUpdate();
  }

  /**
   * React after a composer package has been installed.
   *
   * @param Composer\Installer\PackageEvent $event
   *   A compose package event.
   */
  public function onPostPackageInstall(PackageEvent $event) {
    $this->runPackageOperation($event);
  }

  /**
   * React after a composer package has been uninstalled.
   *
   * @param Composer\Installer\PackageEvent $event
   *   A compose package event.
   */
  public function onPrePackageUninstall(PackageEvent $event) {
    $this->runPackageOperation($event);
  }

  /**
   * Set package update.
   *
   * @return self
   *   The current object.
   */
  protected function setPackageUpdate() {
    $this->packageUpdated = TRUE;

    return $this;
  }

  /**
   * Check if a package was updated.
   *
   * @return bool
   *   TRUE if a package has been updated; otherwise FALSE.
   */
  protected function isPackageUpdated() {
    return $this->packageUpdated;
  }

  /**
   * Run package by operation.
   *
   * @param Composer\Installer\PackageEvent $event
   *   A compose package event.
   */
  protected function runPackageOperation(PackageEvent $event) {
    $package = $event->getOperation()->getPackage();

    if ($package->getType() !== 'drupal-module') {
      return;
    }
    $package_name = $package->getName();

    if (!is_string($package_name)) {
      return;
    }

    $module = substr($package_name, strpos($package_name, '/') + 1);
    $operation = $event->getOperation()->getJobType();

    $this->runBinaryByOperation($operation, [$module]);
  }

  /**
   * Run binary by operation.
   *
   * @param string $operation
   *   A binary operation to perform.
   * @param array $modules
   *   An array of Drupal modules.
   */
  protected function runBinaryByOperation($operation, array $modules = []) {
    if (!isset($operation)) {
      return;
    }
    static $count = 0;
    static $skip_prompt = FALSE;

    if ($count == 0) {
      $skip_prompt = $this->io->askConfirmation(
        sprintf('Skip confirmation prompt and %s all modules? [yes] ', $operation)
      );
    }
    $is_confirmed = FALSE;

    if (!$skip_prompt) {
      $is_confirmed = $this->io->askConfirmation(
        sprintf('Do you want to %s %s? [yes] ', $operation, implode(', ', $modules))
      );
    }

    if ($is_confirmed || $skip_prompt) {
      $executable = $this->binaryExecutable();

      switch ($operation) {
        case 'install':
          $executable->install($modules);
          break;

        case 'uninstall':
          $executable->uninstall($modules);
          break;
      }

      $executable->execute();
    }

    ++$count;
  }

  /**
   * Get installer binary executable.
   *
   * @return \Droath\DrupalModuleInstaller\BinaryInterface
   *   A installer binary executable object.
   */
  protected function binaryExecutable() {
    return $this->binaryManager
      ->loadBinary($this->binary())
      ->setRoot($this->findDrupalRootDirectory());
  }

  /**
   * Get installer binary.
   *
   * Define what binary you want to use when installing/uninstalling
   * Drupal modules. This will attempt to load the binary from the composer
   * vendor bin directory if the package is listed as a requirement.
   *
   * If the binary is not found it will default to using the system level. Make
   * sure to add the binary path within your bash or zsh profile.
   *
   * Options:
   *   - drush: Drush command line tool (default).
   *   - drupal: Drupal console command line tool.
   *
   * @return string
   *   The binary to use; defaults to 'drush'.
   */
  protected function binary() {
    $plugin_extra = $this->getPluginExtra();

    return isset($plugin_extra['binary']) ? $plugin_extra['binary'] : 'drush';
  }

  /**
   * Find Drupal root directory.
   *
   * @return string
   *   The Drupal root path.
   */
  protected function findDrupalRootDirectory() {
    $composer_extra = $this->getComposerExtra();

    // Only if the project is using the installer path composer plugin is this
    // data available. If the Drupal root directory is not discoverable then
    // it's going to have to be defined in the project root composer.json.
    if (isset($composer_extra['installer-paths'])) {
      foreach ($composer_extra['installer-paths'] as $path => $info) {
        if (reset($info) !== 'type:drupal-core') {
          continue;
        }

        $root_directory = dirname($path);
        break;
      }
    }
    $plugin_extra = $this->getPluginExtra();

    if (!isset($root_directory)) {
      $root_directory = isset($plugin_extra['drupal_root']) ? $plugin_extra['drupal_root'] : NULL;
    }

    return getcwd() . '/' . $root_directory;
  }

  /**
   * Get plugin extra.
   *
   * @return array
   *   An array of extra settings for composer plugin.
   */
  protected function getPluginExtra() {
    $extra = $this->getComposerExtra();
    $plugin_key = 'drupal-module-installer';

    if (!isset($extra[$plugin_key])) {
      return [];
    }

    return $extra[$plugin_key];
  }

  /**
   * Get composer extra.
   *
   * @return array
   *   An array of extra plugin settings.
   */
  protected function getComposerExtra() {
    return $this->composer->getPackage()->getExtra();
  }

  /**
   * Get allowed binaries.
   *
   * @return array
   *   An array of allowed binaries.
   */
  protected function allowedBinaries() {
    return array_keys($this->registerBinaries());
  }

  /**
   * Define the available binaries.
   *
   * @return array
   *   An array of available binaries information.
   */
  private function registerBinaries() {
    return [
      'drush' => [
        'binary' => 'drush',
        'package' => 'drush/drush',
        'classname' => '\Droath\DrupalModuleInstaller\Binary\Drush',
      ],
      'drupal' => [
        'binary' => 'drupal',
        'package' => 'drupal/console',
        'classname' => '\Droath\DrupalModuleInstaller\Binary\Drupal',
      ],
    ];
  }

}
