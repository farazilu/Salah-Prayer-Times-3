<?php
namespace farazilu\salahtime3\variables;

use farazilu\salahtime3\SalahTime3;

use Craft;

class SalahTime3Varable
{

    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want. From any Twig template,
     * call it like this:
     *
     * {{ craft.olivemenus.getSahahTime3HTML }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     * {{ craft.olivemenus.getSahahTime3HTML(twigValue) }}
     *
     * @param null $handle
     * @param array $config
     *
     * @return string
     */

    public function getSahahTime3HTML($handle, $config = array())
    {
        if ($handle != '') {
            return SalahTime3::$plugin->olivemenus->getMenuHTML($handle, $config);
        }
    }
}
?>