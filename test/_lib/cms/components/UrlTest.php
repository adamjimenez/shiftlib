<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @var cms|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cms;

    /**
     * @var auth|\PHPUnit\Framework\MockObject\MockObject
     */
    private $auth;

    /**
     * @var array
     */
    private $vars;

    protected function setUp(): void
    {
        $this->cms = $this->createMock(\cms::class);
        $this->auth = $this->createMock(\auth::class);
        $this->vars = [];
    }

    public function testValue(): void
    {
        $component = new \cms\components\Url($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<a href="http://www.google.com/" target="_blank">http://www.google.com/</a>',
            $component->value('http://www.google.com/', '')
        );
    }

    public function testIsValid(): void
    {
        $component = new \cms\components\Url($this->cms, $this->auth, $this->vars);

        $this->assertTrue($component->isValid('http://www.google.com/'));
        $this->assertFalse($component->isValid('foo-bar'));
    }
}
