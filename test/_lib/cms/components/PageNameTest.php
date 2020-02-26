<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PageNameTest extends TestCase
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

    public function testFormatValue(): void
    {
        $component = new \cms\components\PageName($this->cms, $this->auth, $this->vars);
        $this->assertEquals('my-new-page-name', $component->formatValue('my new page name'));

        // TODO: Does this need fixing?
        $this->assertEquals('this--is-a-test/ofstuff', $component->formatValue('this--is-a-test/of=stuff'));
    }
}
