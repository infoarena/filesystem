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
    private $filesystem;

    public function testResolveAbsolutePaths()
    {
        $this->assertEquals(vfsStream::url('root'), $this->filesystem->resolvePath(vfsStream::url('root')));
        $this->assertEquals(vfsStream::url('x/y'), $this->filesystem->resolvePath(vfsStream::url('x/y')));
        $this->assertEquals(vfsStream::url('x/y/'), $this->filesystem->resolvePath(vfsStream::url('x/y/')));
    }

    public function testResolveRelativePaths()
    {
        $this->assertEquals(vfsStream::url("x/y"), $this->filesystem->resolvePath('y', vfsStream::url('x')));
        $this->assertEquals(vfsStream::url("x/y"), $this->filesystem->resolvePath('y', vfsStream::url('x/')));
        $this->assertEquals(vfsStream::url("root/x"), $this->filesystem->resolvePath('x'));

    }

    public function testPathExists()
    {
        $this->assertTrue($this->filesystem->pathExists($this->filesystem->resolvePath("existingFile")));
    }

    public function testAssertExists()
    {
        $this->filesystem->assertExists($this->filesystem->resolvePath("existingFile"));
        $path = $this->filesystem->resolvePath("nonExistingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\FileNotFoundException',
            "Filesystem entity '{$path}' does not exist"
        );
        $this->filesystem->assertExists($path);
    }

    public function testAssertIsFile()
    {
        $this->filesystem->assertIsFile($this->filesystem->resolvePath("existingFile"));
        $path = $this->filesystem->resolvePath("");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Requested path '{$path}' is not a file."
        );
        $this->filesystem->assertIsFile($path);
    }

    public function testAssertIsDirectory()
    {
        $this->filesystem->assertIsDirectory($this->filesystem->resolvePath(""));

        $path = $this->filesystem->resolvePath("existingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Request path '{$path}' is not a directory."
        );

        $this->filesystem->assertIsDirectory($path);
    }

    public function testAssertReadable()
    {
        $this->filesystem->assertReadable($this->filesystem->resolvePath("existingFile"));

        $path = $this->filesystem->resolvePath("nonExistingFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Path '{$path}' is not readable."
        );

        $this->filesystem->assertReadable($path);
    }

    public function testAssertWritable()
    {
        $this->filesystem->assertWritable($this->filesystem->resolvePath("existingFile"));

        $path = $this->filesystem->resolvePath("nonWritableFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Path '{$path}' is not writable"
        );
        $this->filesystem->assertWritable($path);
    }

    public function testAssertWritableFile()
    {
        $this->filesystem->assertWritableFile($this->filesystem->resolvePath("existingFile"));
        $this->filesystem->assertWritableFile($this->filesystem->resolvePath("subfolder"));
        $this->filesystem->assertWritableFile($this->filesystem->resolvePath("nonExistingFile"));
        $path = $this->filesystem->resolvePath("nonWritableFile");
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException'
        );
        $this->filesystem->assertWritableFile($path);
    }

    public function testReadFile()
    {
        chdir(__DIR__);
        $this->assertEquals(
            '',
            $this->filesystem->readFile('emptyFile')
        );

        $this->assertEquals(
            "Non empty\n",
            $this->filesystem->readFile('nonEmptyFile')
        );
    }

    public function testReadErrorFile()
    {
        $path = $this->filesystem->resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Failed to read file '{$path}'"
        );
        $this->filesystem->readFile('errorFile');
    }

    public function testWriteFile()
    {
        $this->assertEquals(
            5,
            $this->filesystem->writeFile($this->filesystem->resolvePath("file"), 'AAAAA')
        );

        $path = $this->filesystem->resolvePath('nonWritableFolder/writableFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not create temporary file for atomic write on '{$path}'"
        );
        $this->filesystem->writeFile('nonWritableFolder/writableFile', '');
    }

    public function testWriteErrorFile()
    {
        $path = $this->filesystem->resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not write to temporary file for atomic write on '{$path}'"
        );
        $this->filesystem->writeFile('errorFile', '');
    }

    public function testRename()
    {
        $this->filesystem->rename('emptyFile', 'nonEmptyFile');
        $this->filesystem->rename('nonEmptyFile', 'newFile');

        $source = $this->filesystem->resolvePath('newFile');
        $destination = $this->filesystem->resolvePath('');

        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Could not rename file '{$source}' to '{$destination}'"
        );
        $this->filesystem->rename('newFile', '');
    }

    public function testChmod()
    {
        $this->filesystem->chmod('emptyFile', 0567);
        $this->assertEquals(0567, fileperms($this->filesystem->resolvePath('emptyFile')) & 0777);

        $path = $this->filesystem->resolvePath('errorFile');
        $this->setExpectedException(
            'Infoarena\\Filesystem\\IOException',
            "Failed to chmod '{$path}' to '0111'"
        );
        $this->filesystem->chmod('errorFile', 0111);
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

        $this->filesystem = new Filesystem;
    }
}
