# GitHub Download

A command line utility to download your GitHub pull requests and gists, built using [Laravel Zero](https://laravel-zero.com/).

## Install

```
composer install
```

## Setup

A GitHub token needs to be setup with the following permissions:

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

Then add it to `.env.sh` in the project root and run: `source .env.sh` to make it available.

## Usage

```
php application dl-prs
php application dl-gists
```
