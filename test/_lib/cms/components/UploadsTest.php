<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UploadsTest extends TestCase
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
        $component = new \cms\components\Uploads($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TEXT', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Uploads($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<textarea name="field-name" class="upload">value</textarea>',
            $component->field('field-name', 'value')
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Uploads($this->cms, $this->auth, $this->vars);
        $output = $component->value("foo\nbar\nbaz", 'some-name');

        $this->assertEquals(
            '<ul id="some-name_files"><li id="item_some-name_1"><img src="/_lib/phpupload/?func=preview&file=foo" id="file_some-name_1_thumb"><br><label id="file_some-name_1_label">foo</label></li><li id="item_some-name_2"><img src="/_lib/phpupload/?func=preview&file=bar" id="file_some-name_2_thumb"><br><label id="file_some-name_2_label">bar</label></li><li id="item_some-name_3"><img src="/_lib/phpupload/?func=preview&file=baz" id="file_some-name_3_thumb"><br><label id="file_some-name_3_label">baz</label></li></ul>',
            $output
        );
    }
}
