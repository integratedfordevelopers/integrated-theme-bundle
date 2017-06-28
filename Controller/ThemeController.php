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
use Integrated\Bundle\ThemeBundle\Templating\Finder\ThemePathFinder;
use Integrated\Bundle\ThemeBundle\Templating\Helper\ThemeManagerHelper;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Christiaan Goslinga
 */
class ThemeController
{
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
     * @var ThemePathFinder
     */
    protected $themePathFinder;

    /**
     * @param MenuBuilder $menuBuilder
     * @param FlashMessage $flashMessage
     * @param ThemeManagerHelper $themeManagerHelper
     * @param TwigEngine $twigEngine
     * @param UrlGeneratorInterface $urlGenerator
     * @param Registry $doctrine
     * @param FormFactoryInterface $formFactoryInterface
     */
    public function __construct(
        MenuBuilder $menuBuilder,
        FlashMessage $flashMessage,
        ThemeManagerHelper $themeManagerHelper,
        TwigEngine $twigEngine,
        UrlGeneratorInterface $urlGenerator,
        Registry $doctrine,
        FormFactoryInterface $formFactoryInterface,
        ThemePathFinder $themePathFinder
    ) {
        $this->menuBuilder = $menuBuilder;
        $this->flashMessage = $flashMessage;
        $this->themeManagerHelper = $themeManagerHelper;
        $this->twigEngine = $twigEngine;
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
        $this->formFactoryInterface = $formFactoryInterface;
        $this->themePathFinder = $themePathFinder;
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
        $realPath = $this->themePathFinder->findThemePath($id);

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
        $link = urldecode($theme);
        $title = ltrim(strrchr($link, "/"), "/");
        $themePath = $this->themePathFinder->findThemeContent($link);

        $form = $this->formFactoryInterface->create(ThemeEditorType::class, $themePath);
        $form->handleRequest($request);

        $em = $this->doctrine->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $themePathContent = $form->getData();

            $themePath->setContent($themePathContent->getContent());

            $em->persist($themePath);
            $em->flush();

            $this->flashMessage->success(sprintf('The changes to the theme %s are saved', $title));

            return new RedirectResponse(
                $this->urlGenerator->generate(
                    'integrated_theme_theme_edit_theme',
                    ["id" => $id, "theme" => $theme]
                )
            );
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
