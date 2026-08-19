# Render Docker Build Audit

## Failure observed

The Render build log from 2026-08-19 reached `RUN docker-php-ext-install pdo_sqlite` successfully, then failed at the next layer:

```text
#13 [stage-1 5/14] RUN docker-php-ext-install sqlite3
Cannot find config.m4.
Make sure that you run '/usr/local/bin/phpize' in the top level source directory of the module
Dockerfile:38
```

The preceding layer also emitted `warning: pdo_sqlite (pdo_sqlite) is already loaded!`.

## Root cause

The official `php:8.3-apache` image already compiles `pdo_sqlite` and `sqlite3` into PHP. The generated official PHP 8.3 Bookworm Apache Dockerfile configures PHP with `--with-pdo-sqlite=/usr` and `--with-sqlite3=/usr`.[1] The official `docker-php-ext-install` helper also warns that some modules are already compiled into PHP and should be checked with `php -i` before compiling them again.[2]

The first attempted fix separated the two extension commands, which removed the shared-invocation cleanup collision but still attempted to recompile `sqlite3`. The new Render log proves that this is insufficient: `pdo_sqlite` completes, while the separate `sqlite3` invocation still cannot find `config.m4`. The upstream maintainer response to the same error recommends using the modules already present in the image rather than recompiling them.[3]

## Applied correction

The Dockerfile now compiles only extensions that are not provided by the base image:

```dockerfile
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mbstring zip
```

The explicit `docker-php-ext-install pdo_sqlite` and `docker-php-ext-install sqlite3` commands have been removed. Deployment smoke assertions now prevent those redundant commands from returning.

## Validation boundary

Deployment smoke, PHP lint, shell lint, and `git diff --check` can run in the sandbox. A full Docker build must still be rerun by Render because the sandbox has no Docker CLI/daemon.

## References

[1]: https://github.com/docker-library/php/blob/master/8.3/bookworm/apache/Dockerfile "Official PHP 8.3 Bookworm Apache Dockerfile"
[2]: https://github.com/docker-library/php/blob/master/docker-php-ext-install "Official docker-php-ext-install helper"
[3]: https://github.com/docker-library/php/issues/1058 "docker-library/php issue: Installing sqlite3 extension fails"
