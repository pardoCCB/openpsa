<?php
namespace midcom\datamanager\template;

use Symfony\Component\Form\FormView;
use midcom;
use midcom_helper_formatter;
use Michelf\MarkdownExtra;

class view extends base
{
    private $skip_empty = false;

    /**
     * Define the quotes behavior when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    private $specialchars_quotes = ENT_QUOTES;

    /**
     * Define the charset to use when htmlspecialchars() is called
     *
     * @see http://www.php.net/htmlspecialchars
     */
    private $specialchars_charset = 'UTF-8';

    public function __construct($renderer, $skip_empty = false)
    {
        parent::__construct($renderer);
        $this->skip_empty = $skip_empty;
    }

    public function form_start(FormView $view, array $data)
    {
        return "<div class=\"midcom_helper_datamanager2_view\">";
    }

    public function form_end(FormView $view, array $data)
    {
        return "</div>";
    }

    public function form_errors(FormView $view, array $data)
    {
        return '';
    }

    public function form_rows(FormView $view, array $data)
    {
        $string = '';
        foreach ($view as $child) {
            if (!empty($child->vars['hidden'])) {
                $child->setRendered();
                continue;
            }

            if (    array_key_exists('start_fieldset', $child->vars)
                && $child->vars['start_fieldset'] !== null) {
                $string .= '<div class="fieldset">';
                if (!empty($child->vars['start_fieldset']['title'])) {
                    $string .= '<h2>' . $this->renderer->humanize($child->vars['start_fieldset']['title']) . '</h2>';
                }
            }
            if (   !$this->skip_empty
                 || ($child->vars['data'] !== '' && $child->vars['data'] !== null && $child->vars['data'] !== [])) {
                $string .= $this->renderer->row($child);
            }
            if (    array_key_exists('end_fieldset', $child->vars)
                && $child->vars['end_fieldset'] !== null) {
                $end_fieldsets = max(1, (int) $child->vars['end_fieldset']);
                for ($i = 0; $i < $end_fieldsets; $i++) {
                    $string .= '</div>';
                }
            }
        }
        return $string;
    }

    public function form_rest(FormView $view, array $data)
    {
        return '';
    }

    public function hidden_row(FormView $view, array $data)
    {
        return '';
    }

    public function button_row(FormView $view, array $data)
    {
        return '';
    }

    public function toolbar_row(FormView $view, array $data)
    {
        return '';
    }

    public function form_row(FormView $view, array $data)
    {
        $class = 'field field_' . $view->vars['block_prefixes'][count($view->vars['block_prefixes']) - 2];

        $string = '<div class="' . $class . '">';
        $string .= $this->renderer->label($view);
        $string .= '<div class="value">';
        $string .= $this->renderer->widget($view);
        return $string . '</div></div>';
    }

    public function blobs_row(FormView $view, array $data)
    {
        if (empty($data['value']['url'])) {
            return '';
        }
        return '<a href="' . $data['value']['url'] . '">' . $data['value']['title'] . '</a>';
    }

    public function form_label(FormView $view, array $data)
    {
        if ($data['label'] === false) {
            return '';
        }
        $label_attr = $data['label_attr'];
        $label_attr['class'] = trim('title ' . (isset($label_attr['class']) ? $label_attr['class'] : ''));
        if (!$data['label']) {
            $data['label'] = $data['name'];
        }
        return '<div' . $this->attributes($label_attr) . '>' . $this->renderer->humanize($data['label']) . '</div>';
    }

    public function subform_widget(FormView $view, array $data)
    {
        if (empty($view->vars['data'])) {
            return '';
        }
        $string = '<div' . $this->renderer->block($view, 'widget_container_attributes') . '>';
        $string .= $this->renderer->block($view, 'form_rows');
        $string .= $this->renderer->rest($view);
        return $string . '</div>';
    }

    public function form_widget_simple(FormView $view, array $data)
    {
        if (   !empty($data['value'])
            || is_numeric($data['value'])) {
            return $this->escape($data['value']);
        }
        return '';
    }

