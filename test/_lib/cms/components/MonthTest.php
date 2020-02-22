<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MonthTest extends TestCase
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

    public function testField(): void
    {
        $component = new \cms\components\Month($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="text" class="month" id="field-name" name="field-name" value="my-value" disabled size="10" foo-bar style="width:75px;" />',
            $component->field('field-name', 'my-value', ['readonly' => true, 'attribs' => 'foo-bar'])
        );

        $this->assertEquals(
            '<input type="text" class="month" id="field-name" name="field-name" value="my-value" size="10"  style="width:75px;" />',
            $component->field('field-name', 'my-value', ['readonly' => false])
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Month($this->cms, $this->auth, $this->vars);
        $this->assertEquals('December 2019', $component->value('2019-12-25'));

        $this->assertEquals('0000-00-00', $component->value('0000-00-00'));
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Month($this->cms, $this->auth, $this->vars);
        $this->assertEquals('2019-12-01', $component->formatValue('2019-12'));
    }

    public function testIsValid(): void
    {
        $component = new \cms\components\Month($this->cms, $this->auth, $this->vars);
        $this->assertTrue($component->isValid('2019-12'));
    }
}
