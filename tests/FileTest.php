<?php

use Sprockets\File;

class FileTest extends PHPUnit_Framework_TestCase {

	public function testGetRetrievesFiles()
	{
		$testFile = __DIR__.'file.txt';
		file_put_contents($testFile, 'Hello World!');

		$file = new File($testFile);
		$this->assertEquals('Hello World!', $file->get());

		@unlink($testFile);
	}

	public function testPutStoresFiles()
	{
		$testFile = __DIR__.'file.txt';

		$file = new File($testFile);
		$file->put('Hello World!');

		$this->assertEquals('Hello World!', file_get_contents($testFile));

		@unlink($testFile);
	}

	public function testPutRespectsFileMode()
	{
		$testFile = __DIR__.'file.txt';

		$file = new File($testFile);
		$file->put('Hello World!', 0777);
		$this->assertEquals('0777', substr(sprintf('%o', fileperms($testFile)), -4));

		$file->put('Hello World!');
		$this->assertEquals('0666', substr(sprintf('%o', fileperms($testFile)), -4));

		@unlink($testFile);
	}

}