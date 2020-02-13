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
        $datetime = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals('DATETIME', $datetime->getFieldSql());
    }

    public function testField(): void
    {
        $datetime = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="datetime-local" name="foo" value="bar" disabled size="10" something>',
            $datetime->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testValue(): void
    {
        $datetime = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '',
            $datetime->value('0000-00-00')
        );

        $this->assertEquals(
            '2019-01-01 12:12:11',
            $datetime->value('2019-01-01 12:12:11')
        );
    }

    public function testFormatValue(): void
    {
        $datetime = new \cms\components\Datetime($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '2019-01-01 12:10:00',
            $datetime->formatValue('2019-01-01 12:10')
        );
    }
}
