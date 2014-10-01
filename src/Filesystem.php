<?php

namespace Infoarena\Filesystem;

/**
 * Wrapper class for filesystem function.
 * This class throws detailed exception on failure, instead of returning false
 * Inspired from libphutil filesystem api
 *
 * @author Adrian Budau <budau.adi@gmail.com>
 */
final class Filesystem implements FilesystemInterface
{
    /**
     * Read a file similar to file_get_contents but throws on failure
     *
     * @param  string $path  File path of the file to be read.
     *
     * @throws IOException   When getting the contents failed
     * @throws IOException   When the file does not exist
     * @throws IOException   When the file is not readable
     *
     * @return string        The file contents
     *
     */
    public function readFile($path)
    {
        $path = $this->resolvePath($path);

        $this->assertExists($path);
        $this->assertIsFile($path);
        $this->assertReadable($path);

        $data = @file_get_contents($path);
        if ($data === false) {
            throw new IOException("Failed to read file '{$path}'", $path);
        }

        return $data;
    }

    /**
     * Atomically write to file
     *
     * @param  string $path  the file to be written to
     * @param  string $data  the data to be written
     *
     * @throws IOException   when the file is not writable
     * @throws IOException   when there are problems with the temporary file
     *
     * @return int           the total number of bytes written
     */
    public function writeFile($path, $data)
    {
        $path = $this->resolvePath($path);
        $this->assertWritableFile($path);

        $dir = dirname($path);

        if (($tmpFile = tempnam($dir, basename($path))) === false) {
            throw new IOException(
                "Could not create temporary file for atomic write on '{$path}'",
                $path
            );
        }

        if (($total = @file_put_contents($tmpFile, $data)) === false) {
            throw new IOException(
                "Could not write to temporary file for atomic write on '{$path}'",
                $path
            );
        }

        $this->rename($tmpFile, $path);
        return $total;
    }

    /**
     * Rename a file or directory
     *
     * @param  string $source       The source file or directory to be renamed
     * @param  string $destination  The new name for the file
     *
     * @throws IOException          when the source file does not exist
     * @throws IOException          when the rename can not be made
     *
     * @return void
     */
    public function rename($source, $destination)
    {
        $source = $this->resolvePath($source);
        $destination = $this->resolvePath($destination);

        $this->assertExists($source);

        if (@rename($source, $destination) === false) {
            throw new IOException(
                "Could not rename file '{$source}' to '{$destination}'",
                $source
            );
        }
    }

    /**
     * Change the permissions of a file or directory
     *
     * @param  string $path   Path to file or directory
     * @param  int    $umask  Permission umask. Note that umask is in octal, so you
     *                        should specify it as, e.g., '0777', not '777'.
     *
     * @throws IOException    when the path does not point to a file or directory
     * @throws IOException    when the chmod fails
     *
     * @return void
     */
    public function chmod($path, $umask)
    {
        $path = $this->resolvePath($path);

        $this->assertExists($path);
        if (!@chmod($path, $umask)) {
            $readable_mask = sprintf('%04o', $umask);
            throw new IOException(
                "Failed to chmod '{$path}' to '{$readable_mask}'",
                $path
            );
        }
    }

    /**
     * Canonicalize a path, resolving it relative to a directory (default PWD)
     *
     * @param  string $path         File path to be resolved.
     * @param  string $relative_to  Directory to which the file is relative to, default PWD.
     *
     * @return string               The resolved file path.
     */
    public function resolvePath($path, $relative_to = null)
    {
        $is_absolute = !strncmp($path, DIRECTORY_SEPARATOR, 1) ||
            preg_match("/^[a-zA-Z][a-zA-Z0-9\-.+]*:\/\//", $path) == 1;

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
    public function assertExists($path)
    {
        if (!$this->pathExists($path)) {
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
    public function assertIsFile($path)
    {
        if (!is_file($path)) {
            throw new IOException(
                "Requested path '{$path}' is not a file.",
                $path
            );
        }
    }

    /**
     * Asserts that the given filepath points to a directory
     *
     * @param  string $path  File path to check
     *
     * @throws IOException   In case filepath doesn't point to a directory
     *
     * @return void
     */
    public function assertIsDirectory($path)
    {
        if (!is_dir($path)) {
            throw new IOException(
                "Request path '{$path}' is not a directory.",
                $path
            );
        }
    }

    /**
     * Asserts the given filepath points to a readable location
     *
     * @param  string $path  File path to check
     *
     * @throws IOException   In case filepath doesn't point to a readable location
     *
     * @return void
     */
    public function assertReadable($path)
    {
        if (!is_readable($path)) {
            throw new IOException(
                "Path '{$path}' is not readable.",
                $path
            );
        }
    }

    /**
     * Asserts the given filepath points to a writable location for a file
     *
     * @param  string $path  File path to check
     *
     * @throws IOException   In case filepath doesn't point to a writable location for a file
     *
     * @return void
     */
    public function assertWritableFile($path)
    {
        $path = $this->resolvePath($path);
        $dir = dirname($path);

        $this->assertExists($dir);
        $this->assertIsDirectory($dir);

        if ($this->pathExists($path)) {
            $this->assertWritable($path);
        } else {
            $this->assertWritable($dir);
        }
    }

    /**
     * Asserts the given filepath points to a writable location
     *
     * @param  string $path File path to check
     *
     * @throws IOException  In case filepath doesn't point to a writable location
     *
     * @return void
     */
    public function assertWritable($path)
    {
        if (!is_writable($path)) {
            throw new IOException(
                "Path '{$path}' is not writable",
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
    public function pathExists($path)
    {
        return file_exists($path) || is_link($path);
    }
}
