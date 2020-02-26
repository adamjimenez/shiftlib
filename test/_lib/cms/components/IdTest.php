<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
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

    public function testGetFieldSql(): void
    {
        $component = new \cms\components\Id($this->cms, $this->auth, $this->vars);
        $this->assertNull($component->getFieldSql());
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Id($this->cms, $this->auth, $this->vars);
        $this->assertFalse($component->formatValue('kbjdsakjndsajknjndas'));
    }
}
