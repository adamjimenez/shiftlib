<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TextareaTest extends TestCase
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
        $component = new \cms\components\Textarea($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<textarea name="field-name"  disabled  placeholder="Enter Text" foo-bar >value</textarea>',
            $component->field('field-name', 'value', ['readonly' => true, 'placeholder' => 'Enter Text', 'attribs' => 'foo-bar'])
        );
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Textarea($this->cms, $this->auth, $this->vars);
        $this->assertEquals('value', $component->formatValue(' value<b></b>', 'field-name'));
        $this->assertEquals('value<iframe>', $component->formatValue(' value<b></b><iframe>', 'field-name'));
    }
}
