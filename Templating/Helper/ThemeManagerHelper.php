<?php

/*
 * This file is part of the Integrated package.
 *
 * (c) e-Active B.V. <integrated@e-active.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integrated\Bundle\ThemeBundle\Templating\Helper;

use Integrated\Bundle\ThemeBundle\Templating\ThemeManager;

/**
 * @author Christiaan Goslinga
 */
class ThemeManagerHelper
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * ThemeManagerHelper constructor.
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function getRouteId()
    {
        $routeId = [];

        foreach ($this->themeManager->getThemes() as $routes) {
            array_push($routeId, $routes->getId());
        }

        return $routeId;
    }
}