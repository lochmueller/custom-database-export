# Custom Database Export

> CLI to download databases with separated configuration for structure and data, to exclude tables, limit data and/or replace special values in the export.
> Specialized in export databases to fit the rules of GDPR and keep the filesize small. Less storage, less energy :)

## Installation

1. Download the [latest released phar](https://github.com/lochmueller/custom-database-export/releases) file of "Custom Database Export"
2. `chmod +x custom-database-export.phar` and (optional) create a alias for the execution (e.g. if you need a special PHP version)
3. Run `custom-database-export init` to create the configuration file incl. the documentation
4. Adapt the configuration for your needs
5. Export the database with `custom-database-export` (optional `--configuration` argument for the configuration file path - default: `custom-database-export.yaml`)

## Configuration

Check `custom-database-export.yaml` after the init process. The base file is a copy of [this yaml](https://github.com/lochmueller/custom-database-export/blob/main/res/custom-database-export.yaml.dist).

## Tech-Talk

Use [druidfi/mysqldump-php](https://github.com/druidfi/mysqldump-php) for export the information and symfony [yaml](https://symfony.com/doc/current/components/yaml.html) & [console](https://symfony.com/doc/current/components/console.html) component for configuration and CLI management. Override value information with [Faker](https://fakerphp.github.io/) (only en_US locale is part of the phar file).

Feel free to send contributions via GitHub.

## ToDo:

- Box Building in GitHub Actions
