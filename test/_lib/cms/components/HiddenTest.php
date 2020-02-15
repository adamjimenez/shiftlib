<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HiddenTest extends TestCase
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
        $datetime = new \cms\components\Hidden($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '0000-00-00 00:00:00',
            $datetime->value('0000-00-00 00:00:00')
        );
        $this->assertEquals(
            '01/01/2019 12:00:00',
            $datetime->value('2019-01-01 12:00:00')
        );
    }
}
