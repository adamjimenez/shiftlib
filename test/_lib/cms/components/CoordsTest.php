<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CoordsTest extends TestCase
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
        $component = new \cms\components\Coords($this->cms, $this->auth, $this->vars);
        $this->assertEquals('POINT', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Coords($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="text" name="my-field" value="" size="50" placeholder="my-placeholder" some-attributes>',
            $component->field('my-field', 'foo', ['readonly', 'placeholder' => 'my-placeholder', 'attribs' => 'some-attributes'])
        );
    }
}
