<?php
namespace FXMLRPC;

use DateTime;
use DateTimeZone;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = new Parser();
    }

    public static function provideSimpleTypes()
    {
        return array(
            array('Value', 'string', 'Value'),
            array(12, 'i4', '12'),
            array(12, 'int', '12'),
            array(false, 'boolean', '0'),
            array(true, 'boolean', '1'),
            array(1.2, 'double', '1.2'),
            array(
                DateTime::createFromFormat('Y-m-d H:i:s', '1998-07-17 14:08:55', new DateTimeZone('UTC')),
                'dateTime.iso8601',
                '19980717T14:08:55'
            ),
        );
    }

    /**
     * @dataProvider provideSimpleTypes
     */
    public function testParsingSimpleTypes($expectedValue, $serializedType, $serializedValue)
    {
        $xml = sprintf(
            '<?xml version="1.0"?>
                <methodResponse>
                <params>
                    <param>
                    <value><%1$s>%2$s</%1$s></value>
                    </param>
                </params>
                </methodResponse>',
            $serializedType,
            $serializedValue
        );

        $this->assertEquals(array($expectedValue), $this->parser->parse($xml));
    }

    public function testParsingMultiMethodResponse()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
              <params>
                <param>
                  <value><string>Ümlaut String</string></value>
                </param>
                <param>
                  <value><string>Normal String</string></value>
                </param>
              </params>
            </methodResponse>';

        $this->assertSame(array('Ümlaut String', 'Normal String'), $this->parser->parse($string));
    }

    public function testParsingListResponse()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
                <params>
                    <param>
                        <value>
                            <array>
                                <data>
                                    <value><string>Str 0</string></value>
                                    <value><string>Str 1</string></value>
                                </data>
                            </array>
                        </value>
                    </param>
                </params>
            </methodResponse>';
        $this->assertSame(array(array('Str 0', 'Str 1')), $this->parser->parse($string));
    }

    public function testParsingNestedListResponse()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
                <params>
                    <param>
                        <value>
                            <array>
                                <data>
                                    <value>
                                        <array>
                                            <data>
                                                <value><string>Str 00</string></value>
                                                <value><string>Str 01</string></value>
                                            </data>
                                        </array>
                                    </value>
                                    <value>
                                        <array>
                                            <data>
                                                <value><string>Str 10</string></value>
                                                <value><string>Str 11</string></value>
                                            </data>
                                        </array>
                                    </value>
                                </data>
                            </array>
                        </value>
                    </param>
                </params>
            </methodResponse>';
        $this->assertSame(
            array(array(array('Str 00', 'Str 01'), array('Str 10', 'Str 11'))),
            $this->parser->parse($string)
        );
    }

    public function testParsingStructs()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
                <params>
                    <param>
                        <value>
                            <struct>
                                <member>
                                    <name>FIRST</name>
                                    <value><string>ONE</string></value>
                                </member>
                                <member>
                                    <value><string>TWO</string></value>
                                    <name>SECOND</name>
                                </member>
                                <member>
                                    <name>THIRD</name>
                                    <value><string>THREE</string></value>
                                </member>
                            </struct>
                        </value>
                    </param>
                </params>
            </methodResponse>';
        $this->assertSame(
            array(array('FIRST' => 'ONE', 'SECOND' => 'TWO', 'THIRD' => 'THREE')),
            $this->parser->parse($string)
        );
    }

    public function testParsingStructsInStructs()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
                <params>
                    <param>
                        <value>
                            <struct>
                                <member>
                                    <name>FIRST</name>
                                    <value>
                                        <struct>
                                            <member>
                                                <name>ONE</name>
                                                <value><i4>1</i4></value>
                                            </member>
                                            <member>
                                                <name>TWO</name>
                                                <value><i4>2</i4></value>
                                            </member>
                                        </struct>
                                    </value>
                                </member>
                                <member>
                                    <name>SECOND</name>
                                    <value>
                                        <struct>
                                            <member>
                                                <name>ONE ONE</name>
                                                <value><i4>11</i4></value>
                                            </member>
                                            <member>
                                                <name>TWO TWO</name>
                                                <value><i4>22</i4></value>
                                            </member>
                                        </struct>
                                    </value>
                                </member>
                            </struct>
                        </value>
                    </param>
                </params>
            </methodResponse>';
        $this->assertSame(
            array(
                array(
                    'FIRST' => array('ONE' => 1, 'TWO' => 2),
                    'SECOND' => array('ONE ONE' => 11, 'TWO TWO' => 22),
                )
            ),
            $this->parser->parse($string)
        );
    }

    public function testParsingListsInStructs()
    {
        $string = '<?xml version="1.0"?>
            <methodResponse>
                <params>
                    <param>
                        <value>
                            <struct>
                                <member>
                                    <name>FIRST</name>
                                    <value>
                                        <array>
                                            <data>
                                                <value>
                                                    <array>
                                                        <data>
                                                            <value><string>Str 00</string></value>
                                                            <value><string>Str 01</string></value>
                                                        </data>
                                                    </array>
                                                </value>
                                                <value>
                                                    <array>
                                                        <data>
                                                            <value><string>Str 10</string></value>
                                                            <value><string>Str 11</string></value>
                                                        </data>
                                                    </array>
                                                </value>
                                            </data>
                                        </array>
                                    </value>
                                </member>
                                <member>
                                    <name>SECOND</name>
                                    <value>
                                        <array>
                                            <data>
                                                <value>
                                                    <array>
                                                        <data>
                                                            <value><string>Str 30</string></value>
                                                            <value><string>Str 31</string></value>
                                                        </data>
                                                    </array>
                                                </value>
                                                <value>
                                                    <array>
                                                        <data>
                                                            <value><string>Str 40</string></value>
                                                            <value><string>Str 41</string></value>
                                                        </data>
                                                    </array>
                                                </value>
                                            </data>
                                        </array>
                                    </value>
                                </member>
                            </struct>
                        </value>
                    </param>
                </params>
            </methodResponse>';
        $this->assertSame(
            array(
                array(
                    'FIRST' => array(array('Str 00', 'Str 01'), array('Str 10', 'Str 11')),
                    'SECOND' => array(array('Str 30', 'Str 31'), array('Str 40', 'Str 41')),
                )
            ),
            $this->parser->parse($string)
        );
    }
}
