<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.invoices
 */
 class org_openpsa_invoices_status
 {
     /**
      *
      * @var org_openpsa_invoices_invoice_dba
      */
     private $invoice;

     /**
      *
      * @var midcom_services_i18n_l10n
      */
     private $l10n;

     /**
      *
      * @var midcom_services_i18n_l10n
      */
     private $l10n_midcom;

     /**
      *
      * @param org_openpsa_invoices_invoice_dba $invoice
      */
     public function __construct(org_openpsa_invoices_invoice_dba $invoice)
     {
         $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');
         $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
         $this->invoice = $invoice;
     }

     /**
      *
      * @return string
      */
     public function get_current_status()
     {
         if (!$this->invoice->sent)
         {
             return $this->l10n->get('unsent');
         }
         if (!$this->invoice->paid)
         {
             if ($this->invoice->due > time())
             {
                 return sprintf($this->l10n->get('due on %s'), date($this->l10n_midcom->get('short date'), $this->invoice->due));
             }
             else
             {
                 return '<span class="bad">' . sprintf($this->l10n->get('overdue since %s'), date($this->l10n_midcom->get('short date'), $this->invoice->due)) . '</span>';
             }
         }
         if ($this->invoice->cancelationInvoice)
         {
             return sprintf($this->l10n->get('invoice canceled on %s'), date($this->l10n_midcom->get('short date'), $this->invoice->paid));
         }
         return sprintf($this->l10n->get('paid on %s'), date($this->l10n_midcom->get('short date'), $this->invoice->paid));
     }

     /**
      * @return array
      */
     public function get_history()
     {
         $entries = array_merge($this->get_status_entries(), $this->get_journal_entries());

         usort($entries, function($a, $b)
         {
             if ($a['timestamp'] == $b['timestamp'])
             {
                 return 0;
             }
             return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
         });

         return $entries;
     }

     /**
      *
      * @return array
      */
     private function get_status_entries()
     {
         $entries = array();
         if ($this->invoice->cancelationInvoice)
         {
             $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
             $cancelation_invoice = new org_openpsa_invoices_invoice_dba($this->invoice->cancelationInvoice);
             $cancelation_invoice_link = $prefix . 'invoice/' . $cancelation_invoice->guid . '/';
             $cancelation_invoice_link = "<a href=\"" . $cancelation_invoice_link . "\">" . $this->l10n->get('invoice') . " " . $cancelation_invoice->get_label() . "</a>";
             $entries[] = array
             (
                 'timestamp' => $cancelation_invoice->metadata->created,
                 'message' => sprintf($this->l10n->get('invoice got canceled by %s'), $cancelation_invoice_link)
             );
         }
         else if ($this->invoice->paid)
         {
             $entries[] = array
             (
                 'timestamp' => $this->invoice->paid,
                 'message' => sprintf($this->l10n->get('marked invoice %s paid'), '')
             );
         }
         if (   $this->invoice->due
             && (   (   $this->invoice->due < time()
                     && $this->invoice->paid == 0)
                 || $this->invoice->due < $this->invoice->paid))
         {
             $entries[] = array
             (
                 'timestamp' => $this->invoice->due,
                 'message' => $this->l10n->get('overdue')
             );
         }

         if ($this->invoice->sent)
         {
             if ($mail_time = $this->invoice->get_parameter('org.openpsa.invoices', 'sent_by_mail'))
             {
                 $entries[] = array
                 (
                     'timestamp' => $mail_time,
                     'message' => sprintf($this->l10n->get('marked invoice %s sent per mail'), '')
                 );
             }
             else
             {
                 $entries[] = array
                 (
                     'timestamp' => $this->invoice->sent,
                     'message' => sprintf($this->l10n->get('marked invoice %s sent'), '')
                 );
             }
         }
         $entries[] = array
         (
             'timestamp' => $this->invoice->metadata->created,
             'message' => sprintf($this->l10n->get('invoice %s created'), '')
         );
         return $entries;
     }

     private function get_journal_entries()
     {
         $entries = array();

         $mc = org_openpsa_relatedto_journal_entry_dba::new_collector('linkGuid', $this->invoice->guid);
         $rows = $mc->get_rows(array('title', 'metadata.created'));

         foreach ($rows as $row)
         {
             $entries[] = array
             (
                 'timestamp' => strtotime((string) $row['created']),
                 'message' => $row['title']
             );
         }
         return $entries;
     }
 }