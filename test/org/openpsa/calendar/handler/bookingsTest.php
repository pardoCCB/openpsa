<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_calendar_handler_bookingsTest extends openpsa_testcase
{
    public function testHandler_list()
    {
        $this->create_user(true);

        $project = $this->create_object(org_openpsa_projects_project::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, [
            'project' => $project->id
        ]);

        $data = $this->run_handler('org.openpsa.calendar', ['bookings', $task->guid]);
        $this->assertEquals('bookings', $data['handler_id']);
    }
}
