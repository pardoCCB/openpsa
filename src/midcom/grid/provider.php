<?php
/**
 * @package midcom.grid
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\grid;

use midcom\grid\provider\client;
use midcom_core_query;
use midcom_error;
use midcom;

/**
 * Manager for retrieving grid data by AJAX
 *
 * @package midcom.grid
 */
class provider
{
    /**
     * The class responsible for getting and formatting rows
     *
     * @var client
     */
    private $_client;

    /**
     * The rows to show
     *
     * @var array
     */
    private $_rows;

    /**
     * The total number of rows
     *
     * @var int
     */
    private $_total_rows;

    /**
     * How many items should be shown per page
     *
     * @var int
     */
    private $_results_per_page = 20;

    /**
     * The current offset
     *
     * @var int
     */
    private $_offset;

    /**
     * The field for sorting
     *
     * @var string
     */
    private $_sort_field;

    /**
     * The direction for sorting (ASC or DESC)
     *
     * @var string
     */
    private $_sort_direction;

    /**
     * The grid we're working with
     *
     * @var grid
     */
    private $_grid;

    /**
     * The datatype we're working with
     *
     * @var string
     */
    private $_datatype;

    /**
     * The midcom query object
     *
     * @var midcom_core_query
     */
    private $_query;

    /**
     * Search parameters
     *
     * @var array
     */
    private $_search = [];

    public function __construct($source, $datatype = 'json')
    {
        $this->_datatype = $datatype;
        if ($source instanceof client) {
            $this->_client = $source;
        } elseif (is_array($source)) {
            $this->set_rows($source);
        } else {
            throw new midcom_error('Unknown source type');
        }
    }

    /**
     * Adds an initial order to the resultset.
     *
     * This can be overwritten by GET parameters
     */
    public function add_order(string $field, string $direction = 'ASC')
    {
        $this->_sort_field = $field;
        $this->_sort_direction = $direction;
    }

    public function set_grid(grid $grid)
    {
        $this->_grid = $grid;
        $this->_grid->set_provider($this);
        $this->_datatype = $grid->get_option('datatype');
    }

    public function get_grid(string $identifier = null) : grid
    {
        if (null !== $identifier) {
            $this->_grid = new grid($identifier, $this->_datatype);
            $this->_grid->set_provider($this);
            if (!empty($this->_sort_field)) {
                $this->_grid->set_option('sortname', $this->_sort_field);
                $this->_grid->set_option('sortorder', strtolower($this->_sort_direction));
            }
        }
        return $this->_grid;
    }

    public function set_rows(array $rows)
    {
        $this->_rows = $rows;
        if ($this->_datatype == 'local') {
            $this->_total_rows = count($this->_rows);
        }
    }

    public function get_rows() : array
    {
        if ($this->_rows === null) {
            $this->_get_rows();
        }
        return $this->_rows;
    }

    public function set_query(midcom_core_query $query)
    {
        $this->_rows = null;
        $this->_total_rows = null;
        $this->_query = $query;
    }

    /**
     * returns the query (uncached)
     */
    public function get_query()
    {
        if ($this->_datatype == 'json') {
            $this->_parse_query($_GET);
        }
        $field = $this->_sort_field;
        if ($field !== null) {
            $field = str_replace('index_', '', $field);
        }

        return $this->_client->get_qb($field, $this->_sort_direction, $this->_search);
    }

    public function count_rows() : int
    {
        if ($this->_total_rows === null) {
            $qb = $this->_prepare_query();
            $this->_total_rows = $qb->count();
        }
        return $this->_total_rows;
    }

    public function get_column_total(string $column)
    {
        $ret = 0;
        $rows = $this->get_rows();
        foreach ($rows as $row) {
            if (array_key_exists($column, $row)) {
                $ret += $row[$column];
            }
        }
        return $ret;
    }

    public function setup_grid()
    {
        if ($this->_datatype == 'local') {
            $this->_grid->prepend_js($this->_convert_to_localdata());
            $this->_grid->set_option('data', $this->_grid->get_identifier() . '_entries', false);
            if (null === $this->_get_grid_option('rowNum')) {
                $this->_grid->set_option('rowNum', $this->count_rows());
            }
        }
    }

    public function render()
    {
        switch ($this->_datatype) {
            case 'json':
                $this->_render_json();
                break;
            case 'local':
                $this->get_grid()->render();
                break;
            default:
                debug_add('Datatype ' . $this->_get_grid_option('datatype', 'json') . ' is not supported', MIDCOM_LOG_ERROR);
                throw new midcom_error('Unsupported datatype');
        }
    }

    private function _get_grid_option(string $key, $default = null)
    {
        if (empty($this->_grid)) {
            return $default;
        }
        return $this->_grid->get_option($key);
    }

    private function _convert_to_localdata() : string
    {
        return "var " . $this->_grid->get_identifier() . '_entries = ' .  json_encode($this->get_rows()) . ";\n";
    }

    private function _render_json()
    {
        $rows = $this->get_rows();
        if ($this->_total_rows === null) {
            $this->_total_rows = count($rows);
        }

        $response = [
            'total' => ceil($this->_total_rows / $this->_results_per_page),
            'page' => ($this->_offset / $this->_results_per_page) + 1,
            'records' => $this->_total_rows,
            'rows' => $rows
        ];
        midcom::get()->cache->content->content_type('application/json; charset=UTF-8');

        echo json_encode($response);
    }

    private function _parse_query(array $query)
    {
        if (!empty($query['rows'])) {
            $this->_results_per_page = (int) $query['rows'];
            if (!empty($query['page'])) {
                $this->_offset = ($this->_results_per_page * ($query['page'] - 1));
            }
        }
        if (!empty($query['sidx'])) {
            $this->_sort_field = $query['sidx'];
            $this->_sort_direction = strtoupper($query['sord']);
        }
        if (   !empty($query['_search'])
            && $query['_search'] === 'true') {
            foreach ($query as $field => $value) {
                if (in_array($field, ['_search', 'nd', 'page', 'rows', 'sidx', 'sord'])) {
                    continue;
                }
                $this->_search[str_replace('index_', '', $field)] = $value;
            }
        }
    }

    private function _prepare_query()
    {
        if ($this->_query === null) {
            $this->_query = $this->get_query();
        }
        return $this->_query;
    }

    private function _get_rows()
    {
        $qb = $this->_prepare_query();

        $this->_total_rows = $qb->count();

        if (   $this->_datatype == 'json'
            && !empty($this->_results_per_page)) {
            $qb->set_limit($this->_results_per_page);
            if (!empty($this->_offset)) {
                $qb->set_offset($this->_offset);
            }
        }
        $this->_rows = [];

        if ($qb instanceof \midcom_core_querybuilder) {
            $items = $qb->execute();
        } elseif ($qb instanceof \midcom_core_collector) {
            $items = $qb->get_objects();
        } else {
            throw new midcom_error('Unsupported query class ' . get_class($qb));
        }
        foreach ($items as $item) {
            $this->_rows[] = $this->_client->get_row($item);
        }
    }
}
