<?php

namespace Nathanmac\Utilities\Parser\Tests;

use \Mockery as m;
use Nathanmac\Utilities\Parser\Parser;
use PHPUnit\Framework\TestCase;

class XMLTest extends TestCase
{
    /**
     * Tear down after tests
     */
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_null_values_for_empty_values()
    {
        $parser = m::mock('Nathanmac\Utilities\Parser\Parser')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $parser->shouldReceive('getPayload')
            ->once()
            ->andReturn('<xml><comments><title></title><message>hello world</message></comments><comments><title>world</title><message></message></comments></xml>');

        $this->assertEquals(["comments" => [["title" => null, "message" => "hello world"], ["title" => "world", "message" => null]]], $parser->payload('application/xml'));
    }

    public function test_array_structured_getPayload_xml()
    {
        $parser = m::mock('Nathanmac\Utilities\Parser\Parser')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $parser->shouldReceive('getPayload')
            ->once()
            ->andReturn('<xml><comments><title>hello</title><message>hello world</message></comments><comments><title>world</title><message>hello world</message></comments></xml>');

        $this->assertEquals(["comments" => [["title" => "hello", "message" => "hello world"], ["title" => "world", "message" => "hello world"]]], $parser->payload('application/xml'));
    }

    public function test_parse_auto_detect_xml_data()
    {
        $parser = m::mock('Nathanmac\Utilities\Parser\Parser')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $parser->shouldReceive('getFormatClass')
            ->once()
            ->andReturn('Nathanmac\Utilities\Parser\Formats\XML');

        $parser->shouldReceive('getPayload')
            ->once()
            ->andReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml><status>123</status><message>hello world</message></xml>");

        $this->assertEquals(['status' => 123, 'message' => 'hello world'], $parser->payload());
    }

    public function test_parse_auto_detect_xml_data_define_content_type_as_param()
    {
        $parser = m::mock('Nathanmac\Utilities\Parser\Parser')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $parser->shouldReceive('getPayload')
            ->once()
            ->andReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml><status>123</status><message>hello world</message></xml>");

        $this->assertEquals(['status' => 123, 'message' => 'hello world'], $parser->payload('application/xml'));
    }

    public function test_parser_validates_xml_data()
    {
        $parser = new Parser();
        $this->assertEquals(['status' => 123, 'message' => 'hello world'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml><status>123</status><message>hello world</message></xml>"));
    }

    public function test_parser_validates_xml_data_with_attribute()
    {
        $parser = new Parser();
        $this->assertEquals(['status' => 123, 'message' => 'hello world', '@name' => 'root'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\"><status>123</status><message>hello world</message></xml>"));
    }

    public function test_parser_validates_xml_data_with_namespace()
    {
        $parser = new Parser();
        $this->assertEquals(['status' => 123, 'ns:message' => 'hello world'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml xmlns:ns=\"data:namespace\"><status>123</status><ns:message>hello world</ns:message></xml>"));
    }

    public function test_parser_validates_xml_data_with_attribute_and_namespace()
    {
        $parser = new Parser();
        $this->assertEquals(['status' => 123, 'ns:message' => 'hello world', '@name' => 'root'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\" xmlns:ns=\"data:namespace\"><status>123</status><ns:message>hello world</ns:message></xml>"));
    }

    public function test_parser_empty_xml_data()
    {
        $parser = new Parser();
        $this->assertEquals([], $parser->xml(""));
    }

    public function test_throws_an_exception_when_parsed_xml_bad_data()
    {
        $parser = new Parser();
        $this->expectException('Exception');
        $this->expectExceptionMessage('Failed To Parse XML');
        $parser->xml('as|df>ASFBw924hg2=');
    }

    public function test_format_detection_xml()
    {
        $parser = new Parser();

        $_SERVER['HTTP_CONTENT_TYPE'] = "application/xml";
        $this->assertEquals('Nathanmac\Utilities\Parser\Formats\XML', $parser->getFormatClass());

        $_SERVER['HTTP_CONTENT_TYPE'] = "application/xml; charset=utf8";
        $this->assertEquals('Nathanmac\Utilities\Parser\Formats\XML', $parser->getFormatClass());

        $_SERVER['HTTP_CONTENT_TYPE'] = "charset=utf8; application/xml";
        $this->assertEquals('Nathanmac\Utilities\Parser\Formats\XML', $parser->getFormatClass());

        $_SERVER['HTTP_CONTENT_TYPE'] = "APPLICATION/XML";
        $this->assertEquals('Nathanmac\Utilities\Parser\Formats\XML', $parser->getFormatClass());

        $_SERVER['HTTP_CONTENT_TYPE'] = "text/xml";
        $this->assertEquals('Nathanmac\Utilities\Parser\Formats\XML', $parser->getFormatClass());

        unset($_SERVER['HTTP_CONTENT_TYPE']);
    }

    public function test_parser_validates_xml_with_spaces_and_new_lines()
    {
        $parser = new Parser();
        $this->assertEquals(['status' => 123, 'message' => 'hello world', '@name' => 'root'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?> <xml name=\"root\"> \n <status>123</status> <message>hello world</message></xml>"));
    }

    public function test_parser_validates_xml_with_attributes()
    {
        $parser = new Parser();
        $this->assertEquals(['@name' => 'root', '@status' => 'active', '#text' => 'some value'], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\" status=\"active\">some value</xml>"));
    }

    public function test_parser_validates_complex_xml_tree()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <Books>
                <Book id="2">
                    <Author id="18">Author #1</Author>
                    <Title>Book #1</Title>
                </Book>
                <Book id="3">
                    <Author id="180">Author #2</Author>
                    <Title>Book #2</Title>
                </Book>
                <Book id="4">
                    <Author id="18">Author #1</Author>
                    <Title>Book #3</Title>
                </Book>
            </Books>';
        $parser = new Parser();
        $this->assertEquals(
            ['Book' => [
                ['@id' => '2', 'Author' => ['@id' => 18,    '#text' => 'Author #1'], 'Title' => 'Book #1'],
                ['@id' => '3', 'Author' => ['@id' => 180,   '#text' => 'Author #2'], 'Title' => 'Book #2'],
                ['@id' => '4', 'Author' => ['@id' => 18,    '#text' => 'Author #1'], 'Title' => 'Book #3'],
            ],],
            $parser->xml($xml)
        );
    }

    public function test_parser_validates_xml_data_with_empty_values()
    {
        $parser = new Parser();
        $this->assertEquals(['@name' => 'root', 'd' => [null, '1', '2']], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\"><d></d><d>1</d><d>2</d></xml>"));
    }

    public function test_parser_validates_xml_data_with_many_empty_values()
    {
        $parser = new Parser();
        $this->assertEquals(['@name' => 'root', 'd' => [null, null, '2', null]], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\"><d></d><d></d><d>2</d><d></d></xml>"));
    }

    public function test_parser_validates_xml_empty_values_with_spaces()
    {
        $parser = new Parser();
        $this->assertEquals(['@name' => 'root', 'test' => [null, '  x  ', null]], $parser->xml("<?xml version=\"1.0\" encoding=\"UTF-8\"?><xml name=\"root\"><test>  \n\n\n  \n</test><test>  x  </test><test>    </test></xml>"));
    }
}
