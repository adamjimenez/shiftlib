<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class IpTest extends TestCase
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

    public function testFormatValueWithCmsId(): void
    {
        $this->cms->method('getId')->willReturn(true);

        $datetime = new \cms\components\Ip($this->cms, $this->auth, $this->vars);
        $this->assertFalse($datetime->formatValue('foo'));
    }

    public function testFormatValueWithoutCmsId(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->cms->method('getId')->willReturn(false);

        $datetime = new \cms\components\Ip($this->cms, $this->auth, $this->vars);
        $this->assertEquals($_SERVER['REMOTE_ADDR'], $datetime->formatValue('foo'));
    }
}
