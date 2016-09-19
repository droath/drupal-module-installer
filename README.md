# Drupal Module Installer

This project is a composer plugin that integrates Drupal command-line tools (drush, drupal console) with composer. When installing or uninstalling Drupal module(s) using composer you had to manually install/uninstall the module(s) independently.

With this plugin installed composer will execute drush or drupal console commands based on the operation being performed, which leads to a more seamless development workflow.

## Usage

Run `composer require droath/drupal-module-installer:dev-master` in your composer project.

Once installed you'll get a prompt when installing or uninstalling Drupal modules using composer. It will ask you if you would like to install/uninstall (based on the operation being performed) the newly added module.

## Configuration

The default binary that will be used is `drush`. You can define a binary that you would like to use when executing commands in the `extra` block in the composer.json.

```json
"extra": {
    "drupal-module-installer": {
        "binary": "drush",
        "drupal_root": "[DRUPAL_ROOT_RELATIVE_PATH]"
    }
}
```
**Extra Options:**

  - The `binary` option allows the following:
    - drush
    - drupal
  - (Optional) The `drupal_root` option allows a relative path to the Drupal project. The script attempts to find the Drupal root itself, the path defined here will be used if it's unable to find it.

## Contributing

Feel free to hack on this project. Please submit PRs or feature/bug issues on the GitHub project.

