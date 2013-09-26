<?php
/**
 * NoFramework
 *
 * @author Roman Zaykin <roman@noframework.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://noframework.com
 */

namespace NoFramework\Render;

class Json extends Base
{
    public $hex_quot = false;
    public $hex_tag = false;
    public $hex_amp = false;
    public $hex_apos = false;
    public $numeric_check = false;
    public $pretty_print = false;
    public $unescaped_slashes = false;
    public $force_object = false;
    public $unescaped_unicode = false;
    public $depth = 512;

    protected function __property_options()
    {
        $options = 0;

        if ($this->hex_quot) {
            $options |= JSON_HEX_QUOT;
        }

        if ($this->hex_tag) {
            $options |= JSON_HEX_TAG;
        }

        if ($this->hex_amp) {
            $options |= JSON_HEX_AMP;
        }

        if ($this->hex_apos) {
            $options |= JSON_HEX_APOS;
        }

        if ($this->numeric_check) {
            $options |= JSON_NUMERIC_CHECK;
        }

        if ($this->pretty_print) {
            $options |= JSON_PRETTY_PRINT;
        }

        if ($this->unescaped_slashes) {
            $options |= JSON_UNESCAPED_SLASHES;
        }

        if ($this->force_object) {
            $options |= JSON_FORCE_OBJECT;
        }

        if ($this->unescaped_unicode) {
            $options |= JSON_UNESCAPED_UNICODE;
        }

        return $options;
    }

    public function __invoke($data = [])
    {
        return json_encode(
            is_array($data) ? $data : compact('data'),
            $this->options,
            $this->depth
        );
    }
}

