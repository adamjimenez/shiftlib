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
        $datetime = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals('DATE', $datetime->getFieldSql());
    }

    public function testField(): void
    {
        $datetime = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="text" data-type="dob" id="foo" name="foo" value="bar disabled size="10" something>',
            $datetime->field('foo', 'bar', ['readonly' => true, 'attribs' => 'something'])
        );
    }

    public function testValue(): void
    {
        $datetime = new \cms\components\Dob($this->cms, $this->auth, $this->vars);

        // TODO: I think this functionality is broken, Adam can you check please.
        $this->assertEquals(
            '0000-00-00 ()',
            $datetime->value('0000-00-00')
        );

        // TODO: I think this functionality is broken, Adam can you check please.
        $this->assertEquals(
            ' ()',
            $datetime->value('')
        );

        $this->assertEquals(
            '12/05/2019 (0)',
            $datetime->value('12/05/2019')
        );
    }

    public function testConditionsToSql(): void
    {
        $datetime = new \cms\components\Dob($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            "`field-name`!='0000-00-00' AND DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(field-name, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(field-name, '00-%m-%d')) LIKE ' ",
            $datetime->conditionsToSql('field-name', '12/05/2020')
        );
    }
}
