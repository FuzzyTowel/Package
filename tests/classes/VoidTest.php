<?php

class VoidTest extends PHPUnit_Framework_TestCase
{
	public function testForge()
	{
		$this->assertInstanceOf('Foolz\Package\FoolVoid', \Foolz\Package\FoolVoid::forge());
	}
}