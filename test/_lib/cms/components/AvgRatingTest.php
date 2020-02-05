<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AvgRatingTest extends TestCase
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
        $component = new \cms\components\AvgRating($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TINYINT', $component->getFieldSql());
    }

    public function testField(): void
    {
        $this->cms->expects($this->once())
            ->method('getSection')
            ->willReturn('my-section');

        $this->cms->expects($this->once())
            ->method('getContent')
            ->willReturn(['id' => 'some-id']);

        $component = new \cms\components\AvgRating($this->cms, $this->auth, $this->vars);
        $output = $component->field('foo', 'bar', ['attribs' => 'my custom attribs']);

        $this->assertEquals(
            '<select name="foo" class="rating" data-section="my-section" data-item="some-id" data-avg="data-avg" my custom attribs><option value="">Choose</option><option label="Very Poor" value="1">Very Poor</option><option label="Poor" value="2">Poor</option><option label="Average" value="3">Average</option><option label="Good" value="4">Good</option><option label="Excellent" value="5">Excellent</option></select>',
            $output
        );
    }
}
