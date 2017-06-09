<?php

/*
 * This file is part of the Integrated package.
 *
 * (c) e-Active B.V. <integrated@e-active.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integrated\Bundle\ThemeBundle\Controller;

use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Integrated\Bundle\ThemeBundle\Form\Type\ThemeEditorType;
use Integrated\Bundle\ThemeBundle\Menu\MenuBuilder;
use Integrated\Bundle\ThemeBundle\Templating\Helper\ThemeManagerHelper;
use Integrated\Bundle\ThemeBundle\Templating\ThemeManager;
use Integrated\Common\Channel\Connector\Adapter\RegistryInterface;
use Integrated\Common\Channel\Connector\Config\ConfigManagerInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Integrated\Bundle\ThemeBundle\Entity\ThemePath;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Christiaan Goslinga
 */
class ThemeController
{
    /**
     * @var ConfigManagerInterface
     */
    protected $manager;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var MenuBuilder
     */
    protected $menuBuilder;

    /**
     * @var FlashMessage
     */
    protected $flashMessage;

    /**
     * @var ThemeManagerHelper
     */
    protected $themeManagerHelper;

    /**
     * @var TwigEngine
     */
    protected $twigEngine;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactoryInterface;

    /**
     * @param ConfigManagerInterface $manager
     * @param RegistryInterface $registry
     * @param ThemeManager $themeManager
     * @param MenuBuilder $menuBuilder
     * @param FlashMessage $flashMessage
     * @param ThemeManagerHelper $themeManagerHelper
     * @param TwigEngine $twigEngine
     * @param UrlGeneratorInterface $urlGenerator
     * @param Registry $doctrine
     * @param FormFactoryInterface $formFactoryInterface
     */
    public function __construct(
        ConfigManagerInterface $manager,
        RegistryInterface $registry,
        ThemeManager $themeManager,
        MenuBuilder $menuBuilder,
        FlashMessage $flashMessage,
        ThemeManagerHelper $themeManagerHelper,
        TwigEngine $twigEngine,
        UrlGeneratorInterface $urlGenerator,
        Registry $doctrine,
        FormFactoryInterface $formFactoryInterface
    ) {
        $this->manager = $manager;
        $this->registry = $registry;
        $this->themeManager = $themeManager;
        $this->menuBuilder = $menuBuilder;
        $this->flashMessage = $flashMessage;
        $this->themeManagerHelper = $themeManagerHelper;
        $this->twigEngine = $twigEngine;
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
        $this->formFactoryInterface = $formFactoryInterface;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->twigEngine->renderResponse(
            'IntegratedThemeBundle:Theme:index.html.twig',
            [
                'routeId' => $this->themeManagerHelper->getRouteId()
            ]
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     * @param string  $theme
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id, $theme = null)
    {
        $finder = new Finder();
        $paths = [];
        $pathArray = [];

        foreach ($this->themeManager->getThemes() as $routes) {
            if ($routes->getId() == $id) {
                foreach ($routes->getPaths() as $route) {
                    $routePath = strtolower($route);

                    $find = array("@integrated", "bundle", "theme", "resources", );
                    $replace = array("/vagrant/vendor/integrated/", "-bundle", "-theme", "Resources");

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
        foreach ($finder as $f) {
            array_push($realPath, $f->getRealPath());
        }

        if (null === $theme) {
            return new RedirectResponse(
                $this->urlGenerator->generate(
                    'integrated_theme_theme_edit_theme',
                    ["id" => $id, "theme" => urlencode($realPath[0])]
                )
            );
        }

        $menuLink = strstr($realPath[0], "integrated", true) . "integrated";

        $result = array();
        $count = array();

        foreach ($realPath as $item) {
            $shorturl = str_replace(array("integrated/"), "", strstr($item, "integrated"));

            array_push($count, substr_count(strstr($shorturl, $id . "/", true), "/"));

            $parts = explode('/', $shorturl);
            $current = &$result;
            for ($i = 1, $max = count($parts); $i < $max; $i++) {
                if (!isset($current[$parts[$i-1]])) {
                    $current[$parts[$i-1]] = array();
                }
                $current = &$current[$parts[$i-1]];
            }
            $current[] = $parts[$i-1];
        }

        $level = max($count) + 1;

        $list = $this->menuBuilder->buildMenu($result, $id);

        $em = $this->doctrine->getManager();

        if (strpos($theme, '/') === false) {
            $link = urldecode($theme);
        } else {
            $link = $theme;
        }

        $title = ltrim(strrchr($link, "/"), "/");

        $themePath = $em->getRepository("IntegratedThemeBundle:ThemePath")->findOneBy(["path" => $link]);

        if (empty($themePath)) {
            $content = file_get_contents($link);

            $themePath = new ThemePath();

            $themePath->setContent($content);
            $themePath->setPath($link);
        }

        $form = $this->formFactoryInterface->create(ThemeEditorType::class, $themePath);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $themePathContent = $form->getData();

            $themePath->setContent($themePathContent->getContent());

            $em->persist($themePath);
            $em->flush();

            $this->flashMessage->success(sprintf('The changes to the theme %s are saved', $title));

            return new RedirectResponse('integrated_theme_theme_edit_theme', ["id" => $id, "theme" => $theme]);
        }


        return $this->twigEngine->renderResponse('IntegratedThemeBundle:Theme:edit.html.twig', [
            'list' => $list,
            'editForm' => $form->createView(),
            'menuLink' => $menuLink,
            'title' => $title,
            'level' => $level,
            'themeId' => $id
        ]);
    }
}
