<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom;
use midcom\datamanager\extension\transformer\imageTransformer;

class imageTransformerTest extends openpsa_testcase
{
    /**
     * @return imageTransformer
     */
    private function get_transformer()
    {
        $config = [
            'widget_config' => [
                'show_description' => false,
                'show_title' => true
            ]
        ];
        return new imageTransformer($config);
    }

    /**
     * @dataProvider provider_transform
     */
    public function test_transform($input, $expected)
    {
        $transformer = $this->get_transformer();
        $this->assertEquals($expected, $transformer->transform($input));
    }

    /**
     * @dataProvider provider_transform
     */
    public function test_reverseTransform($expected, $input)
    {
        $transformer = $this->get_transformer();
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    public function provider_transform()
    {
        $topic = $this->create_object(\midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.datamanager');
        $att = $topic->create_attachment('test', 'test', 'text/plain');
        $handle = $att->open('w');
        fwrite($handle, 'test');
        $time = filemtime($att->get_path());
        $att->close();

        midcom::get()->auth->drop_sudo('midcom.datamanager');

        return [
           [null, []],
           [['main' => $att, 'title' => 'test'], [
               'objects' => [
                   'main' => [
                       'object' => $att,
                       'filename' => 'test',
                       'description' => 'test',
                       'title' => 'test',
                       'mimetype' => 'text/plain',
                       'url' => '/midcom-serveattachmentguid-' . $att->guid . '/test',
                       'id' => $att->id,
                       'guid' => $att->guid,
                       'filesize' => 4,
                       'formattedsize' => '4 Bytes',
                       'lastmod' => $time,
                       'isoformattedlastmod' => date('Y-m-d H:i:s', $time),
                       'size_x' => null,
                       'size_y' => null,
                       'size_line' => null,
                       'score' => 0,
                       'identifier' => $att->guid,
                       'file' => null
                   ]
               ],
               'title' => 'test'
           ]]
        ];
    }
}
