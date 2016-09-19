<?php

namespace Droath\DrupalModuleInstaller;

use AdamBrett\ShellWrapper\Command\Builder;
use AdamBrett\ShellWrapper\Runners\Exec;

/**
 * Class \Droath\DrupalModuleInstaller\Binary.
 */
abstract class Binary {

  /**
   * Binary command.
   *
   * @var \AdamBrett\ShellWrapper\Command\Builder
   */
  protected $command;

  /**
   * Binary executable.
   *
   * @var string
   */
  protected $executable;

  /**
   * Constructor for \Droath\DrupalModuleInstaller\Binary.
   */
  public function __construct($executable) {
    $this->executable = $executable;
    $this->command = new Builder($executable);
  }

  /**
   * Get binary command.
   *
   * @return \AdamBrett\ShellWrapper\Command\Builder
   *   A command builder object.
   */
  public function getCommand() {
    return $this->command;
  }

  /**
   * Set binary root directory.
   *
   * @param string $root
   *   The root directory path.
   */
  public function setRoot($root) {
    $this->command->addFlag('r', $root);

    return $this;
  }

  /**
   * Execute binary command.
   *
   * @throws \Droath\DrupalModuleInstaller\Exception\BinaryExecuteException.
   *
   * @return mixed
   *   Output from the external binary command.
   */
  public function execute() {
    try {
      $output = (new Exec())->run($this->command);
    }
    catch (Exception $e) {
      throw new BinaryExecuteException(
        $e->getMessage()
      );
    }
    $this->reset();

    return $output;
  }

  /**
   * Reset binary command builder.
   *
   * @return self
   *   The current object.
   */
  protected function reset() {
    $this->command = new Builder($this->executable);

    return $this;
  }

}
