<?php


class npDateRender extends npFieldRender {

    public function init() {
        $this->modx->regClientCSS($this->newspublisher->assetsUrl . 'datepicker/css/datepicker.css');
        $this->modx->regClientStartupHTMLBlock('<script type=text/javascript src="' . $this->newspublisher->assetsUrl . 'datepicker/js/datepicker.packed.js">{"lang":"' . $this->newspublisher->language . '"}</script>');
    }

    public function process() {
        
        if ($timestamp = $this->field->getValue()) {
            /* format date string according to np_date_format lexicon entry
            * (see http://www.frequency-decoder.com/2009/09/09/unobtrusive-date-picker-widget-v5
            * for details)
            */
            $format = $this->modx->lexicon('np_date_format');
            $format = str_replace( array('-','sp','dt','sl','ds','cc'),
                                 array( '', ' ', '.', '/', '-', ','), $format);
            $date = mktime(0, 0, 0, substr($timestamp,5,2), substr($timestamp,8,2), substr($timestamp,0,4));
            $date = date($format, $date);

            /* time */
            $time = substr($timestamp,11,5);

        } else {
            $date = $time = '';
        }
        $this->modx->toPlaceholder($this->field->name, $date, $this->newspublisher->prefix);
        $this->modx->toPlaceholder($this->field->name . '_time' , $time, $this->newspublisher->prefix);

        /* Set disabled dates */

        $disabled = '';
        if ($this->properties['disabledDates']) {
            $disabled .= 'disabledDates:{';
            foreach (explode(',', $this->properties['disabledDates']) as $d) {
                $disabled .= '"';
                $d = str_replace('-', '', $d);
                $d = str_replace('.', '*', $d);
                if (! (strpos($d, '^') === false)) {
                    $d = str_replace('^',  str_repeat('*', 9 - strlen($d)), $d);
                }
                $disabled .= $d . '":1,';
            }
            $disabled .= '},';
        }
        if ($this->properties['disabledDays']) {
            $disabled .= 'disabledDays:[';
            $days = explode(',', $this->properties['disabledDays']);
            for ($day = 1; $day <= 7; $day++) {
                $disabled .= (in_array($day, $days) ? 1 : 0) . ',';
            }
            $disabled .= '],';
        }
        if ($this->properties['minDateValue']) {
            $disabled .= 'rangeLow:"' . str_replace('-', '', $this->properties['minDateValue']) . '",';
        }
        if ($this->properties['maxDateValue']) {
            $disabled .= 'rangeHigh:"' . str_replace('-', '', $this->properties['maxDateValue']) . '",';
        }

        $this->setPlaceholder('disabledDates', $disabled);
    }

    public function getTemplate() {
        return 'DateTpl';
    }

    public static function getPostbackValue($fieldName) {
        $time = strtotime($_POST[$fieldName].' '.$_POST[$fieldName.'_time']);
        unset($_POST[$fieldName]);
        unset($_POST[$fieldName.'_time']);        
        return date('Y-m-d H:i', $time);
    }
}


?>
