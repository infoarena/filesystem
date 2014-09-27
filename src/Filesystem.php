<?php

namespace Infoarena\Filesystem;

/**
 * Wrapper class for filesystem function.
 * This class throws detailed exception on failure, instead of returning false
 * Inspired from libphutil filesystem api
 *
 * @author Adrian Budau <budau.adi@gmail.com>
 */
final class Filesystem
{
    /**
     * Read a file similar to file_get_contents but throws on failure
     *
     * @param  string $path  File path of the file to be read.
     *
     * @throws IOException   When getting the contents failed or
     *                       When the file does not exist or
     *                       When the file is not readable
     *
     * @return string        The file contents
     *
     */
    public static function readFile($path)
    {
        $path = self::resolvePath($path);

        self::assertExists($path);
        self::assertIsFile($path);
        self::assertReadable($path);

        $data = @file_get_contents($path);
        if ($data === false) {
            throw new IOException("Failed to read file '{$path}'", $path);
        }

        return $data;
    }

    /**
     * Canonicalize a path, resolving it relative to a directory (default PWD)
     *
     * @param  string $path         File path to be resolved.
     * @param  string $relative_to  Directory to which the file is relative to, default PWD.
     *
     * @return string               The resolved file path.
     */
    public static function resolvePath($path, $relative_to = null)
    {
        $is_absolute = !strncmp($path, DIRECTORY_SEPARATOR, 1);
        if (!$is_absolute) {
            if ($relative_to === null) {
                $relative_to = getcwd();
            }

            // if the path ends in a / we eliminate it
            if (substr($relative_to, -1) == DIRECTORY_SEPARATOR) {
                $relative_to = substr($relative_to, 0, -1);
            }

            $path = $relative_to . DIRECTORY_SEPARATOR . $path;
        }

        $realpath = realpath($path);
        if ($realpath !== false) {
            return $realpath;
        }

        return $path;
    }

    /**
     * Asserts that a file path exist, i.e points to something
     * a directory, a file or a symlink
     *
     * @param  string $path          File path to check for existence
     *
     * @throws FileNotFoundException In case filepath does not point to anything
     *
     * @return void
     */
    public static function assertExists($path)
    {
        if (!self::pathExists($path)) {
            throw new FileNotFoundException(
                "Filesystem entity '{$path}' does not exist",
                $path
            );
        }
    }

    /**
     * Asserts that the given filepath points to a file
     *
     * @param  string $path  File path to check
     *
     * @throws IOException   In case filepath doesn't point to a file
     *
     * @return void
     */
    public static function assertIsFile($path)
    {
        if (!is_file($path)) {
            throw new IOException(
                "Requested path '{$path}' is not a file.",
                $path
            );
        }
    }

    public static function assertReadable($path)
    {
        if (!is_readable($path)) {
            throw new IOException(
                "Path '{$path}' is not readable.",
                $path
            );
        }
    }
    /**
     * Check if a file exists
     * This differs from file_exists because it also checks for links
     *
     * @param  string $path  The filepath to check for existence
     *
     * @return bool          true if the filepath points to something, false otherwise
     */
    public static function pathExists($path)
    {
        return file_exists($path) || is_link($path);
    }
}
