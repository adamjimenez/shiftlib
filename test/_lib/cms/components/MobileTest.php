<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MobileTest extends TestCase
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

    public function testIsValid(): void
    {
        $component = new \cms\components\Mobile($this->cms, $this->auth, $this->vars);
        $this->assertTrue($component->isValid('+4407970557211'));
        $this->assertFalse($component->isValid('foo'));
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Mobile($this->cms, $this->auth, $this->vars);
        $this->assertEquals('4407970557211', $component->formatValue('+44 07970557211'));
    }
}
