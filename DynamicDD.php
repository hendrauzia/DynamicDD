<?php
class DynamicDD {
    // CUSTOM FORM
    // These can all be set independently when constructing the object
    private $_select_enable    = true;
	private $_select_attribute = "";

    /**
     * Select prompt message.
     */
    private $prompt = 'Please select';

    /**
     * Array of dropdown key name. Index start from 1.
     */
    private $keys;

    /**
     * Data for select options.
     */
    private $data;

    /**
     * Count of dynamic dropdown generated.
     */
	private $count = 0;

    /**
     * Dynamic dropdown group name.
     * This will be used as identifier beetween several dynamic dropdown.
     */
    private $group = 'dd';

    /**
     * State of current field on parent change.
     *
     * Possible values: none, hide.
     * Default: hide.
     */
    private $on_parent_change = "hide";

    /**
     * Boolean value of whether the javascript has already been printed.
     */
    private $javascript_printed = false;

    /**
     * Available options:
     * - group : String. Group name of several dynamic dropdown field.
     * - prompt : String. Select prompt message.
     * - select_attribute : String. HTML attributes for dropdown field. e.g. 'class="shinny" style="z-index:1"'
     * - on_parent_change : String. Available value: none, hide. Current field state on parent select change.
     * - select_enable : Boolean. Enable/disable select prompt.
     */
    public function __construct($options = [])
    {
        extract($options);

        if (isset($group)) $this->group = $group;
        if (isset($prompt)) $this->prompt = $prompt;
        if (isset($select_attribute)) $this->_select_attribute = $select_attribute;
        if (isset($on_parent_change)) $this->on_parent_change = $on_parent_change;
        if (isset($select_enable)) {
            $this->_select_enable = $select_enable;
            if (!$this->_select_enable) $this->prompt = '';
        }
    }

    public function disableSelectMessage()
    {
        $this->_select_enable = false;
    }

    /**
     * Generate dynamic dropdown field.
     * TODO: Assign selected value.
     *
     * Available parameter for params
     * - prompt : String. Select prompt message.
     * - data : Array. Data for option.
     * - name : String. Name for select field.
     *
     * @param $params Array.
     * @return String
     */
    public function dropdown($params = [])
    {
        // TODO: currently limit to only 3 level dropdown.
        if ($this->count <= 3) {
            extract($params);

            // Validate required parameters
            if (empty($name)) throw new Exception('Dropdown name is required.');
            if (empty($key)) throw new Exception('Dropdown key is required.');

            $this->count++;
            $this->keys[$this->count] = $key;

            if (empty($prompt)) $prompt = $this->prompt;

            $output = '';
            $output .= '<select name="' . $name . '" id="' . $this->group . '_level' . $this->count . 'DD" ';
            if ($this->count > 1) $output .= ' data-parent="' . $this->keys[$this->count - 1] . '" ';
            // data attributes
            $output .= ' data-plugin="DynamicDD" data-group="' . $this->group . '" data-key="' . $key . '" data-on-parent-change="' . $this->on_parent_change . '" data-prompt="' . $prompt . '" ';
            // custom html attributes
            $output .= $this->_select_attribute . ' >';

            $output .= '<option>' . $prompt . '</option>';
            if ($this->count == 1)
                foreach ($data[$key] as $row)
                    $output .= '<option value="' . $row['value'] . '">' . $row['title'] . '</option>';

            $output .= '</select>';

            if (!empty($data)) $this->data = $data;
            return $output . $this->javascript();
        }
    }

    /**
     * Generate javascript for content binding.
     */
    protected function javascript()
    {
        // TODO: should be checking javascript_printed NOT count.
        if ($this->count < 3) return '';
        $data = $this->data;

        $data_name = "data_" . $this->group;
        $json = json_encode($data);

        $output = <<<"EOT"
        <script type="text/javascript">
        $(document).ready(function() {
            var {$data_name} = {$json};

            var id_1 = "#{$this->group}_level1DD";
            var id_2 = "#{$this->group}_level2DD";

            // generate data-child attribute on parent dropdown
            $.each($('[data-plugin=DynamicDD][data-group={$this->group}][data-parent]'), function(index, item) {
                var parent_key = $(item).attr('data-parent');
                var current_key = $(item).attr('data-key');

                reset('#' + $(item).attr('id'));
                $('[data-plugin=DynamicDD][data-group={$this->group}][data-key=' + parent_key + ']').attr('data-child', current_key);
            })

            $(document).on('change', id_1, function(){
                var current = id_1;
                var children = '[data-plugin=DynamicDD][data-group={$this->group}][data-key=' + $(current).attr('data-child') + ']';

                var index = $(current).get(0).selectedIndex;

                reset(children);
                $(children).trigger('change');

                if (index !== 0) {
                    var current_key = $(current).attr('data-key');
                    var child = $(current).attr('data-child');

                    var data = {$data_name}[current_key][index];
                    update(child, data);
                }
            });

            $(document).on('change', id_2, function(){
                var index1 = $(id_1).get(0).selectedIndex;
                var parent_data = {$data_name}['level1'][index1];

                // TODO: refactor code below to a single event handler for all dynamic dropdown.
                var current = id_2;
                var children = '[data-plugin=DynamicDD][data-group={$this->group}][data-key=' + $(current).attr('data-child') + ']';

                var index = $(current).get(0).selectedIndex;

                reset(children);
                $(children).trigger('change');

                if (index !== 0) {
                    var current_key = $(current).attr('data-key');
                    var child = $(current).attr('data-child');

                    var data = parent_data[current_key][index];
                    update(child, data);
                }
            });

            function reset(selector) {
                $(selector).html('<option>' + $(selector).attr('data-prompt') + '</option>');
                $(selector + "[data-on-parent-change=hide]").hide();
            }

            /**
             * Update select option.
             *
             * current : jQuery selector
             * parent : jQuery selector
             * data : key value array
             * key : data index e.g. data[key]
             * value : value index name e.g. data[key][value]
             * title : title index name e.g. data[key][title]
             */
            function update(key, data, value, title) {
                value = typeof value !== 'undefined' ? value : 'value';
                title = typeof title !== 'undefined' ? title : 'title';

                var selector = '[data-plugin=DynamicDD][data-group={$this->group}][data-key=' +  key + ']';
                var options = '<option>' + $(selector).attr('data-prompt') + '</option>';

                $.each(data[key], function(index, option){
                    options += '<option value="' + option[value] + '" >' + option[title] + '</option>';
                });

                $(selector).html(options);
                $(selector + "[data-on-parent-change=hide]").show();
            }
        });
        </script>
EOT;

        $this->javascript_printed = true;
        return $output;
    }
}
