<?php
/**
 * Created by PhpStorm.
 * User: Christiaan Goslinga
 * Date: 3-4-2017
 * Time: 16:41
 */

namespace Integrated\Bundle\ThemeBundle\Menu;

class MenuBuilder
{
    private $i = 0;

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
                        $html = '<li data-jstree={"icon":"/images/twigFileIcon.png","selected":true}>' . $v . "</li>";
                    } else {
                        $html .= '<li data-jstree={"icon":"/images/twigFileIcon.png","selected":true}>' . $v . "</li>";
                    }
                    $this->i++;
                } else {
                    if ($html == '<li>') {
                        $html = '<li data-jstree={"icon":"/images/twigFileIcon.png"}>' . $v . "</li>";
                    } else {
                        $html .= '<li data-jstree={"icon":"/images/twigFileIcon.png"}>' . $v . "</li>";
                    }
                }
            }
        }

        return $html . "</li>";
    }
}
