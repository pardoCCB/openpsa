<?php
/**
 * @package midcom.helper.filesync
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.helper.filesync
 */
abstract class midcom_helper_filesync_exporter extends midcom_baseclasses_components_purecode
{
    /**
     * Whether to delete elements from file system that do not exist in database
     *
     * @var boolean
     */
    var $delete_missing = false;

    /**
     * Initializes the class.
     *
     * @param boolean $delete_missing whether to delete missing items from database
     */
    public function __construct($delete_missing = false)
    {
         $this->_component = 'midcom.helper.filesync';
         $this->delete_missing = $delete_missing;
         parent::__construct();
    }

    abstract public function export();

    protected function delete_missing_folders($foldernames, $path)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $directory = dir($path);
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (!is_dir("{$path}/{$entry}"))
            {
                // We're only checking for directories here
                continue;
            }

            if (!in_array($entry, $foldernames))
            {
                unlink("{$path}/{$entry}");
            }
        }
        $directory->close();
    }

    protected function delete_missing_files($filenames, $path)
    {
        if (!$this->delete_missing)
        {
            return;
        }

        $directory = dir($path);
        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // We're only checking for files here
                continue;
            }

            if (!in_array($entry, $filenames))
            {
                unlink("{$path}/{$entry}");
            }
        }
        $directory->close();
    }

    /**
     * This is a static factory method which lets you dynamically create exporter instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * @param string $type type
     * @return midcom_helper_filesync_exporter A reference to the newly created exporter instance.
     * @static
     */
    public static function & create($type)
    {
        $classname = "midcom_helper_filesync_exporter_{$type}";
        if (!class_exists($classname))
        {
            throw new midcom_error("Requested exporter class {$type} is not installed.");
            // This will exit.
        }

        $class = new $classname();
        return $class;
    }
}
?>