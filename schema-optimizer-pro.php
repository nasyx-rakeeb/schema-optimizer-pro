<?php
/*
* Plugin Name: Schema Optimizer Pro
* Text Domain: schema-optimizer-pro
* Description: Schema Optimizer Pro lets you easily add schema markup to your website, increasing search visibility and traffic, with ability to customize schema markup using custom fields, optimize performance with caching and validate with debug mode.
* Version: 1.0
* Requires PHP: 5.6
* Requires at least: 4.5
* Tested up to: 6.1.1
* Plugin URI: https://github.com/nasyx-rakeeb/schema-optimizer-pro
* Author URI: https://nasyxrakeeb.vercel.app
* Author: Nasyx Rakeeb
* License: GPL2
* License URI: https://github.com/nasyx-rakeeb/schema-optimizer-pro/blob/main/LICENSE.txt
*/

class SEO_Schema_Markup_Generator {
    public function __construct() {
        add_action('wp_head', array($this, 'generate_schema_markup'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));

        function add_settings_link($links) {
            $settings_link = '<a href="admin.php?page=schema-optimizer-pro">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_settings_link');
    }

    public function add_settings_page() {
        add_menu_page('Schema Optimizer Pro', 'Schema Optimizer Pro', 'manage_options', 'schema-optimizer-pro', array($this, 'display_settings_page'), 'dashicons-admin-generic', 99);
    }

    public function register_settings() {
        register_setting('schema-optimizer-pro-options', 'schema-optimizer-pro-options', array($this, 'sanitize_options'), array('custom_fields' => '', 'cache_enabled' => 0, 'debug_mode' => 0));
    }

    public function display_settings_page() {
        $options = get_option('schema-optimizer-pro-options');
        ?>
        <div class="wrap">
            <h1>Schema Optimizer Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('schema-optimizer-pro-options'); ?>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="schema-optimizer-pro-options[custom_fields]">Custom Fields</label>
                        </th>
                        <td>
                            <input type="text" id="schema-optimizer-pro-options[custom_fields]" name="schema-optimizer-pro-options[custom_fields]" value="<?php echo esc_attr(isset($options['custom_fields']) ? $options['custom_fields'] : ''); ?>" class="regular-text">
                            <p class="description">
                                Enter a comma-separated list of custom fields to use for the schema markup.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>

                        </th>
                        <td>
                            <input type="checkbox" id="schema-optimizer-pro-options[cache_enabled]" name="schema-optimizer-pro-options[cache_enabled]" value="1" <?php checked(isset($options['cache_enabled']) ? $options['cache_enabled'] : 0, 1); ?>>
                            <label for="schema-optimizer-pro-options[cache_enabled]"><strong>Enable caching</strong></label>
                            <p class="description">
                                Enable caching of the generated schema markup for improved performance.
                            </p>
                        </td>

                    </tr>
                    <tr>
                        <th>

                        </th>
                        <td>
                            <input type="checkbox" id="schema-optimizer-pro-options[debug_mode]" name="schema-optimizer-pro-options[debug_mode]" value="1" <?php checked(isset($options['debug_mode']) ? $options['debug_mode'] : 0, 1); ?>>
                            <label for="schema-optimizer-pro-options[debug_mode]"><strong>Debug mode</strong></label>
                            <p class="description">
                                Enable debug mode to view and validate the generated schema markup.
                            </p>
                        </td>

                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function sanitize_options($options) {
        $options['custom_fields'] = (isset($options['custom_fields']) ? sanitize_text_field($options['custom_fields']) : '');
        $options['cache_enabled'] = (isset($options['cache_enabled']) ? intval($options['cache_enabled']) : 0);
        $options['debug_mode'] = (isset($options['debug_mode']) ? intval($options['debug_mode']) : 0);
        return $options;
    }

    public function generate_schema_markup() {
        global $post;

        $options = get_option('schema-optimizer-pro-options');

        if ($options['cache_enabled']) {
            // check for cached schema markup
            $cached_schema = get_transient('schema_markup_' . $post->ID);
            if ($cached_schema) {
                echo $cached_schema;
                return;
            }
        }

        $schema = array();

        if (is_single()) {
            $schema['@context'] = "http://schema.org";
            $schema['@type'] = "Article";
            $schema['headline'] = get_the_title();
            $schema['datePublished'] = get_the_date('c');
            $schema['dateModified'] = get_the_modified_date('c');
            $schema['author'] = array(
                '@type' => 'Person',
                'name' => get_the_author()
            );
            $schema['publisher'] = array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            );
            $schema['image'] = get_the_post_thumbnail_url();
            $schema['mainEntityOfPage'] = get_the_permalink();

            // add custom fields
            $custom_fields = explode(',', $options['custom_fields']);
            foreach ($custom_fields as $custom_field) {
                $custom_field = trim($custom_field);
                $schema[$custom_field] = get_post_meta($post->ID, $custom_field, true);
            }
        }

        if (!empty($schema)) {
            $schema_script = '<script type="application/ld+json">' . json_encode($schema) . '</script>';

            if ($options['debug_mode']) {
                // validate schema markup
                $validator = new JsonLdValidator();
                $validation_errors = $validator->validate($schema_script);

                if (count($validation_errors) > 0) {
                    echo '<!-- Schema Markup Validation Errors: ' . implode(', ', $validation_errors) . ' -->';
                } else {
                    echo $schema_script;
                }
            } else {
                echo $schema_script;
            } if ($options['cache_enabled']) {
                // cache the schema markup for 1 hour
                set_transient('schema_markup_' . $post->ID, $schema_script, HOUR_IN_SECONDS);
            }
        }
    }
}

new SEO_Schema_Markup_Generator();