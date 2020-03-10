<?php declare(strict_types=1);

use cms\components\Checkbox;
use PHPUnit\Framework\TestCase;

class CheckboxTest extends TestCase
{
    /**
     * @var \cms|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cms;

    /**
     * @var \auth|\PHPUnit\Framework\MockObject\MockObject
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

    public function testValue()
    {
        $component = new Checkbox($this->cms, $this->auth, $this->vars);
        $this->assertEquals('Yes', $component->value(true));
        $this->assertEquals('No', $component->value(false));
    }

    public function testGetFieldSql()
    {
        $component = new Checkbox($this->cms, $this->auth, $this->vars);
        $this->assertEquals('TINYINT', $component->getFieldSql());
    }

    public function testConditionsToSql()
    {
        $component = new Checkbox($this->cms, $this->auth, $this->vars);
        $this->assertEquals('table-prefixmy-field LIKE \'\'', $component->conditionsToSql('my-field', 'my-value', '', 'table-prefix'));
    }

    public function testSearchField()
    {
        $component = new Checkbox($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<div><label for="my-field" class="col-form-label">My-field</label><br><select name="my-field" class="form-control"><option value=""></option><option label="Yes" value="1">Yes</option><option label="No" value="0">No</option></select><br><br></div>',
            $component->searchField('my-field', 'my-value', '', 'table-prefix')
        );
    }

    public function testField()
    {
        $component = new Checkbox($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<input type="checkbox" name="field-foo" value="1" checked some-options>',
            $component->field('field-foo', true, ['attribs' => 'some-options'])
        );

        $this->assertEquals(
            '<input type="checkbox" name="field-foo" value="1" some-options>',
            $component->field('field-foo', false, ['attribs' => 'some-options'])
        );
    }
}
