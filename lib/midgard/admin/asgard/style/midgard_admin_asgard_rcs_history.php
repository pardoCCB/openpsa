<?php
midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.services.rcs/rcs.js');
$history = $data['history']->all();
$guid = $data['guid'];

if (empty($history)) {
    echo $data['l10n']->get('no revisions exist');
    return;
}
?>
<form method="get" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midgard_admin_asgard_rcs_version_compare">
    <div>
        <table>
            <thead>
                <tr>
                    <th colspan="2"></th>
                    <th><?php echo $data['l10n']->get('revision'); ?></th>
                    <th><?php echo $data['l10n']->get('date'); ?></th>
                    <th><?php echo $data['l10n']->get('user'); ?></th>
                    <th><?php echo $data['l10n']->get('lines'); ?></th>
                    <th><?php echo $data['l10n']->get('message'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $formatter = $data['l10n']->get_formatter();
            foreach ($history as $number => $revision) {
                $link = $data['router']->generate('object_rcs_preview', ['guid' => $guid, 'revision' => $number]);
                echo "                <tr>\n";
                echo "                    <td><input type=\"radio\" name=\"first\" value=\"{$number}\" />\n";
                echo "                    <td><input type=\"radio\" name=\"last\" value=\"{$number}\" />\n";
                echo "                    <td><a href='{$link}'>{$number}</a></td>\n";
                echo "                    <td>" . $formatter->datetime($revision['date'], IntlDateFormatter::MEDIUM, IntlDateFormatter::LONG) . "</td>\n";
                echo "                    <td>";

                if (   $revision['user']
                    && $user = midcom::get()->auth->get_user($revision['user'])) {
                    echo $user->get_storage()->name;
                } elseif ($revision['ip']) {
                    echo $revision['ip'];
                }
                echo "</td>\n";
                echo "                    <td>{$revision['lines']}</td>\n";
                echo "                    <td>{$revision['message']}</td>\n";
                echo "                </tr>\n";
            }
            ?>
            </tbody>
        </table>
        <input type="submit" name="f_compare" value="<?php echo $data['l10n']->get('show differences'); ?>" />
    </div>
</form>
<script>
init_controls('#midgard_admin_asgard_rcs_version_compare');
</script>