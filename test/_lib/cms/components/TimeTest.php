<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
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
        $component = new \cms\components\Time($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TIME', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Time($this->cms, $this->auth, $this->vars);

        $this->assertEquals(
            '<input type="time" step="1" data-type="time" id="field-name" name="field-name" value="" disabled foo-bar/>',
            $component->field('field-name', '00:00:00', ['readonly' => true, 'attribs' => 'foo-bar'])
        );

        $this->assertEquals(
            '<input type="time" step="1" data-type="time" id="field-name" name="field-name" value="10:00" disabled foo-bar/>',
            $component->field('field-name', '10:00:00', ['readonly' => true, 'attribs' => 'foo-bar'])
        );
    }
}
