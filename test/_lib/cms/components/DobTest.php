<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DobTest extends TestCase
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
        $component = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals('DATE', $component->getFieldSql());
    }

    public function testField(): void
    {
        $component = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="text" data-type="dob" id="foo" name="foo" value="bar disabled size="10" something>',
            $component->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testValue(): void
    {
        $component = new \cms\components\Dob($this->cms, $this->auth, $this->vars);

        // TODO: I think this functionality is broken, Adam can you check please.
        $this->assertEquals(
            '0000-00-00 ()',
            $component->value('0000-00-00')
        );

        // TODO: I think this functionality is broken, Adam can you check please.
        $this->assertEquals(
            ' ()',
            $component->value('')
        );

        $this->assertEquals(
            '12/05/2019 (0)',
            $component->value('12/05/2019')
        );
    }

    public function testConditionsToSql(): void
    {
        $component = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            "`field-name`!='0000-00-00' AND DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(field-name, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(field-name, '00-%m-%d')) LIKE ' ",
            $component->conditionsToSql('field-name', '12/05/2020')
        );
    }
}
