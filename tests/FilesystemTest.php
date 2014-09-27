<?php

namespace Infoarena\Filesystem;

function file_get_contents($path)
{
    global $mock_file_get_contents;
    if (isset($mock_file_get_contents)) {
        return false;
    }

    return \file_get_contents($path);
}

final class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveAbsolutePaths()
    {
        $this->assertEquals('/', Filesystem::resolvePath('/'));
        $this->assertEquals('/x/y', Filesystem::resolvePath('/x/y'));
        $this->assertEquals('/x/y/', Filesystem::resolvePath('/x/y/'));
    }

    public function testResolveRelativePaths()
    {
        $this->assertEquals('/x', Filesystem::resolvePath('x', '/'));
        $this->assertEquals('/father/child', Filesystem::resolvePath('child', '/father'));

        chdir(__DIR__);
        $this->assertEquals(
            realpath(__DIR__) . '/FilesystemTest.php',
            Filesystem::resolvePath('FilesystemTest.php')
        );

        symlink(__DIR__, __DIR__ . '/symlink');
        $this->assertEquals(
            realpath(__DIR__) . '/FilesystemTest.php',
            Filesystem::resolvePath('symlink/FilesystemTest.php')
        );
    }

    public function testPathExists()
    {
        $this->assertTrue(Filesystem::pathExists(__DIR__ . '/FilesystemTest.php'));
    }

    public function testAssertExistsFailed()
    {
        Filesystem::assertExists(__DIR__ . '/FilesystemTest.php');
        $path = __DIR__ . '/missing_file';
        $this->setExpectedException(
            'Infoarena\\Filesystem\\FileNotFoundException',
            "Filesystem entity '{$path}' does not exist"
        );
        Filesystem::assertExists($path);
    }

    public function testAssertIsFile()
    {
        Filesystem::assertIsFile(__DIR__ . '/FilesystemTest.php');
        $path = __DIR__;
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Requested path '{$path}' is not a file."
        );
        Filesystem::assertIsFile($path);
    }

    public function testAssertReadable()
    {
        Filesystem::assertReadable(__DIR__ . '/FilesystemTest.php');

        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Path '/random/path' is not readable."
        );

        Filesystem::assertReadable('/random/path');
    }

    public function testReadFile()
    {
        chdir(__DIR__);
        $this->assertEquals(
            '',
            Filesystem::readFile('fixtures/empty_file.txt')
        );

        $this->assertEquals(
            "Non empty\n",
            Filesystem::readFile('fixtures/non_empty_file.txt')
        );

        global $mock_file_get_contents;
        $mock_file_get_contents = true;
        $resolvedPath = Filesystem::resolvePath(__DIR__ . '/fixtures/empty_file.txt');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Failed to read file '{$resolvedPath}'"
        );
        Filesystem::readFile('fixtures/empty_file.txt');
    }

    public function tearDown()
    {
        if (file_exists(__DIR__ . '/symlink')) {
            unlink(__DIR__ . '/symlink');
        }

        global $mock_file_get_contents;
        unset($mock_file_get_contents);
    }
}
