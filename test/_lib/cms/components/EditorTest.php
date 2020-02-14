<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class EditorTest extends TestCase
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
        $datetime = new \cms\components\Editor($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TEXT', $datetime->getFieldSql());
    }

    public function testField(): void
    {
        $datetime = new \cms\components\Editor($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<textarea name="foo" disabled something rows="25" style="width:100%; height: 400px;" data-type="tinymce">bar</textarea>',
            $datetime->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testFormatValue(): void
    {
        $datetime = new \cms\components\Editor($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<p><b>This</b> is an example</p>',
            $datetime->formatValue('<html><p><b>This</b> is an example</p></html>')
        );
    }

    public function testConditionsToSql(): void
    {
        // TODO: This uses mysqli so the value isn't being substituted in properly
        $datetime = new \cms\components\Editor($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            "table-prefixfield-name LIKE ''",
            $datetime->conditionsToSql('field-name', 'value', '', 'table-prefix')
        );
    }
}
