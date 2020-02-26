<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RatingTest extends TestCase
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
        $component = new \cms\components\Rating($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TINYINT', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Rating($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<select name="field-name" class="rating" > <option value="">Choose</option> <option label="Very Poor" value="1">Very Poor</option><option label="Poor" value="2">Poor</option><option label="Average" value="3">Average</option><option label="Good" value="4">Good</option><option label="Excellent" value="5">Excellent</option> </select>',
            $component->field('field-name', 'value')
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Rating($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<select name="field-name" class="rating" disabled="disabled"><option value="">Choose</option><option label="Very Poor" value="1">Very Poor</option><option label="Poor" value="2">Poor</option><option label="Average" value="3">Average</option><option label="Good" value="4">Good</option><option label="Excellent" value="5">Excellent</option></select>',
            $component->value('value', 'field-name')
        );
    }

    public function testSearchField(): void
    {
        $component = new \cms\components\Rating($this->cms, $this->auth, $this->vars);
        $this->assertEquals('', $component->searchField('value', 'field-name'));
    }
}
