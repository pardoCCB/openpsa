<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PEAR Mail_sendmail
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail_sendmail extends org_openpsa_mail_backend
{
    public function __construct(array $params)
    {        
        // example: Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs')
        $this->_mail = Swift_SendmailTransport::newInstance($params['sendmail_path'] . " " . $params['sendmail_args']);
    }

    public function mail(org_openpsa_mail_message $message)
    {
        return $this->_mail->send($message->get_message());
    }
}
?>
