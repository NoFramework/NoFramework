<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Http\View;

class Factory implements \ArrayAccess
{
    use \NoFramework\ArrayShortcut;

    protected $charset = 'utf-8';
    protected $template_extension = '.html.twig';
    protected $template_path = [];

    /**
     * cache
     * debug
     * auto_reload
     * strict_variables
     * base_template_class
     * autoescape
     * optimizations
     */
    protected $twig_options = [];

    /**
     * is_hex_quot
     * is_hex_tag
     * is_hex_amp
     * is_hex_apos
     * is_numeric_check
     * is_pretty_print
     * is_unescaped_slashes
     * is_force_object
     * is_unescaped_unicode
     * depth (default: 512)
     */
    protected $json_options = [];

    protected function loadTemplate($name, $filters = [])
    {
        $out = new \Twig_Environment(
            new \Twig_Loader_Filesystem($this->template_path),
            array_merge(
                ['charset' => $this->charset],
                $this->twig_options
            )
        );
        
        foreach ($filters as $filter_name => $filter) {
            $out->addFilter(new \Twig_SimpleFilter(
                $filter_name,
                $filter
            ));
        }

        $out->addFilter(new \Twig_SimpleFilter(
            'dump',
            function ($object) {
                return '<pre>' . print_r($object, true) . '</pre>';
            },
            ['is_safe' => ['html']]
        ));

        return $out->loadTemplate($name . $this->template_extension);
    }

    /**
     * template
     * filters
     * data
     */
    public function get($data = [])
    {
        if ($data instanceof Item) {
            return $data;
        }

        $state = [
            'json_options' => array_reduce([
                ['is_hex_quot', JSON_HEX_QUOT],
                ['is_hex_tag', JSON_HEX_TAG],
                ['is_hex_amp', JSON_HEX_AMP],
                ['is_hex_apos', JSON_HEX_APOS],
                ['is_numeric_check', JSON_NUMERIC_CHECK],
                ['is_pretty_print', JSON_PRETTY_PRINT],
                ['is_unescaped_slashes', JSON_UNESCAPED_SLASHES],
                ['is_force_object', JSON_FORCE_OBJECT],
                ['is_unescaped_unicode', JSON_UNESCAPED_UNICODE],
            ], function ($out, $item) {
                if (isset($this->json_options[$item[0]]) and $this->json_options[$item[0]]) {
                    $out |= $item[1];
                    return $out;
                }
            }, 0),
            'charset' => $this->charset,
        ];

        if (isset($this->json_options['depth'])) {
            $state['json_depth'] = $this->json_options['depth'];
        }

        if (is_string($data)) {
            $state['template'] = $this->loadTemplate($data);
        } else {
            if (isset($data['template'])) {
                $state['template'] = $this->loadTemplate(
                    $data['template'],
                    isset($data['filters']) ? $data['filters'] : []
                );

                unset($data['filters']);
            }

            $state['data'] = $data;
        }

        return new Item($state);
    }

    public function hasTemplate($template)
    {
        foreach ((array)$this->template_path as $template_path) {
            if (is_file(
                $template_path . DIRECTORY_SEPARATOR .
                $template . $this->template_extension
            )) {
                return true;
            }
        }

        return false;
    }
}

