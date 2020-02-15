<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
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

    public function testValue(): void
    {
        $datetime = new \cms\components\Email($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<a href="mailto:foo@bar.com" target="_blank">foo@bar.com</a>',
            $datetime->value('foo@bar.com')
        );
    }

    public function testIsValid(): void
    {
        $datetime = new \cms\components\Email($this->cms, $this->auth, $this->vars);
        $this->assertTrue($datetime->is_valid('foo@bar.com'));
        $this->assertFalse($datetime->is_valid('foo@bar'));
    }
}
