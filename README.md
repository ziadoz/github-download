# GitHub Download

A command line utility to download your GitHub pull requests and gists, built using [Laravel Zero](https://laravel-zero.com/).

## Install

```
composer install
```

## Setup

Create a GitHub token with the following permissions:

* `repo`
    * `repo:status`
    * `repo_deployment`
    * `public_repo`
    * `repo:invite`
    * `security_events`
* `admin:org`
    * `write:org`
    * `read:org`
    * `manage_runners:org`
* `gist`

Run `cp .env.sh.sample .env.sh` to create a `.env.sh` file in the project root, and then add the newly created token to it.

Run `source .env.sh` to make the token available.

Alternatively, you can add `export GITHUB_TOKEN="your-token"` directly to your ZSH `~/.zprofile` or `~/.zshrc` instead.

## Usage

```
php application dl-prs
php application dl-gists
```
