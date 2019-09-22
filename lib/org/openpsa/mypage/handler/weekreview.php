<?php
/**
 * @package org.openpsa.mypage
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

/**
 * My page weekreview handler
 *
 * @package org.openpsa.mypage
 */
class org_openpsa_mypage_handler_weekreview extends midcom_baseclasses_components_handler
{
    /**
     * @return midcom_response_relocate
     */
    public function _handler_redirect()
    {
        $date = date('Y-m-d');
        return new midcom_response_relocate($this->router->generate('weekreview', ['date' => $date]));
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('day', ['date' => strftime('%Y-%m-%d', $this->_request_data['week_start'])]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('day review'),
            MIDCOM_TOOLBAR_GLYPHICON => 'dashboard',
        ]);
        org_openpsa_widgets_ui::add_navigation_toolbar([[
            MIDCOM_TOOLBAR_URL => $this->router->generate('weekreview', ['date' => $this->_request_data['prev_week']]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-left',
        ], [
            MIDCOM_TOOLBAR_URL => $this->router->generate('weekreview', ['date' => $this->_request_data['next_week']]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-right',
        ]]);
    }

    /**
     * List user's event memberships
     */
    private function _list_events_between(array &$data_array, int $person, int $from, int $to)
    {
        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->get_doctrine()
            ->leftJoin('org_openpsa_eventmember', 'm', Join::WITH, 'm.eid = c.id')
            ->where('m.uid = :person')
            ->setParameter('person', $person);

        // Find all events that occur during [$from, $to]
        $qb->add_constraint('start', '<=', $to);
        $qb->add_constraint('end', '>=', $from);

        foreach ($qb->execute() as $event) {
            $this->_add_to($data_array, $event, $event->start);
        }
    }

    private function _list_hour_reports_between(array &$data_array, int $person, int $from, int $to)
    {
        // List user's hour reports
        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('date', '>=', $from);
        $qb->add_constraint('date', '<=', $to);
        $qb->add_constraint('person', '=', $person);

        foreach ($qb->execute() as $hour_report) {
            $time = mktime(date('H', $hour_report->metadata->created), date('i', $hour_report->metadata->created), date('s', $hour_report->metadata->created), date('m', $hour_report->date), date('d', $hour_report->date), date('Y', $hour_report->date));
            $this->_add_to($data_array, $hour_report, $time);
        }
    }

    private function _list_task_statuses_between(array &$data_array, $person, int $from, int $to)
    {
        // List user's hour reports
        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('timestamp', '>=', $from);
        $qb->add_constraint('timestamp', '<=', $to);
        $qb->begin_group('OR');
        $qb->add_constraint('targetPerson', '=', $person->id);
        $qb->add_constraint('metadata.creator', '=', $person->guid);
        $qb->end_group();

        foreach ($qb->execute() as $task_status) {
            $this->_add_to($data_array, $task_status, $task_status->timestamp);
        }
    }

    private function _add_to(array &$array, midcom_core_dbaobject $object, int $time)
    {
        $date = date('Y-m-d', $time);
        $array = array_replace_recursive($array, [$date => [$time => [$object->guid => $object]]]);
    }

    /**
     * @param string $date The requested date
     * @param array $data The local request data.
     */
    public function _handler_review($date, array &$data)
    {
        // Get start and end times
        $date = new DateTime($date);
        $data['requested_time'] = (int) $date->format('U');

        $offset = $date->format('N') - 1;
        $date->modify('-' . $offset . ' days');
        $data['week_start'] = (int) $date->format('U');
        $date->modify('+6 days');
        $date->setTime(23, 59, 59);
        $data['week_end'] = (int) $date->format('U');

        $date->modify('+1 second');
        $data['next_week'] = $date->format('Y-m-d');
        $date->modify('-2 weeks');
        $data['prev_week'] = $date->format('Y-m-d');

        // Empty the data array
        $data['review_data'] = [];

        // Then start looking for stuff to display
        $this->_list_events_between($data['review_data'], midcom_connection::get_user(), $data['week_start'], $data['week_end']);
        $this->_list_hour_reports_between($data['review_data'], midcom_connection::get_user(), $data['week_start'], $data['week_end']);
        $this->_list_task_statuses_between($data['review_data'], midcom::get()->auth->user, $data['week_start'], $data['week_end']);

        // Arrange by date/time
        ksort($data['review_data']);

        // Set page title
        if ($data['requested_time'] > time()) {
            $title_string = 'preview for week %s';
        } else {
            $title_string = 'review of week %s';
        }

        $data['title'] = sprintf($this->_l10n->get($title_string), strftime('%W %Y', $data['requested_time']));
        midcom::get()->head->set_pagetitle($data['title']);

        $this->_populate_toolbar();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $this->add_breadcrumb($this->router->generate('weekreview_redirect'), $this->_l10n->get('week review'));
        $this->add_breadcrumb('', $data['title']);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_review($handler_id, array &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['calendar_node'] = midcom_helper_misc::find_node_by_component('org.openpsa.calendar');
        $data['projects_url'] = $siteconfig->get_node_full_url('org.openpsa.projects');

        $date = new DateTime(date('Y-m-d', $data['week_start']));
        $offset = $date->format('N') - 1;
        $date->modify('-' . $offset . ' days');

        $week_hours_invoiceable = 0;
        $week_hours_total = 0;

        midcom_show_style('weekreview-header');
        for ($i = 0; $i < 7; $i++) {
            $day = $date->format('Y-m-d');
            $data['day_start'] = (int) $date->format('U');

            $date->setTime(23, 59, 59);
            $data['day_end'] = (int) $date->format('U');

            //Roll over to the next day
            $date->modify('+1 second');

            if (!array_key_exists($day, $data['review_data'])) {
                // Nothing for today
                continue;
            }

            midcom_show_style('weekreview-day-header');

            $day_hours_invoiceable = 0;
            $day_hours_total = 0;

            // Arrange entries per time
            ksort($data['review_data'][$day]);
            $data['class'] = 'even';
            foreach ($data['review_data'][$day] as $time => $guids) {
                foreach ($guids as $object) {
                    $data['class'] = ($data['class'] == 'even') ? 'odd' : 'even';
                    $data['time'] = $time;
                    $data['object'] = $object;
                    switch (get_class($object)) {
                        case org_openpsa_calendar_event_dba::class:
                            midcom_show_style('weekreview-day-item-event');
                            break;
                        case org_openpsa_expenses_hour_report_dba::class:
                            midcom_show_style('weekreview-day-item-hour-report');

                            if ($object->invoiceable) {
                                $day_hours_invoiceable += $object->hours;
                            }
                            $day_hours_total += $object->hours;

                            break;
                        case org_openpsa_projects_task_status_dba::class:
                            midcom_show_style('weekreview-day-item-task-status');
                            break;
                    }
                }
            }

            $data['day_hours_invoiceable'] = $day_hours_invoiceable;
            $week_hours_invoiceable += $day_hours_invoiceable;
            $data['day_hours_total'] = $day_hours_total;
            $week_hours_total += $day_hours_total;

            midcom_show_style('weekreview-day-footer');
        }
        $data['week_hours_invoiceable'] = $week_hours_invoiceable;
        $data['week_hours_total'] = $week_hours_total;
        midcom_show_style('weekreview-footer');
    }
}
