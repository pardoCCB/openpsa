<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\contacts\handler\group;

use openpsa_testcase;
use midcom;
use org_openpsa_contacts_group_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class editTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_group;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_group = self::create_class_object(org_openpsa_contacts_group_dba::class);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['group', 'edit', self::$_group->guid]);
        $this->assertEquals('group_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
