# Forge Previewer

This CLI is designed to create "preview" environments for pull requests and branches using Laravel Forge.

It's intended for use inside of CI tools such as GitHub Actions to automatically create a site on Forge that is connected to your pull request branch, then once the pull request is merged the CLI can be used to cleanup too.

## Prerequisites

Before using this tool, please make sure you have the following:
* A server connected to [Forge](https://forge.laravel.com)
* A wildcard subdomain DNS record pointing to your Forge server.
* A [Forge API token](https://forge.laravel.com/docs/1.0/accounts/api.html)

## Installation

Install this package with Composer:

```sh
composer global require ryangjchandler/forge-previewer
```

## Usage

There are two commands in the command.

* `deploy` - Creates and deploys a site.
* `destroy` - Deletes and cleans up after a site.

### `deploy`

The `deploy` command is used to do the following things:

1. Create a site on Forge.
2. Generate an SSL certificate for the new site.
3. Create a database for the new site.
4. Enable quick deploy so all changes appear on the preview site automatically.
4. Update the environment variables to point to the database.
5. Deploy your site once.
6. Run any additional commands provided.
7. Create a scheduled job if required.

The command accepts the following flags:

```
Description:
  Deploy a branch / pull request to Laravel Forge.

Usage:
  deploy [options]

Options:
    --token[=TOKEN]              The Forge API token.
    --server[=SERVER]            The ID of the target server.
    --provider[=PROVIDER]        The Git provider. [default: "github"]
    --repo[=REPO]                The name of the repository being deployed.
    --branch[=BRANCH]            The name of the branch being deployed.
    --domain[=DOMAIN]            The domain you'd like to use for deployments, e.g. mydomain.com.
    --php-version[=PHP-VERSION]  The version of PHP the site should use, e.g. php81, php80, ... [default: "php81"]
    --command[=COMMAND]          A command you would like to execute on the site, e.g. php artisan db:seed. (multiple values allowed)
    --edit-env[=EDIT-ENV]        The colon-separated name and value that will be added/updated in the site's environment, e.g. "MY_API_KEY:my_api_key_value". (multiple values allowed)
    --scheduler                  Setup a cronjob to run Laravel's scheduler.
    --no-quick-deploy            Create your site without "Quick Deploy".
    --no-deploy                  Avoid deploying the site.
```

> **Note**: the `deploy` command can be run multiple times and will skip any steps that have already been run previously.

### `destroy`

The `destroy` command simply reverses all of the things that the `deploy` command does. It checks for the existence of certain resources in Forge and removes them if they exist.

The command accepts the following flags:

```
Description:
  Destroy a previously created preview site.

Usage:
  destroy [options]

Options:
    --token[=TOKEN]    The Forge API token.
    --server[=SERVER]  The ID of the target server.
    --repo[=REPO]      The name of the repository being deployed.
    --branch[=BRANCH]  The name of the branch being deployed.
    --domain[=DOMAIN]  The domain you'd like to use for deployments.
```

Since Forge Previewer is convention based, we will try to detect resources based on the names we generate for them.

## Example Workflow

_TODO_
