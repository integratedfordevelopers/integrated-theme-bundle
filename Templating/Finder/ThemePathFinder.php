<?php

/*
 * This file is part of the Integrated package.
 *
 * (c) e-Active B.V. <integrated@e-active.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integrated\Bundle\ThemeBundle\Templating\Finder;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Integrated\Bundle\ThemeBundle\Templating\ThemeManager;
use Integrated\Bundle\ThemeBundle\Entity\ThemePath;

/**
 * @author Christiaan Goslinga
 */
class ThemePathFinder
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var string
     */
    protected $kernelPath;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ThemeManager $themeManager
     * @param $kernelPath
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ThemeManager $themeManager, $kernelPath, EntityManagerInterface $entityManager)
    {
        $this->themeManager = $themeManager;
        $this->kernelPath = $kernelPath;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function findThemePath($id)
    {
        $finder = new Finder();
        $paths = [];
        $pathArray = [];

        foreach ($this->themeManager->getThemes() as $routes) {
            if ($routes->getId() == $id) {
                foreach ($routes->getPaths() as $route) {
                    $routePath = strtolower($route);

                    $find = array("@integrated", "bundle", "theme", "resources", );
                    $replace = array(substr($this->kernelPath, 0, strrpos($this->kernelPath, '/')) . "/vendor/integrated/", "-bundle", "-theme", "Resources");

                    $falsePath = str_replace($find, $replace, $routePath);

                    $themeBundle = strstr($falsePath, "theme-bundle", true);

                    if (substr($themeBundle, -2) == "/-") {
                        $falsePath = str_replace("-theme-bundle", "theme-bundle", $falsePath);
                    }

                    $path = str_replace("-themes", "themes", $falsePath);

                    array_push($paths, explode("/", ltrim($path, "/")));

                    array_push($pathArray, $path);
                }
            }
        }

        foreach ($pathArray as $find) {
            $finder->files()->in($find);
        }

        $realPath = [];
        foreach ($finder as $file) {
            array_push($realPath, $file->getRealPath());
        }

        return $realPath;
    }

    public function findThemeContent($link)
    {
        $themePath = $this->entityManager->getRepository("IntegratedThemeBundle:ThemePath")->findOneBy(["path" => $link]);

        if (empty($themePath)) {
            $content = file_get_contents($link);

            $themePath = new ThemePath();

            $themePath->setContent($content);
            $themePath->setPath($link);
        }

        return $themePath;
    }
}