    public function email_widget(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            return '<a href="mailto:' . $data['value'] . '">' . $this->escape($data['value']) . '</a>';
        }
        return '';
    }

    public function url_widget(FormView $view, array $data)
    {
        if (!empty($data['value'])) {
            return '<a href="' . $data['value'] . '">' . $this->escape($data['value']) . '</a>';
        }
        return '';
    }

    public function text_widget(FormView $view, array $data)
    {
        if (empty($view->vars['output_mode'])) {
            $view->vars['output_mode'] = 'html';
        }
        switch ($view->vars['output_mode']) {
            case 'code':
                return '<pre style="overflow:auto">' . htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'pre':
                return '<pre style="white-space: pre-wrap">' . htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset) . '</pre>';

            case 'specialchars':
                return htmlspecialchars($data['value'], $this->specialchars_quotes, $this->specialchars_charset);

            case 'nl2br':
                return nl2br(htmlentities($data['value'], $this->specialchars_quotes, $this->specialchars_charset));

            case 'midgard_f':
                return midcom_helper_formatter::format($data['value'], 'f');

            case 'markdown':
                return MarkdownExtra::defaultTransform($data['value']);

            case (substr($view->vars['output_mode'], 0, 1) == 'x'):
                // Run the contents through a custom formatter registered via mgd_register_filter
                return midcom_helper_formatter::format($data['value'], $view->vars['output_mode']);

            case 'html':
                return $data['value'];
        }
    }

    public function radiocheckselect_widget(FormView $view, array $data)
    {
        $ret = [];
        foreach ($view->children as $child) {
            if ($child->vars['checked']) {
                $ret[] = $this->renderer->humanize($child->vars['label']);
            }
        }
        return implode(', ', $ret);
    }

    public function image_widget(FormView $view, array $data)
    {
        if (!array_key_exists('main', $data['value'])) {
            $ret = "";
            if (!empty($data['value'])) {
                $ret .= $this->renderer->humanize('could not figure out which image to show, listing files') . "<ul>";
                foreach ($data['value'] as $info) {
                    $ret .= "<li><a href='{$info['url']}'>{$info['filename']}</a></li>";
                }
                $ret .= "</ul>";
            }
            return $ret;
        }

        $identifier = 'main';
        $linkto = false;
        if (array_key_exists('view', $data['value'])) {
            $identifier = 'view';
            $linkto = 'main';
        } elseif (array_key_exists('thumbnail', $data['value'])) {
            $identifier = 'thumbnail';
            $linkto = 'main';
        } elseif (array_key_exists('archival', $data['value'])) {
            $linkto = 'archival';
        }

        $img = $data['value'][$identifier];
        $return = '<div class="midcom_helper_datamanager2_type_photo">';
        $img_tag = "<img src='{$img['url']}' {$img['size_line']} class='photo {$identifier}' />";
        if ($linkto) {
            $linked = $data['value'][$linkto];
            $return .= "<a href='{$linked['url']}' target='_blank' class='{$linkto} {$linked['mimetype']}'>{$img_tag}</a>";
        } else {
            $return .= $img_tag;
        }
        if (array_key_exists('archival', $data['value'])) {
            $arch = $data['value']['archival'];
            $return .= "<br/><a href='{$arch['url']}' target='_blank' class='archival {$arch['mimetype']}'>" . $this->renderer->humanize('archived image') . '</a>';
        }
        return $return . '</div>';
    }

    public function autocomplete_widget(FormView $view, array $data)
    {
        $options = json_decode($data['handler_options'], true);
        $string = implode(', ', $options['preset']);

        if ($string == '') {
            $string = $this->renderer->humanize('type select: no selection');
        }

        return $string;
    }

    public function choice_widget_collapsed(FormView $view, array $data)
    {
        if (isset($data['data'])) {
            if (!empty($view->vars['multiple'])) {
                $selection = $data['data'];
            } else {
                $selection = (string) $data['data'];
            }
            foreach ($data['choices'] as $choice) {
                if ($data['is_selected']($choice->value, $selection)) {
                    return $this->renderer->humanize($choice->label);
                }
            }
        }
        return '';
    }

    public function checkbox_widget(FormView $view, $data)
    {
        if ($data['checked']) {
            return '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png" alt="selected" />';
        }
        return '<img src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png" alt="not selected" />';
    }

    public function codemirror_widget(FormView $view, array $data)
    {
        $string = '<textarea ' . $this->renderer->block($view, 'widget_attributes') . '>';
        $string .= $data['value'] . '</textarea>';
        if (!empty($data['codemirror_snippet'])) {
            $snippet = str_replace('{$id}', $data['id'], $data['codemirror_snippet']);
            $snippet = str_replace('{$read_only}', 'true', $snippet);
            $string .= $this->jsinit($snippet);
        }
        return $string;
    }

    public function tinymce_widget(FormView $view, array $data)
    {
        return $data['value'];
    }

    public function jsdate_widget(FormView $view, array $data)
    {
        if (empty($data['value']['date'])) {
            return '';
        }

        $time_format = 'none';
        if (isset($view['time'])) {
            $time_format = (isset($data['value']['seconds'])) ? 'medium' : 'short';
        }
        return midcom::get()->i18n->get_l10n()->get_formatter()->date($data['value']['date'], 'medium', $time_format);
    }
}
