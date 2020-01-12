<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Date extends Component implements ComponentInterface
{
    public $field_sql = 'DATE';

    public function field(string $field_name, $value = '', array $options = []): void
    {
        ?>
        <input type="date" name="<?= $field_name; ?>" value="<?= ($value && '0000-00-00' != $value) ? $value : ''; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?>>
        <?php
    }

    public function value($value, $name = '')
    {
        if (starts_with($value, '0000-00-00')) {
            $value = '';
        } elseif ('' != $value) {
            $value = dateformat('d/m/Y', $value);
        }
        return $value;
    }

    public function is_valid($value): bool
    {
        return preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $value);
    }

    public function conditions_to_sql($field_name, $value, $func = '', $table_prefix = ''): string
    {
        if ('now' == $value) {
            $start = 'NOW()';
        } elseif ('month' == $func) {
            $start = dateformat('mY', $value);
        } else {
            $start = "'" . escape(dateformat('Y-m-d', $value)) . "'";
        }

        if ('month' == $func) {
            $where = 'date_format(' . $table_prefix . $field_name . ", '%m%Y') = '" . escape($value) . "'";
        } elseif ('year' == $func) {
            $where = 'date_format(' . $table_prefix . $field_name . ", '%Y') = '" . escape($value) . "'";
        } elseif ($value and is_array($func) and $func['end']) {
            $end = escape($func['end']);

            $where = '(' . $table_prefix . $field_name . ' >= ' . $start . ' AND ' . $table_prefix . $field_name . " <= '" . $end . "')";
        } else {
            if (!in_array($func, ['=', '!=', '>', '<', '>=', '<='])) {
                $func = '=';
            }

            $where = $table_prefix . $field_name . ' ' . escape($func) . ' ' . $start;
        }

        return $where;
    }

    public function search_field($name, $value): void
    {
        $field_name = underscored($name); ?>
        <div>
            <label class="col-form-label"><?= ucfirst($name); ?></label>

            <div>
                <div style="float:left">
                    From&nbsp;
                </div>
                <div style="float:left">
                    <input type="text" name="<?= $field_name; ?>" value="<?= $_GET[$field_name]; ?>" size="8" data-type="date" autocomplete="off" class="form-control">
                </div>
                <div style="float:left">
                    &nbsp;To&nbsp;
                </div>
                <div style="float:left">
                    <input type="text" name="func[<?= $field_name; ?>][end]" value="<?= $_GET['func'][$field_name]['end']; ?>" size="8" data-type="date" autocomplete="off" class="form-control">
                </div>
                <br style="clear: both;">
            </div>
        </div>
        <br>
        <?php
    }
}
