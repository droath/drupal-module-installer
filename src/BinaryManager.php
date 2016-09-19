<?php

namespace Droath\DrupalModuleInstaller;

use Composer\Composer;

/**
 * Class \Droath\DrupalModuleInstaller\BinaryManager.
 */
class BinaryManager {

  /**
   * Composer object.
   *
   * @var Composer\Composer.
   */
  protected $composer;

  /**
   * Binaries array.
   *
   * @var array
   */
  protected $binaries = [];

  /**
   * Constructor for \Droath\DrupalModuleInstaller\BinaryManager.
   *
   * @param Composer\Composer $composer
   *   An composer object.
   */
  public function __construct(Composer $composer) {
    $this->composer = $composer;
  }

  /**
   * Add binary object.
   *
   * @param string $name
   *   A unique machine name.
   * @param array $metadata
   *   An array of metadata about the binary.
   */
  public function addBinary($name, array $metadata) {
    if (isset($metadata['classname']) && class_exists($metadata['classname'])) {
      $this->binaries[$name] = $metadata;
    }

    return $this;
  }

  /**
   * Add an array of binaries.
   *
   * @param array $binaries
   *   An array of binaries to register.
   */
  public function addBinaries(array $binaries = []) {
    foreach ($binaries as $name => $metadata) {
      $this->addBinary($name, $metadata);
    }

    return $this;
  }

  /**
   * Load a single binary by name.
   *
   * @param string $name
   *   The unique binary name.
   *
   * @return \Drupal\Core\TypedData\Type\BinaryInterface|bool
   *   An instantiated binary object; otherwise FALSE.
   */
  public function loadBinary($name) {
    $metadata = $this->loadBinaryMetadata($name);

    if (empty($metadata)) {
      return FALSE;
    }
    $classname = $metadata['classname'];
    $executable = $this->loadBinaryExcutePathFromComposer($metadata);

    return new $classname($executable);
  }

  /**
   * Load binary metadata.
   *
   * @param string $name
   *   The unique binary name.
   *
   * @return array
   *   An array of binary metadata.
   */
  public function loadBinaryMetadata($name) {
    if (!isset($this->binaries[$name])) {
      return [];
    }

    return $this->binaries[$name];
  }

  /**
   * Load binary executable path from composer.
   *
   * Attempt to find the binary executable path from an installed package.
   *
   * @param array $metadata
   *   An array of the binary metadata.
   *
   * @return string|bool
   *   The binary executable path from composer or default; otherwise FALSE.
   */
  public function loadBinaryExcutePathFromComposer(array $metadata) {
    if (!isset($metadata['package'])) {
      return FALSE;
    }
    $repository = $this->composer
      ->getRepositoryManager()
      ->getLocalRepository();

    $package = $repository->findPackage($metadata['package'], '*');

    if (is_null($package)
      || !in_array($metadata['binary'], $package->getBinaries())) {
      return $metadata['binary'];
    }

    return $this->composer->getConfig()->get('bin-dir') . '/' . $metadata['binary'];
  }

  /**
   * Get all registered binaries.
   *
   * @return array
   *   An array of all register binaries.
   */
  public function getBinaries() {
    return $this->binaries;
  }

}
