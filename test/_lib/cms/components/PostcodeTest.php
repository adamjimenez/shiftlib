<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PostcodeTest extends TestCase
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

    public function testIsValid(): void
    {
        $component = new \cms\components\Postcode($this->cms, $this->auth, $this->vars);
        $this->assertFalse($component->isValid('THIS IS AN INVALID POSTCODE'));
        $this->assertTrue($component->isValid('LE1 2QR'));
    }

    public function testFormatValue(): void
    {
        $component = new \cms\components\Postcode($this->cms, $this->auth, $this->vars);
        $this->assertEquals('LE1 2QR', $component->formatValue('le12qr'));
    }

    public function testSearchField(): void
    {
        $component = new \cms\components\Postcode($this->cms, $this->auth, $this->vars);
        $this->assertEquals(
            'Distance from field-name<br> Within <select name="func[field-name]"> <option value=""></option> <option label="3 miles" value="3">3 miles</option><option label="10 miles" value="10">10 miles</option><option label="15 miles" value="15">15 miles</option><option label="20 miles" value="20">20 miles</option><option label="30 miles" value="30">30 miles</option><option label="40 miles" value="40">40 miles</option><option label="50 miles" value="50">50 miles</option><option label="75 miles" value="75">75 miles</option><option label="100 miles" value="100">100 miles</option><option label="150 miles" value="150">150 miles</option><option label="200 miles" value="200">200 miles</option> </select> of <input type="text" name="field-name" value="" size="7">',
            $component->searchField('field-name', '')
        );
    }
}
