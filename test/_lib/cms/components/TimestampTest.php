<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
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
        $component = new \cms\components\Timestamp($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $component->getFieldSql());
    }

    public function testIsValid(): void
    {
        $component = new \cms\components\Timestamp($this->cms, $this->auth, $this->vars);

        $this->assertTrue($component->isValid((string) time()));
        $this->assertFalse($component->isValid(PHP_INT_MAX + 1));
        $this->assertFalse($component->isValid(PHP_INT_MAX));
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Timestamp($this->cms, $this->auth, $this->vars);

        $this->assertFalse($component->formatValue(time()));
    }
}
