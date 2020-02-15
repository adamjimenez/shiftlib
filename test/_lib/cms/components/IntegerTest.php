<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class IntegerTest extends TestCase
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
        $datetime = new \cms\components\Integer($this->cms, $this->auth, $this->vars);
        $this->assertEquals('INT', $datetime->getFieldSql());
    }

    public function testValue(): void
    {
        $datetime = new \cms\components\Integer($this->cms, $this->auth, $this->vars);
        $this->assertEquals('1,000', $datetime->value(1000));
        $this->assertEquals('1,000,000', $datetime->value(1000000));
    }

    public function testIsValid(): void
    {
        $datetime = new \cms\components\Integer($this->cms, $this->auth, $this->vars);
        $this->assertTrue($datetime->isValid('1000'));
        $this->assertFalse($datetime->isValid('sdsadasdsa'));
    }

    public function testFormatValue(): void
    {
        $datetime = new \cms\components\Integer($this->cms, $this->auth, $this->vars);
        $this->assertEquals(1000, $datetime->formatValue('1000'));
    }

    public function testSearchField(): void
    {
        $datetime = new \cms\components\Integer($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            '<label>Search-field</label><br> <div> <div style="float:left"> <select name="func[search-field]" class="form-control"> <option value=""></option> <option label="=" value="=">=</option><option label="!=" value="!=">!=</option><option label="&gt;" value="&gt;">></option><option label="&lt;" value="&lt;"><</option> </select> </div> <div style="float:left"> <input type="number" id="search-field" name="search-field" value="" size="8" class="form-control"> </div> <br style="clear: both;"> </div>',
            $datetime->searchField('search-field', 1000)
        );
    }
}
