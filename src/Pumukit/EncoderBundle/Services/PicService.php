<?php

namespace Pumukit\EncoderBundle\Services;

use Pumukit\SchemaBundle\Document\Pic;
use Symfony\Component\Filesystem;
use Symfony\Component\Finder\Finder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;

class PicService
{
    private $dm;
    private $fileSystem;
    private $finder;
    private $mmsPicService;
    private $max_width = 1920;
    private $max_height = 1080;

    /**
     * PicService constructor.
     *
     * @param DocumentManager            $documentManager
     * @param MultimediaObjectPicService $mmsPicService
     */
    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService)
    {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;

        $this->fileSystem = new Filesystem\Filesystem();
        $this->finder = new Finder();
    }

    /**
     * @param null $id
     * @param null $size
     * @param null $path
     * @param null $extension
     * @param null $tags
     * @param null $exists
     * @param null $type
     *
     * @return \Doctrine\MongoDB\Iterator|mixed|null
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function findPicsByOptions($id = null, $size = null, $path = null, $extension = null, $tags = null, $exists = null, $type = null)
    {
        if ('series' == $type) {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:Series');
        } else {
            $collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        }

        $pipeline = array(array('$match' => array('pics' => array('$exists' => true))));
        array_push($pipeline, array('$unwind' => '$pics'));

        $match = array(
            '$match' => array('pics.path' => array('$exists' => true)),
        );

        array_push($pipeline, $match);

        if ($id) {
            $match = array(
                '$match' => array('_id' => new \MongoId($id)),
            );

            array_push($pipeline, $match);
        }
        if ($path) {
            $match = array(
                '$match' => array('pics.path' => array('$regex' => $path, '$options' => 'i')),
            );

            array_push($pipeline, $match);
        }

        if ($tags) {
            $match = array(
                '$match' => array('pics.tags' => array('$in' => $tags)),
            );

            array_push($pipeline, $match);
        }

        if ($extension) {
            $orCondition = array();
            foreach ($extension as $ext) {
                if (false !== strpos($ext, '.')) {
                    $orCondition[] = array('pics.path' => array('$regex' => $ext, '$options' => 'i'));
                } else {
                    $orCondition[] = array('pics.path' => array('$regex' => '.'.$ext, '$options' => 'i'));
                }
            }

            $match = array('$match' => array('$or' => $orCondition));

            array_push($pipeline, $match);
        }

        $group = array('$group' => array(
            '_id' => null,
            'pics' => array('$addToSet' => '$pics'),
        ));

        array_push($pipeline, $group);

        $pics = $collection->aggregate($pipeline, array('cursor' => array()));
        $data = $pics->toArray();
        $pics = reset($data);

        if ($pics) {
            if (isset($exists)) {
                $pics = $this->checkExistsFiles($pics, $exists);
            }

            if (isset($size)) {
                $pics = $this->checkSizeFiles($pics, $size);
            }
        }

        return $pics;
    }

    /**
     * @param null $data
     * @param      $exists
     *
     * @return array $data
     */
    public function checkExistsFiles($data, $exists)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            if ('true' === $exists or '1' === $exists) {
                if ($this->fileSystem->exists($pic['path'])) {
                    $filterResult[] = $pic;
                }
            } else {
                if (!$this->fileSystem->exists($pic['path'])) {
                    $filterResult[] = $pic;
                }
            }
        }

        $data['pics'] = $filterResult;

        return $data;
    }

    /**
     * @param null $data
     * @param      $size
     *
     * @return array $data
     */
    public function checkSizeFiles($data, $size)
    {
        $filterResult = array();

        foreach ($data['pics'] as $pic) {
            $this->finder = new Finder();
            if (!$this->fileSystem->exists($pic['path'])) {
                $filterResult[] = 'File not found '.$pic['path'];
            } else {
                $files = $this->finder->files()->name(basename($pic['path']))->size('> '.$size.'K')->in(dirname($pic['path']));
                foreach ($files as $file) {
                    if ($file->getPathName() === $pic['path']) {
                        $filterResult[] = $pic;
                    }
                }
            }
        }

        $data['pics'] = $filterResult;

        return $data;
    }

    /**
     * @param $id
     * @param $size
     * @param $path
     * @param $extension
     * @param $tags
     * @param $exists
     * @param $type
     *
     * @return array
     *
     * @throws \Exception
     */
    public function formatInputs($id, $size, $path, $extension, $tags, $exists, $type)
    {
        if ($extension) {
            $extension = $this->getAllInputExtensions($extension);
            if (empty($extension)) {
                throw new \Exception('Please check extensions input');
            }
        }

        if ($path) {
            $pathExists = $this->checkPath($path);
            if (!$pathExists) {
                throw new \Exception("Path doesn't exists");
            }
        }

        if ($tags) {
            $tags = $this->getAllInputTags($tags);
            if (empty($tags)) {
                throw new \Exception('Please check tags input');
            }
        }

        return array($id, $size, $path, $extension, $tags, $exists, $type);
    }

    /**
     * @param       $data
     * @param array $params
     * @param bool  $no_replace
     *
     * @return array
     *
     * @throws \Exception
     */
    public function convertImage($data, array $params, $no_replace = false)
    {
        if (!isset($data['pics']) or empty($data['pics'])) {
            throw new \Exception('No pics found');
        }

        $output = array();

        foreach ($data['pics'] as $pic) {
            $ext = pathinfo($pic['path'], PATHINFO_EXTENSION);
            $picPath = $this->createFromPic($pic, $params, $no_replace, $ext);

            $multimediaObject = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
                array('pics.path' => $pic['path'])
            );

            if (!$multimediaObject) {
                $output[] = 'Multimedia Object not found by path '.$pic['path'];
                continue;
            }

            if ($no_replace) {
                try {
                    $newPic = $this->generateNewPic($multimediaObject, $picPath, $pic);
                    $this->hideOriginalImage($multimediaObject, $pic);

                    $multimediaObject->addPic($newPic);

                    $output[] = 'Create new image - Multimedia object '.$multimediaObject->getId().' and image path '.$picPath;
                } catch (\Exception $exception) {
                    $output[] = 'Create new image - Multimedia object '.$multimediaObject->getId().' error trying to add new pic';
                    continue;
                }
            } else {
                if ($picPath !== $pic['path']) {
                    try {
                        $this->updateOriginalImage($multimediaObject, $picPath, $pic);
                    } catch (\Exception $exception) {
                        $output[] = 'Override - Multimedia object '.$multimediaObject->getId().' error trying to update original image '.$picPath;
                        continue;
                    }
                    $output[] = 'Override - Updated path for multimedia object '.$picPath;
                } else {
                    $output[] = 'Override - Multimedia object have the same path for the image '.$picPath;
                }
            }

            $this->dm->flush();
        }

        return $output;
    }

    /**
     * @param $extension
     *
     * @return array
     */
    private function getAllInputExtensions($extension)
    {
        $extension = trim($extension);
        if (false !== strpos($extension, ',')) {
            $aExtensions = explode(',', $extension);
        } else {
            $aExtensions = array($extension);
        }

        array_map('trim', $aExtensions);
        array_filter($aExtensions, function ($value) {
            return '' !== $value;
        });

        return $aExtensions;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    private function checkPath($path)
    {
        return $this->fileSystem->exists($path);
    }

    /**
     * @param $tags
     *
     * @return array
     */
    private function getAllInputTags($tags)
    {
        $tags = trim($tags);
        if (false !== strpos($tags, ',')) {
            $aTags = explode(',', $tags);
        } else {
            $aTags = array($tags);
        }

        array_map('trim', $aTags);
        array_filter($aTags, function ($value) {
            return '' !== $value;
        });

        return $aTags;
    }

    /**
     * @param $pic
     * @param $params
     * @param $no_replace
     * @param $ext
     *
     * @return mixed|string
     */
    private function createFromPic($pic, $params, $no_replace, $ext)
    {
        list($originalWidth, $originalHeight) = getimagesize($pic['path']);

        $width = isset($params['max_width']) ? $params['max_width'] : 0;
        $height = isset($params['max_height']) ? $params['max_height'] : 0;

        list($width, $height) = $this->preserveAspectRatio($width, $height, $originalWidth, $originalHeight);

        $image_p = \imagecreatetruecolor($width, $height);
        if ('png' === $ext) {
            $image = \imagecreatefrompng($pic['path']);
        } else {
            $image = \imagecreatefromjpeg($pic['path']);
        }

        \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

        if ($no_replace) {
            $name = dirname($pic['path']).'/'.rand().'.jpg';
            \imagejpeg($image_p, $name, $params['quality']);
        } else {
            $name = $pic['path'];
            $name = str_replace($ext, 'jpg', $name);
            \imagejpeg($image_p, $name, $params['quality']);
        }

        return $name;
    }

    /**
     * @param $width
     * @param $height
     * @param $originalWidth
     * @param $originalHeight
     *
     * @return array
     */
    private function preserveAspectRatio($width, $height, $originalWidth, $originalHeight)
    {
        if (0 == $width && 0 == $height) {
            $width = $this->max_width;
        }

        $exceededRatio = 0;
        if ($height <= $width and $originalWidth > $width) {
            $exceededRatio = $originalWidth / $width;
        } elseif ($height > $width and $originalHeight > $height) {
            $exceededRatio = $originalHeight / $height;
        }

        if ($exceededRatio > 0) {
            $width = $originalWidth / $exceededRatio;
            $height = $originalHeight / $exceededRatio;
        } else {
            $width = $originalWidth;
            $height = $originalHeight;
        }

        return array($width, $height);
    }

    /**
     * @param $multimediaObject
     * @param $picPath
     * @param $pic
     *
     * @return Pic
     */
    private function generateNewPic($multimediaObject, $picPath, $pic)
    {
        $url = $this->mmsPicService->getTargetUrl($multimediaObject);

        $newPic = new Pic();
        $newPic->setPath($picPath);
        $newPic->setUrl($url.'/'.basename($picPath));

        $newPic->setSize(filesize($picPath));
        $newPic->setHide(false);
        $newPic->addTag('refactor_image');

        list($width, $height, $type, $attributes) = \getimagesize($picPath);

        $newPic->setWidth($width);
        $newPic->setHeight($height);
        $newPic->setMimeType(\image_type_to_mime_type($type));

        $newPic->setProperty('referer', $pic['path']);

        $this->dm->persist($newPic);

        return $newPic;
    }

    /**
     * @param $multimediaObject
     * @param $pic
     */
    private function hideOriginalImage($multimediaObject, $pic)
    {
        foreach ($multimediaObject->getPics() as $mmsPic) {
            if ($mmsPic->getPath() === $pic['path']) {
                $mmsPic->setHide(true);
                break;
            }
        }
    }

    /**
     * @param $multimediaObject
     * @param $picPath
     * @param $pic
     */
    private function updateOriginalImage($multimediaObject, $picPath, $pic)
    {
        $url = $this->mmsPicService->getTargetUrl($multimediaObject);
        $url .= '/'.basename($picPath);

        list($width, $height, $type, $attributes) = \getimagesize($picPath);

        foreach ($multimediaObject->getPics() as $mmsPic) {
            if ($mmsPic->getPath() === $pic['path']) {
                $mmsPic->setPath($picPath);
                $mmsPic->setUrl($url);
                $mmsPic->setSize(filesize($picPath));
                $mmsPic->setWidth($width);
                $mmsPic->setHeight($height);
                $mmsPic->setMimeType(\image_type_to_mime_type($type));
                $mmsPic->addTag('overrided');
                break;
            }
        }
    }
}
