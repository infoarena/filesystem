<?php

namespace Infoarena\Filesystem;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

function getcwd()
{
    return vfsStream::url('root');
}

function realpath($path)
{
    if (substr($path, 0, 1) != '/' && preg_match("/^[a-zA-Z][a-zA-Z0-9\-.+]*:\/\/[^\/]*/", $path) != 1) {
        $path = getcwd() . $path;
    }


    if (!file_exists($path)) {
        return false;
    }

    return $path;
}

function file_get_contents($path)
{
    if ($path == vfsStream::url('root/errorFile')) {
        return false;
    }
    return \file_get_contents($path);
}

function file_put_contents($path, $contents)
{
    $pattern = vfsStream::url('root/errorFile');
    if (!strncmp($path, $pattern, strlen($pattern))) {
        return false;
    }

    return \file_put_contents($path, $contents);
}

function tempnam($directory, $prefix)
{
    $file_name = realpath($directory) . '/' . $prefix . uniqid();
    if (@\file_put_contents($file_name, '') === false) {
        return false;
    }

    return $file_name;
}

function chmod($file, $umask)
{
    if ($file == vfsStream::url('root/errorFile')) {
        return false;
    }
    return \chmod($file, $umask);
}

final class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    private $vfs;

    public function testResolveAbsolutePaths()
    {
        $this->assertEquals(vfsStream::url('root'), Filesystem::resolvePath(vfsStream::url('root')));
        $this->assertEquals(vfsStream::url('x/y'), Filesystem::resolvePath(vfsStream::url('x/y')));
        $this->assertEquals(vfsStream::url('x/y/'), Filesystem::resolvePath(vfsStream::url('x/y/')));
    }

    public function testResolveRelativePaths()
    {
        $this->assertEquals(vfsStream::url("x/y"), Filesystem::resolvePath('y', vfsStream::url('x')));
        $this->assertEquals(vfsStream::url("x/y"), Filesystem::resolvePath('y', vfsStream::url('x/')));
        $this->assertEquals(vfsStream::url("root/x"), Filesystem::resolvePath('x'));

    }

    public function testPathExists()
    {
        $this->assertTrue(Filesystem::pathExists(Filesystem::resolvePath("existingFile")));
    }

    public function testAssertExists()
    {
        Filesystem::assertExists(Filesystem::resolvePath("existingFile"));
        $path = Filesystem::resolvePath("nonExistingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\FileNotFoundException',
            "Filesystem entity '{$path}' does not exist"
        );
        Filesystem::assertExists($path);
    }

    public function testAssertIsFile()
    {
        Filesystem::assertIsFile(Filesystem::resolvePath("existingFile"));
        $path = Filesystem::resolvePath("");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Requested path '{$path}' is not a file."
        );
        Filesystem::assertIsFile($path);
    }

    public function testAssertIsDirectory()
    {
        Filesystem::assertIsDirectory(Filesystem::resolvePath(""));

        $path = Filesystem::resolvePath("existingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Request path '{$path}' is not a directory."
        );

        Filesystem::assertIsDirectory($path);
    }

    public function testAssertReadable()
    {
        Filesystem::assertReadable(Filesystem::resolvePath("existingFile"));

        $path = Filesystem::resolvePath("nonExistingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Path '{$path}' is not readable."
        );

        Filesystem::assertReadable($path);
    }

    public function testAssertWritable()
    {
        Filesystem::assertWritable(Filesystem::resolvePath("existingFile"));

        $path = Filesystem::resolvePath("nonWritableFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Path '{$path}' is not writable"
        );
        Filesystem::assertWritable($path);
    }

    public function testAssertWritableFile()
    {
        Filesystem::assertWritableFile(Filesystem::resolvePath("existingFile"));
        Filesystem::assertWritableFile(Filesystem::resolvePath("subfolder"));
        Filesystem::assertWritableFile(Filesystem::resolvePath("nonExistingFile"));
        $path = Filesystem::resolvePath("nonWritableFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException'
        );
        Filesystem::assertWritableFile($path);
    }

    public function testReadFile()
    {
        chdir(__DIR__);
        $this->assertEquals(
            '',
            Filesystem::readFile('emptyFile')
        );

        $this->assertEquals(
            "Non empty\n",
            Filesystem::readFile('nonEmptyFile')
        );
    }

    public function testReadErrorFile()
    {
        $path = Filesystem::resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Failed to read file '{$path}'"
        );
        Filesystem::readFile('errorFile');
    }

    public function testWriteFile()
    {
        $this->assertEquals(
            5,
            Filesystem::writeFile(Filesystem::resolvePath("file"), 'AAAAA')
        );

        $path = Filesystem::resolvePath('nonWritableFolder/writableFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not create temporary file for atomic write on '{$path}'"
        );
        Filesystem::writeFile('nonWritableFolder/writableFile', '');
    }

    public function testWriteErrorFile()
    {
        $path = Filesystem::resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not write to temporary file for atomic write on '{$path}'"
        );
        Filesystem::writeFile('errorFile', '');
    }

    public function testRename()
    {
        Filesystem::rename('emptyFile', 'nonEmptyFile');
        Filesystem::rename('nonEmptyFile', 'newFile');

        $source = Filesystem::resolvePath('newFile');
        $destination = Filesystem::resolvePath('');

        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not rename file '{$source}' to '{$destination}'"
        );
        Filesystem::rename('newFile', '');
    }

    public function testChmod()
    {
        Filesystem::chmod('emptyFile', 0567);
        $this->assertEquals(0567, fileperms(Filesystem::resolvePath('emptyFile')) & 0777);

        $path = Filesystem::resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Failed to chmod '{$path}' to '0111'"
        );
        Filesystem::chmod('errorFile', 0111);
    }

    public function setUp()
    {
        $this->vfs = vfsStream::setUp('root');
        vfsStream::create(array(
            'existingFile' => '',
            'nonWritableFile' => '',
            'emptyFile' => '',
            'nonEmptyFile' => "Non empty\n",
            'subfolder' => array(),
            'errorFile' => 'file',
            'nonWritableFolder' => array(
                'writableFile' => ''
            )
        ), $this->vfs);

        chmod(vfsStream::url('root/nonWritableFile'), 0555);
        chmod(vfsStream::url('root/nonWritableFolder'), 0444);
        chmod(vfsStream::url('root/nonWritableFolder/writableFile'), 0777);
    }
}
