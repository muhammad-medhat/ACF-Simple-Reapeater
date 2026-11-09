<?php
/**
 * Plugin Name: MD Repeater for ACF Free
 * Description: A lightweight repeater field type for Advanced Custom Fields (free), with unlimited rows and configurable text, textarea, number and select subfields.
 * Version: 1.0.0
 * Author: Muhammad Medhat
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: simple-acf-repeater
 */

if (!defined('ABSPATH')) {
    exit;
}
//constants
define('MD_ACF_SIMPLE_REPEATER_VERSION', '1.0.0');
define('MD_ACF_SIMPLE_REPEATER_PATH', plugin_dir_path(__FILE__));
define('MD_ACF_SIMPLE_REPEATER_URL', plugin_dir_url(__FILE__));


add_action('acf/include_field_types', 'md_register_acf_simple_repeater');

function md_register_acf_simple_repeater() {
    if (!class_exists('acf_field') || !function_exists('acf_register_field_type')) {
        return;
    }

    class MD_ACF_Simple_Repeater_Field extends acf_field {
        public function __construct() {
            $this->name = 'md_repeater';
            $this->label = __('MD Repeater (Free)', 'simple-acf-repeater');
            $this->category = 'layout';
            $this->defaults = array(
                'subfields_definition' => "desc_ar|Arabic Description|text\ndesc_en|English Description|text\nprice|Price|number",
                'min_rows' => 0,
                'max_rows' => 0,
            );
            parent::__construct();
        }

        public function render_field_settings($field) {
            acf_render_field_setting($field, array(
                'label' => __('Subfields', 'simple-acf-repeater'),
                'instructions' => __('One subfield per line: name|Label|type. Types: text, textarea, number, select. For select add options: name|Label|select|value:Label,value2:Label 2. Names must be unique and use letters, digits, underscores.', 'simple-acf-repeater'),
                'type' => 'textarea',
                'name' => 'subfields_definition',
                'rows' => 6,
            ));
            acf_render_field_setting($field, array(
                'label' => __('Minimum rows', 'simple-acf-repeater'),
                'type' => 'number',
                'name' => 'min_rows',
                'min' => 0,
            ));
            acf_render_field_setting($field, array(
                'label' => __('Maximum rows (0 means unlimited)', 'simple-acf-repeater'),
                'type' => 'number',
                'name' => 'max_rows',
                'min' => 0,
            ));
        }

        private function definitions($field) {
            $raw = isset($field['subfields_definition']) ? (string) $field['subfields_definition'] : '';
            $result = array();
            foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line, 4));
                $name = $parts[0] ?? '';
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name) || isset($result[$name])) {
                    continue;
                }
                $type = $parts[2] ?? 'text';
                if (!in_array($type, array('text', 'textarea', 'number', 'select'), true)) {
                    $type = 'text';
                }
                $options = array();
                if ($type === 'select' && !empty($parts[3])) {
                    foreach (explode(',', $parts[3]) as $option) {
                        $pair = array_map('trim', explode(':', $option, 2));
                        if ($pair[0] !== '') {
                            $options[$pair[0]] = $pair[1] ?? $pair[0];
                        }
                    }
                }
                $result[$name] = array(
                    'name' => $name,
                    'label' => ($parts[1] ?? '') !== '' ? $parts[1] : $name,
                    'type' => $type,
                    'options' => $options,
                );
            }
            return array_values($result);
        }

        private function render_cell($definition, $value, $input_name, $template = false) {
            $type = $definition['type'];
            $name_attr = $template ? 'data-md-name' : 'name';
            $safe_value = is_scalar($value) ? (string) $value : '';
            if ($type === 'textarea') {
                printf('<textarea %s="%s" rows="2" class="widefat">%s</textarea>', esc_attr($name_attr), esc_attr($input_name), esc_textarea($safe_value));
            } elseif ($type === 'select') {
                printf('<select %s="%s" class="widefat">', esc_attr($name_attr), esc_attr($input_name));
                echo '<option value=""></option>';
                foreach ($definition['options'] as $key => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($safe_value, (string) $key, false), esc_html($label));
                }
                echo '</select>';
            } else {
                printf('<input type="%s" %s="%s" value="%s" class="widefat" %s>', esc_attr($type), esc_attr($name_attr), esc_attr($input_name), esc_attr($safe_value), $type === 'number' ? 'step="any"' : '');
            }
        }

        private function render_row($definitions, $row, $field_name, $index, $template = false) {
            echo '<tr class="md-repeater-row">';
            echo '<td class="md-repeater-handle"><button type="button" class="button-link md-repeater-drag" aria-label="Move row" title="Drag to reorder">&#9776;</button><span class="md-repeater-number">' . ($template ? '#' : esc_html((string) ($index + 1))) . '</span></td>';
            foreach ($definitions as $def) {
                echo '<td>';
                $input_name = $field_name . '[' . $index . '][' . $def['name'] . ']';
                $this->render_cell($def, $row[$def['name']] ?? '', $input_name, $template);
                echo '</td>';
            }
            echo '<td><button type="button" class="button-link-delete md-repeater-remove">' . esc_html__('Remove', 'simple-acf-repeater') . '</button></td>';
            echo '</tr>';
        }

        public function render_field($field) {
            $definitions = $this->definitions($field);
            if (!$definitions) {
                echo '<p>' . esc_html__('Configure at least one valid subfield in the field group.', 'simple-acf-repeater') . '</p>';
                return;
            }
            $rows = is_array($field['value']) ? array_values($field['value']) : array();
            $rows = array_filter($rows, 'is_array');
            $max = max(0, (int) ($field['max_rows'] ?? 0));
            $min = max(0, (int) ($field['min_rows'] ?? 0));
            $input_name = $field['name'];
            $uid = wp_unique_id('md-repeater-');
            printf('<div class="md-repeater" id="%s" data-min="%d" data-max="%d" data-input-name="%s">', esc_attr($uid), $min, $max, esc_attr($input_name));
            // Ensures an empty repeater is submitted when all rows are removed.
            printf('<input type="hidden" name="%s" value="">', esc_attr($input_name));
            echo '<div class="md-repeater-scroll"><table class="widefat striped"><thead><tr><th class="md-repeater-handle">#</th>';
            foreach ($definitions as $def) {
                echo '<th>' . esc_html($def['label']) . '</th>';
            }
            echo '<th>' . esc_html__('Actions', 'simple-acf-repeater') . '</th></tr></thead><tbody class="md-repeater-rows">';
            $index = 0;
            foreach ($rows as $row) {
                $this->render_row($definitions, $row, $input_name, $index++);
            }
            echo '</tbody></table></div>';
            echo '<p><button type="button" class="button button-secondary md-repeater-add">' . esc_html__('Add row', 'simple-acf-repeater') . '</button> <span class="md-repeater-count"></span></p>';
            echo '<template class="md-repeater-template">';
            $this->render_row($definitions, array(), '__MD_FIELD_NAME__', '__INDEX__', true);
            echo '</template>';
            echo '</div>';
        }

        public function load_value($value, $post_id, $field) {
            return is_array($value) ? array_values(array_filter($value, 'is_array')) : array();
        }

        public function update_value($value, $post_id, $field) {
            $definitions = $this->definitions($field);
            if (!is_array($value)) {
                return array();
            }
            $max = max(0, (int) ($field['max_rows'] ?? 0));
            $sanitized = array();
            foreach ($value as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $clean = array();
                $has_value = false;
                foreach ($definitions as $def) {
                    $raw = $row[$def['name']] ?? '';
                    $raw = is_scalar($raw) ? (string) wp_unslash($raw) : '';
                    switch ($def['type']) {
                        case 'textarea':
                            $v = sanitize_textarea_field($raw);
                            break;
                        case 'number':
                            $v = ($raw !== '' && is_numeric($raw)) ? (string) (float) $raw : '';
                            break;
                        case 'select':
                            $v = array_key_exists($raw, $def['options']) ? $raw : '';
                            break;
                        default:
                            $v = sanitize_text_field($raw);
                    }
                    $clean[$def['name']] = $v;
                    if ($v !== '') {
                        $has_value = true;
                    }
                }
                if ($has_value) {
                    $sanitized[] = $clean;
                }
                if ($max && count($sanitized) >= $max) {
                    break;
                }
            }
            return $sanitized;
        }

        public function validate_value($valid, $value, $field, $input) {
            if ($valid !== true) {
                return $valid;
            }
            $count = 0;
            if (is_array($value)) {
                foreach ($value as $row) {
                    if (is_array($row) && count(array_filter($row, static function ($v) { return is_scalar($v) && trim((string) $v) !== ''; }))) {
                        ++$count;
                    }
                }
            }
            $min = max(0, (int) ($field['min_rows'] ?? 0));
            $max = max(0, (int) ($field['max_rows'] ?? 0));
            if ($count < $min) {
                return sprintf(__('At least %d rows are required.', 'simple-acf-repeater'), $min);
            }
            if ($max && $count > $max) {
                return sprintf(__('No more than %d rows are allowed.', 'simple-acf-repeater'), $max);
            }
            return true;
        }

        public function input_admin_enqueue_scripts() {
            wp_enqueue_style('simple-acf-repeater', MD_ACF_SIMPLE_REPEATER_URL. 'assets/repeater.css', array(), '1.0.0');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('simple-acf-repeater', MD_ACF_SIMPLE_REPEATER_URL. 'assets/repeater.js', array('jquery', 'jquery-ui-sortable'), '1.0.0', true);
        }
    }

    acf_register_field_type('MD_ACF_Simple_Repeater_Field');
}