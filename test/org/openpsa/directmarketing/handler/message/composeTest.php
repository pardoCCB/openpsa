<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}
require_once OPENPSA_TEST_ROOT . 'org/openpsa/directmarketing/__helper/campaign.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_handler_message_composeTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_compose()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('message', 'compose', $message->guid));
        $this->assertEquals('compose', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_compose4person()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('message', 'compose', $message->guid, self::$_person->guid));
        $this->assertEquals('compose4person', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>