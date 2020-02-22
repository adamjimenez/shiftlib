<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DatetimeTest extends TestCase
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
        $component = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals('DATETIME', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="datetime-local" name="foo" value="bar" disabled size="10" something>',
            $component->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '',
            $component->value('0000-00-00')
        );

        $this->assertEquals(
            '2019-01-01 12:12:11',
            $component->value('2019-01-01 12:12:11')
        );
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '2019-01-01 12:10:00',
            $component->formatValue('2019-01-01 12:10')
        );
    }

    public function testIsValid(): void
    {
        $component = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertTrue(
            $component->isValid('2019-01-01 12:10')
        );
    }
}
