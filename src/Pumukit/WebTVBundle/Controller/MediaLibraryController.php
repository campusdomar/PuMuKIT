<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;

/**
 * Class MediaLibraryController.
 */
class MediaLibraryController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/mediateca/{sort}", defaults={"sort" = "date"}, requirements={"sort" = "alphabetically|date|tags"}, name="pumukit_webtv_medialibrary_index")
     * @Template("PumukitWebTVBundle:MediaLibrary:template.html.twig")
     *
     * @param         $sort
     * @param Request $request
     *
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \MongoException
     */
    public function indexAction(Request $request, $sort)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        list($objectByCol, $templateTitle, $array_tags, $hasCatalogueThumbnails) = $this->getMediaLibraryParameters();

        $this->get('pumukit_web_tv.breadcrumbs')->addList($templateTitle, 'pumukit_webtv_medialibrary_index', ['sort' => $sort]);

        $options = ['page' => $request->get('page', 1)];
        if ('tags' === $sort) {
            $options['tag'] = $request->query->get('p_tag', false);
        }

        $criteria = $this->getMediaLibraryCriteria($request);

        $locale = $request->getLocale();
        list($result, $aggregatedNumMmobjs) = $this->get('pumukit_web_tv.list_service')->getMediaLibrary($criteria, $sort, $locale, $options);

        $selectionTags = $dm->getRepository(Tag::class)->findBy(
            ['cod' => [
                '$in' => $array_tags,
            ],
        ]);

        return [
            'objects' => $result,
            'sort' => $sort,
            'tags' => $selectionTags,
            'objectByCol' => $objectByCol,
            'show_info' => false,
            'show_more' => false,
            'catalogue_thumbnails' => $hasCatalogueThumbnails,
            'aggregated_num_mmobjs' => $aggregatedNumMmobjs,
        ];
    }

    /**
     * @return array
     */
    private function getMediaLibraryParameters()
    {
        $objectByCol = $this->container->getParameter('columns_objs_catalogue');

        $templateTitle = $this->container->getParameter('menu.mediateca_title');
        $templateTitle = $this->get('translator')->trans($templateTitle);

        $array_tags = $this->container->getParameter('pumukit_web_tv.media_library.filter_tags');
        $hasCatalogueThumbnails = $this->container->getParameter('catalogue_thumbnails');

        return [
            $objectByCol,
            $templateTitle,
            $array_tags,
            $hasCatalogueThumbnails,
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws \MongoException
     */
    private function getMediaLibraryCriteria(Request $request)
    {
        $locale = $request->getLocale();
        $criteria = [];
        if ($request->query->get('search', false)) {
            $criteria = [
                'title.'.$locale => new \MongoRegex(sprintf('/%s/i', $request->query->get('search'))),
            ];
        }

        return $criteria;
    }
}
