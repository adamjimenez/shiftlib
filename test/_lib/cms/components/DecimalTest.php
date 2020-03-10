<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
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
        $component = new \cms\components\Decimal($this->cms, $this->auth, $this->vars);
        $this->assertEquals('DECIMAL( 8,2 )', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Decimal($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="number" name="foo" value="bar" disabled  something>',
            $component->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Decimal($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '',
            $component->value(-1.00)
        );

        $this->assertEquals(
            '2.00',
            $component->value(2)
        );

        $this->assertEquals(
            '',
            $component->value('')
        );
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Decimal($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '2',
            $component->formatValue(' 2 ')
        );
    }
}
