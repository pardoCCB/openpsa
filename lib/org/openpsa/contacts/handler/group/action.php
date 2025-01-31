<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_action extends midcom_baseclasses_components_handler
{
    use org_openpsa_contacts_handler;

    /**
     * @var org_openpsa_contacts_group_dba[]
     */
    private $results;

    public function _handler_update_member_title(Request $request)
    {
        $response = ['status' => false];

        if ($request->request->has('guid') && $request->request->has('title')) {
            try {
                $member = new midcom_db_member($request->request->get('guid'));
                $member->require_do('midgard:update');
                $member->extra = $request->request->get('title');
                $response['status'] = $member->update();
            } catch (midcom_error $e) {
                $e->log();
            }
            $response['message'] = midcom_connection::get_error_string();
        }

        return new JsonResponse($response);
    }

    public function _handler_members(string $guid, array &$data)
    {
        $data['group'] = new org_openpsa_contacts_group_dba($guid);
        $qb = new org_openpsa_qbpager(midcom_db_member::class, 'group_members');
        $qb->add_constraint('gid', '=', $data['group']->id);
        $qb->results_per_page = 10;
        $data['members_qb'] = $qb;
    }

    public function _show_members(string $handler_id, array &$data)
    {
        $results = $data['members_qb']->execute();
        if (!empty($results)) {
            $this->add_head_elements();
            midcom_show_style('show-group-persons-header');
            foreach ($results as $member) {
                $data['member'] = $member;
                $data['member_title'] = $member->extra;

                $data['person'] = new org_openpsa_contacts_person_dba($member->uid);
                midcom_show_style('show-group-persons-item');
            }
            midcom_show_style('show-group-persons-footer');
        }
    }

    public function _handler_subgroups(string $guid)
    {
        $group = new org_openpsa_contacts_group_dba($guid);
        $qb = org_openpsa_contacts_group_dba::new_query_builder();
        $qb->add_constraint('owner', '=', $group->id);
        $this->results = $qb->execute();
    }

    public function _show_subgroups(string $handler_id, array &$data)
    {
        if (!empty($this->results)) {
            $this->add_head_elements();
            midcom_show_style('show-group-subgroups-header');
            foreach ($this->results as $subgroup) {
                $data['subgroup'] = $subgroup;
                midcom_show_style('show-group-subgroups-item');
            }
            midcom_show_style('show-group-subgroups-footer');
        }
    }
}
