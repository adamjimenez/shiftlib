<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
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
        $component = new \cms\components\Password($this->cms, $this->auth, $this->vars);
        $this->assertEquals('<input type="password" name="field-name" value="" >', $component->field('field-name', 'value'));
    }

    public function testValue(): void
    {
        $component = new \cms\components\Password($this->cms, $this->auth, $this->vars);
        $this->assertEquals('', $component->value('Super Secret Password'));
    }

    public function testFormatValue(): void
    {
        $this->auth->method('shouldHashPassword')->willReturn(true);
        $this->auth->method('create_hash')->willReturn('hashed-password');

        $component = new \cms\components\Password($this->cms, $this->auth, $this->vars);
        $this->assertEquals('hashed-password', $component->formatValue('password'));
    }

    public function testFormatValueWithoutHash(): void
    {
        $this->auth->method('shouldHashPassword')->willReturn(false);

        $component = new \cms\components\Password($this->cms, $this->auth, $this->vars);
        $this->assertEquals('plaintext', $component->formatValue('plaintext'));
    }
}
