<?php

declare(strict_types=1);

$source = __DIR__ . '/../docroot/libraries/yaireo--tagify';
$target = __DIR__ . '/../docroot/libraries/tagify';

try {
  if (! is_dir($source)) {
    throw new RuntimeException(
      sprintf('Source directory does not exist: %s', $source)
    );
  }

  if (realpath($source) === realpath($target)) {
    throw new RuntimeException(
      'Source and target directory must not be identical.'
    );
  }

  if (file_exists($target) || is_link($target)) {
    removeDirectory($target);
  }

  createDirectory($target);
  copyDirectory($source, $target);

  echo sprintf(
    "Directory synchronized successfully:\n%s\n->\n%s\n",
    $source,
    $target
  );

  exit(0);
} catch (Throwable $exception) {
  fwrite(
    STDERR,
    sprintf(
      "Directory synchronization failed: %s\n",
      $exception->getMessage()
    )
  );

  exit(1);
}

/**
 * Recursively removes a directory or file.
 */
function removeDirectory(string $path): void
{
  if (is_link($path) || is_file($path)) {
    if (! unlink($path)) {
      throw new RuntimeException(
        sprintf('Could not remove file: %s', $path)
      );
    }

    return;
  }

  if (! is_dir($path)) {
    return;
  }

  $entries = scandir($path);

  if ($entries === false) {
    throw new RuntimeException(
      sprintf('Could not read directory: %s', $path)
    );
  }

  foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') {
      continue;
    }

    removeDirectory($path . DIRECTORY_SEPARATOR . $entry);
  }

  if (! rmdir($path)) {
    throw new RuntimeException(
      sprintf('Could not remove directory: %s', $path)
    );
  }
}

/**
 * Recursively copies a directory and its contents.
 */
function copyDirectory(string $source, string $target): void
{
  createDirectory($target);

  $entries = scandir($source);

  if ($entries === false) {
    throw new RuntimeException(
      sprintf('Could not read source directory: %s', $source)
    );
  }

  foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') {
      continue;
    }

    $sourcePath = $source . DIRECTORY_SEPARATOR . $entry;
    $targetPath = $target . DIRECTORY_SEPARATOR . $entry;

    if (is_link($sourcePath)) {
      throw new RuntimeException(
        sprintf('Symbolic links are not supported: %s', $sourcePath)
      );
    }

    if (is_dir($sourcePath)) {
      copyDirectory($sourcePath, $targetPath);
      continue;
    }

    if (! is_file($sourcePath)) {
      throw new RuntimeException(
        sprintf('Unsupported filesystem entry: %s', $sourcePath)
      );
    }

    if (! copy($sourcePath, $targetPath)) {
      throw new RuntimeException(
        sprintf(
          'Could not copy file: %s -> %s',
          $sourcePath,
          $targetPath
        )
      );
    }

    $permissions = fileperms($sourcePath);

    if ($permissions !== false) {
      chmod($targetPath, $permissions & 0777);
    }
  }
}

/**
 * Creates a directory if it does not exist.
 */
function createDirectory(string $path): void
{
  if (is_dir($path)) {
    return;
  }

  if (! mkdir($path, 0775, true) && ! is_dir($path)) {
    throw new RuntimeException(
      sprintf('Could not create directory: %s', $path)
    );
  }
}
