<?php

namespace Infoarena\Filesystem;

interface FilesystemInterface
{
    /**
     * Read file pointed by the given filepath
     *
     * @param  string $path  File path of the file to be read.
     *
     * @return string        The file contents
     *
     */
    public function readFile($path);

    /**
     * Atomically write to file
     *
     * @param  string $path  the file to be written to
     * @param  string $data  the data to be written
     *
     * @return int           the total number of bytes written
     */
    public function writeFile($path, $data);

    /**
     * Rename a file or directory
     *
     * @param  string $source       The source file or directory to be renamed
     * @param  string $destination  The new name for the file
     *
     * @return void
     */
    public function rename($source, $destination);

    /**
     * Change the permissions of a file or directory
     *
     * @param  string $path   Path to file or directory
     * @param  int    $umask  Permission umask. Note that umask is in octal, so you
     *                        should specify it as, e.g., '0777', not '777'.
     * @return void
     */
    public function chmod($path, $umask);

    /**
     * Canonicalize a path, resolving it relative to a directory (default PWD)
     *
     * @param  string $path         File path to be resolved.
     * @param  string $relative_to  Directory to which the file is relative to, default PWD.
     *
     * @return string               The resolved file path.
     */
    public function resolvePath($path, $relative_to = null);

    /**
     * Asserts that a file path exist, i.e points to something
     * a directory, a file or a symlink
     *
     * @param  string $path          File path to check for existence
     *
     * @return void
     */
    public function assertExists($path);

    /**
     * Asserts that the given filepath points to a file
     *
     * @param  string $path  File path to check
     *
     * @return void
     */
    public function assertIsFile($path);

    /**
     * Asserts that the given filepath points to a directory
     *
     * @param  string $path  File path to check
     *
     * @return void
     */
    public function assertIsDirectory($path);

    /**
     * Asserts the given filepath points to a readable location
     *
     * @param  string $path  File path to check
     *
     * @return void
     */
    public function assertReadable($path);

    /**
     * Asserts the given filepath points to a writable location for a file
     *
     * @param  string $path  File path to check
     *
     * @return void
     */
    public function assertWritableFile($path);

    /**
     * Asserts the given filepath points to a writable location
     *
     * @param  string $path File path to check
     *
     * @return void
     */
    public function assertWritable($path);

    /**
     * Check if a file exists
     *
     * @param  string $path  The filepath to check for existence
     *
     * @return bool          true if the filepath points to something, false otherwise
     */
    public function pathExists($path);
}
