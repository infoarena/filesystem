<?php

namespace Infoarena\Filesystem;

final class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionCreation()
    {
        $exception = new IOException(
            'Message',
            '/x'
        );

        $this->assertEquals(
            'Message',
            $exception->getMessage()
        );
        $this->assertEquals(
            '/x',
            $exception->getFilepath()
        );
    }
}
