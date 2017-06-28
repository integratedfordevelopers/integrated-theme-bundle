<?php

/*
* This file is part of the Integrated package.
*
* (c) e-Active B.V. <integrated@e-active.nl>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Integrated\Bundle\ThemeBundle\Menu;

/**
 * @author Christiaan Goslinga
 */
class MenuBuilder
{
    /**
     * @var int
     */
    protected $i = 0;

    /**
     * {@inheritdoc}
     */
    public function buildMenu(array $array, $theme)
    {
        $html = "<li>";
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                if ($html !== "<li>") {
                    $html .= "<li>";
                }
                if ($k == $theme) {
                    $html = "<li class='$k'>";
                }
                $html .= $k;
                $html .= "<ul>" . $this->buildMenu($v, $theme) . "</ul>";
            } else {
                if ($this->i == 0) {
                    if ($html == '<li>') {
                        $html = '<li data-jstree={"icon":"/bundles/integratedtheme/images/twigFileIcon.png","selected":true}>' . $v . "</li>";
                    } else {
                        $html .= '<li data-jstree={"icon":"/bundles/integratedtheme/images/twigFileIcon.png","selected":true}>' . $v . "</li>";
                    }
                    $this->i++;
                } else {
                    if ($html == '<li>') {
                        $html = '<li data-jstree={"icon":"/bundles/integratedtheme/images/twigFileIcon.png"}>' . $v . "</li>";
                    } else {
                        $html .= '<li data-jstree={"icon":"/bundles/integratedtheme/images/twigFileIcon.png"}>' . $v . "</li>";
                    }
                }
            }
        }

        return $html . "</li>";
    }
}
