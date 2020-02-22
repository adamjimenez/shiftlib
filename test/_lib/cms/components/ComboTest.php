<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ComboTest extends TestCase
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
        $component = new \cms\components\Combo($this->cms, $this->auth, $this->vars);
        $this->assertEquals("VARCHAR( 64 ) NOT NULL DEFAULT ''", $component->getFieldSql());
    }

    public function testField(): void
    {
        $this->cms->expects($this->once())
            ->method('getContent')
            ->willReturn(['my-field_label' => 'my-value']);

        $component = new \cms\components\Combo($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="hidden" name="my-field" value="foo" some-attributes> <input type="text" value="my-value" data-type="combo" data-field="my-field" some-attributes>',
            $component->field('my-field', 'foo', ['readonly', 'attribs' => 'some-attributes'])
        );
    }

    public function testSearchField(): void
    {
        $this->cms->expects($this->once())
            ->method('get_field')
            ->willReturn('<input type="text" placeholder="search" />');

        $component = new \cms\components\Combo($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<div> my-field </div> <input type="text" placeholder="search" /> <br> <br>',
            $component->searchField('my-field', 'foo')
        );
    }
}
