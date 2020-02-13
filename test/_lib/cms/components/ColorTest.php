<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
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
        $color = new \cms\components\Color($this->cms, $this->auth, $this->vars);
        $this->assertEquals("VARCHAR( 7 ) NOT NULL DEFAULT ''", $color->getFieldSql());
    }
}
