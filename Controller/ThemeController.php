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

use Integrated\Bundle\ChannelBundle\Form\Type\ActionsType;
use Integrated\Bundle\ChannelBundle\Form\Type\ConfigFormType;
use Integrated\Bundle\ChannelBundle\Form\Type\DeleteFormType;
use Integrated\Bundle\ChannelBundle\Model\Config;
use Integrated\Bundle\ThemeBundle\Form\Type\ThemeEditorType;
use Integrated\Common\Channel\Connector\Adapter\RegistryInterface;
use Integrated\Common\Channel\Connector\AdapterInterface;
use Integrated\Common\Channel\Connector\Config\ConfigManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Finder\Finder;

use Integrated\Bundle\ThemeBundle\Entity\ThemePath;

/**
 * @author Jan Sanne Mulder <jansanne@e-active.nl>
 */
class ThemeController extends Controller
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
     * Constructor/
     *
     * @param ConfigManagerInterface $manager
     * @param RegistryInterface      $registry
     * @param ContainerInterface     $container
     */
    public function __construct(
        ConfigManagerInterface $manager,
        RegistryInterface $registry,
        ContainerInterface $container
    ) {
        $this->manager = $manager;
        $this->registry = $registry;

        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $themeManager = $this->get('integrated_theme.templating.theme_manager');

        $routeId = [];

        foreach ($themeManager->getThemes() as $routes) {
            array_push($routeId, $routes->getId());
        }

        return $this->render('IntegratedThemeBundle:Theme:index.html.twig', [
            'routeId' => $routeId
        ]);
    }

    /**
     * @param Request $request
     * @param string  $id
     * @param string  $theme
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id, $theme)
    {
        $themeManager = $this->get('integrated_theme.templating.theme_manager');
        $finder = new Finder();
        $paths = [];
        $pathArray = [];

        foreach ($themeManager->getThemes() as $routes) {
            if ($routes->getId() == $id) {
                foreach ($routes->getPaths() as $route) {
                    $routePath = strtolower($route);

                    $find = array("@integrated", "bundle", "theme", "resources");
                    $replace = array("/vagrant/vendor/integrated/", "-bundle", "-theme", "Resources");

                    $falsePath = str_replace($find, $replace, $routePath);
                    $path = str_replace("-themes", "themes", $falsePath);

                    array_push($paths, explode("/", ltrim($path, "/")));

                    array_push($pathArray, $path);
                }
            }
        }

        foreach ($pathArray as $find) {
            $finder->files()->in($find);
        }

        $d = [];
        $realPath = [];
        foreach ($finder as $f) {
            array_push($realPath, $f->getRealPath());

            array_push($d, explode("/", ltrim($f->getRealPath(), "/")));
        }

        if ($theme == "false" || empty($theme)) {
            return $this->redirectToRoute(
                'integrated_theme_theme_edit',
                ["id" => $id, "theme" => urlencode($realPath[0])]
            );
        }

        $menulink = strstr($realPath[0], "integrated", true) . "integrated";

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

        $menu = $this->get("integrated_theme.menu.menubuilder");

        $list = $menu->buildMenu($result, $id);

        $em = $this->getDoctrine()->getManager();

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

        $form = $this->createForm(ThemeEditorType::class, $themePath);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $themePathContent = $form->getData();

            $themePath->setContent($themePathContent->getContent());

            $em = $this->getDoctrine()->getManager();
            $em->persist($themePath);
            $em->flush();

            if ($message = $this->getFlashMessage()) {
                $message->success(sprintf('The changes to the theme %s are saved', $title));
            }

            return $this->redirectToRoute('integrated_theme_theme_edit', ["id" => $id, "theme" => $theme]);
        }


        return $this->render('IntegratedThemeBundle:Theme:edit.html.twig', [
            'list' => $list,
            'editForm' => $form->createView(),
            'menulink' => $menulink,
            'title' => $title,
            'level' => $level,
            'themeId' => $id
        ]);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $id)
    {
        $data = $this->manager->find($id);

        // It should always be possible to delete a connector even if the adaptor itself does
        // not exist anymore. So unlike the new and edit actions this action will not throw a
        // not found exception when the adaptor does not exist.

        if (!$data) {
            return $this->redirect($this->generateUrl('integrated_channel_config_index')); // data is already gone
        }

        $form = $this->createDeleteForm($data);

        if ($request->isMethod('delete')) {
            $form->handleRequest($request);

            if ($form->get('actions')->getData() == 'cancel') {
                return $this->redirect($this->generateUrl('integrated_channel_config_index'));
            }

            if ($form->isValid()) {
                $this->manager->remove($data);

                if ($message = $this->getFlashMessage()) {
                    $message->success(sprintf('The config %s is removed', $data->getName()));
                }

                return $this->redirect($this->generateUrl('integrated_channel_config_index'));
            }
        }

        return $this->render('IntegratedChannelBundle:Config:delete.html.twig', [
            'adapter' => $this->registry->hasAdapter(
                $data->getAdapter()
            ) ? $this->registry->getAdapter($data->getAdapter()) : null,
            'data'    => $data,
            'form'    => $form->createView()
        ]);
    }

    /**
     * @param Config           $data
     * @param AdapterInterface $adapter
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createNewForm(Config $data, AdapterInterface $adapter)
    {
        $form = $this->createForm(ConfigFormType::class, $data, [
            'adapter' => $adapter,
            'action'  => $this->generateUrl(
                'integrated_channel_config_new',
                ['adapter' => $adapter->getManifest()->getName()]
            ),
            'method'  => 'POST',
        ]);

        $form->add('actions', ActionsType::class, ['buttons' => ['create', 'cancel']]);

        return $form;
    }

    /**
     * @param Config           $data
     * @param AdapterInterface $adapter
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createEditForm(Config $data, AdapterInterface $adapter)
    {
        $form = $this->createForm(ConfigFormType::class, $data, [
            'adapter' => $adapter,
            'action'  => $this->generateUrl('integrated_channel_config_edit', ['id' => $data->getName()]),
            'method'  => 'PUT',
        ]);

        $form->add('actions', ActionsType::class, ['buttons' => ['save', 'cancel']]);

        return $form;
    }

    /**
     * @param Config $data
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createDeleteForm(Config $data)
    {
        $form = $this->createForm(DeleteFormType::class, $data, [
            'action'  => $this->generateUrl('integrated_channel_config_delete', ['id' => $data->getName()]),
            'method'  => 'DELETE',
        ]);

        $form->add('actions', ActionsType::class, ['buttons' => ['delete', 'cancel']]);

        return $form;
    }

    /**
     * @return \Knp\Component\Pager\Paginator
     */
    protected function getPaginator()
    {
        return $this->get('knp_paginator');
    }

    /**
     * @return \Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage
     */
    protected function getFlashMessage()
    {
        return $this->get('braincrafted_bootstrap.flash');
    }
}
