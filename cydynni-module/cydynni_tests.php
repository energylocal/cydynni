<?php
/** @todo: add Unit Tests
 *  run phpunit on this file (once composer autoloader is set up):
 * ./vendor/bin/phpunit --bootstrap vendor/autoload.php Modules/cydynni/cydynni_tests
 * 
 * https://phpunit.de/getting-started/phpunit-7.html
 * 
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

use PHPUnit\Framework\TestCase;

class CydynniTest extends TestCase{

	public function testGetUser(): void
    {
        $this->assertInstanceOf(
            User::class,
            User::get('1')
		);
	}	
	public function testGetCydynniUser(): void
	{
		$this->assertEquals(
			'anothertoken',
			Cydynni::getUser('1')['token']
		);
	}
}
