<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
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
        $component = new \cms\components\Upload($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="text" name="field-name" class="upload" value="value">',
            $component->field('field-name', 'value')
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Upload($this->cms, $this->auth, $this->vars);

        $this->assertEquals(
            '<img src="/_lib/phpupload/?func=preview&file=some-value&w=320&h=240" id="some-name_thumb"><br><label id="some-name_label">some-value</label>',
            $component->value('some-value', 'some-name')
        );
    }
}
