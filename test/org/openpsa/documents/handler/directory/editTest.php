<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\documents\handler\directory;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class editTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.documents');

        $data = $this->run_handler('org.openpsa.documents', ['edit']);
        $this->assertEquals('directory-edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
