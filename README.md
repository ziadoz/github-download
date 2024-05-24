# GitHub Download

## Install

```
composer install
```

## Usage

```
php application dl-prs
php application dl-gists
```

## GitHub Token

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
