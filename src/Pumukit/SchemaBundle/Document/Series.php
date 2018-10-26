<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\SeriesRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(name="text_index", keys={"textindex.text"="text", "secondarytextindex.text"="text"}, options={"language_override"="indexlanguage", "default_language"="none", "weights"={"textindex.text"=10, "secondarytextindex.text"=1}})
 * })
 */
class Series
{
    use Traits\Keywords;
    use Traits\Properties;
    use Traits\Pic {
        Traits\Pic::__construct as private __PicConstruct;
    }

    const TYPE_SERIES = 0;
    const TYPE_PLAYLIST = 1;

    const SORT_MANUAL = 0;
    const SORT_PUB_ASC = 1;
    const SORT_PUB_DES = 2;
    const SORT_REC_DES = 3;
    const SORT_REC_ASC = 4;
    const SORT_ALPHAB = 5;

    public static $sortCriteria = array(
        self::SORT_MANUAL => array('rank' => 'asc'),
        self::SORT_PUB_ASC => array('public_date' => 'asc'),
        self::SORT_PUB_DES => array('public_date' => 'des'),
        self::SORT_REC_DES => array('record_date' => 'des'),
        self::SORT_REC_ASC => array('record_date' => 'asc'),
        self::SORT_ALPHAB => array('title.es' => 'asc'),
        //TODO culture
    );

    public static $sortText = array(
        self::SORT_MANUAL => 'manual',
        self::SORT_PUB_ASC => 'publication date ascending',
        self::SORT_PUB_DES => 'publication date descending',
        self::SORT_REC_DES => 'recording date descending',
        self::SORT_REC_ASC => 'recording date ascending',
        self::SORT_ALPHAB => 'title',
    );

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     * @MongoDB\Index
     */
    private $secret;

    /**
     * Flag with TYPE_SERIES or TYPE_PLAYLIST to determine the collection type.
     *
     * @var int
     * @MongoDB\Field(type="int")
     * @MongoDB\Index
     */
    private $type;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     */
    private $sorting = self::SORT_MANUAL;

    /**
     * @MongoDB\ReferenceOne(targetDocument="SeriesType", inversedBy="series", simple=true, cascade={"persist"})
     */
    private $series_type;

    /**
     * @MongoDB\ReferenceOne(targetDocument="SeriesStyle", inversedBy="series", simple=true, cascade={"persist"})
     */
    private $series_style;

    /**
     * @var ArrayCollection
     * @MongoDB\EmbedOne(targetDocument="Playlist")
     * @Serializer\Exclude
     */
    private $playlist;

    /**
     * @var bool
     * @MongoDB\Field(type="boolean")
     */
    private $announce = false;

    /**
     * When series is hide and we access to the serie with the mmobj->getSeries(),
     * it creates a pseudo serie with default values (the webtv filter dont permit to access hide series),
     * and we want to force that the serie will be hide.
     *
     * @var bool
     * @MongoDB\Field(type="boolean")
     * @MongoDB\Index
     */
    private $hide = true;

    /**
     * @var datetime
     * @MongoDB\Field(type="date")
     * @MongoDB\Index
     */
    private $public_date;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $title = array('en' => '');

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $subtitle = array('en' => '');

    /**
     * @var text
     * @MongoDB\Field(type="raw")
     */
    private $description = array('en' => '');

    /**
     * @var text
     * @MongoDB\Field(type="raw")
     */
    private $header = array('en' => '');

    /**
     * @var text
     * @MongoDB\Field(type="raw")
     */
    private $footer = array('en' => '');

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $copyright;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    private $license;

    /**
     * @var string
     * @MongoDB\Field(type="raw")
     */
    private $line2 = array('en' => '');

    /**
     * @var array
     * @MongoDB\Raw
     */
    private $textindex = array();

    /**
     * @var array
     * @MongoDB\Raw
     */
    private $secondarytextindex = array();

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    public function __construct()
    {
        $this->secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->hide = false;
        $this->playlist = new Playlist();
        $this->__PicConstruct();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        return true;
    }

    /**
     * Get id.
     *
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Resets secret.
     *
     * @return string
     */
    public function resetSecret()
    {
        $this->secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        return $this->secret;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @return int
     */
    public function setType($type)
    {
        return $this->type = $type;
    }

    /**
     * Get sorting type.
     *
     * @return int
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Get sorting criteria.
     *
     * @return array
     */
    public function getSortingCriteria()
    {
        return isset(self::$sortCriteria[$this->sorting]) ?
            self::$sortCriteria[$this->sorting] :
            self::$sortCriteria[0];
    }

    /**
     * Set sorting type.
     *
     * @return int
     */
    public function setSorting($sorting)
    {
        return $this->sorting = $sorting;
    }

    /**
     * Set series_type.
     *
     * @param SeriesType $series_type
     */
    public function setSeriesType(SeriesType $series_type)
    {
        $this->series_type = $series_type;
    }

    /**
     * Get series_type.
     *
     * @return SeriesType
     */
    public function getSeriesType()
    {
        return $this->series_type;
    }

    /**
     * Set series_style.
     *
     * @param SeriesStyle $series_style
     */
    public function setSeriesStyle(SeriesStyle $series_style = null)
    {
        $this->series_style = $series_style;
    }

    /**
     * Get series_style.
     *
     * @return SeriesStyle
     */
    public function getSeriesStyle()
    {
        return $this->series_style;
    }

    /**
     * Contains multimedia_object.
     *
     * @param MultimediaObject $multimedia_object
     *
     * @return bool
     */
    public function containsMultimediaObject(MultimediaObject $multimedia_object)
    {
        //TODO:
        throw new \Exception('PMK2.5 PHP7 use service');

        return $this->multimedia_objects->contains($multimedia_object);
    }

    /**
     * Add multimedia object.
     *
     * @param MultimediaObject $multimedia_object
     */
    public function addMultimediaObject(MultimediaObject $multimedia_object)
    {
        throw new \Exception('PMK2.5 PHP7 use service');

        return $this->multimedia_objects->add($multimedia_object);
    }

    /**
     * Remove multimedia object.
     *
     * @param MultimediaObject $multimedia_object
     */
    public function removeMultimediaObject(MultimediaObject $multimedia_object)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        $this->multimedia_objects->removeElement($multimedia_object);
    }

    /**
     * Get multimedia_objects.
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjects()
    {
        throw new \Exception('PMK2.5 PHP7 use service');

        return $this->multimedia_objects;
    }

    /**
     * Set playlist.
     *
     * @return Playlist
     */
    public function setPlaylist(Playlist $playlist)
    {
        $this->playlist = $playlist;
    }

    /**
     * Get playlist.
     *
     * @return Playlist
     */
    public function getPlaylist()
    {
        return $this->playlist;
    }

    /**
     * Set announce.
     *
     * @param bool $announce
     */
    public function setAnnounce($announce)
    {
        $this->announce = $announce;
    }

    /**
     * Get announce.
     *
     * @return bool
     */
    public function getAnnounce()
    {
        return $this->announce;
    }

    /**
     * Get announce.
     *
     * @return bool
     */
    public function isAnnounce()
    {
        return $this->announce;
    }

    /**
     * Set hide.
     *
     * @param bool $hide
     */
    public function setHide($hide)
    {
        $this->hide = $hide;
    }

    /**
     * Get hide.
     *
     * @return bool
     */
    public function getHide()
    {
        return $this->hide;
    }

    /**
     * Get hide.
     *
     * @return bool
     */
    public function isHide()
    {
        return $this->hide;
    }

    /**
     * Set public_date.
     *
     * @param datetime $public_date
     */
    public function setPublicDate($public_date)
    {
        $this->public_date = $public_date;
    }

    /**
     * Get public_date.
     *
     * @return datetime
     */
    public function getPublicDate()
    {
        return $this->public_date;
    }

    /**
     * Set title.
     *
     * @param string      $title
     * @param string|null $locale
     */
    public function setTitle($title, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->title[$locale] = $title;
    }

    /**
     * Get title.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getTitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->title[$locale])) {
            return '';
        }

        return $this->title[$locale];
    }

    /**
     * Set I18n title.
     *
     * @param array $title
     */
    public function setI18nTitle(array $title)
    {
        $this->title = $title;
    }

    /**
     * Get i18n title.
     *
     * @return array
     */
    public function getI18nTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle.
     *
     * @param string      $subtitle
     * @param string|null $locale
     */
    public function setSubtitle($subtitle, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->subtitle[$locale] = $subtitle;
    }

    /**
     * Get subtitle.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getSubtitle($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->subtitle[$locale])) {
            return '';
        }

        return $this->subtitle[$locale];
    }

    /**
     * Set I18n subtitle.
     *
     * @param array $subtitle
     */
    public function setI18nSubtitle(array $subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Get i18n subtitle.
     *
     * @return array
     */
    public function getI18nSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set description.
     *
     * @param text        $description
     * @param string|null $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param string|null $locale
     *
     * @return text
     */
    public function getDescription($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * Set I18n description.
     *
     * @param array $description
     */
    public function setI18nDescription(array $description)
    {
        $this->description = $description;
    }

    /**
     * Get i18n description.
     *
     * @return array
     */
    public function getI18nDescription()
    {
        return $this->description;
    }

    /**
     * Set header.
     *
     * @param text        $header
     * @param string|null $locale
     */
    public function setHeader($header, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->header[$locale] = $header;
    }

    /**
     * Get header.
     *
     * @param string|null $locale
     *
     * @return text
     */
    public function getHeader($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->header[$locale])) {
            return '';
        }

        return $this->header[$locale];
    }

    /**
     * Set I18n header.
     *
     * @param array $header
     */
    public function setI18nHeader(array $header)
    {
        $this->header = $header;
    }

    /**
     * Get i18n header.
     *
     * @return array
     */
    public function getI18nHeader()
    {
        return $this->header;
    }

    /**
     * Set footer.
     *
     * @param text        $footer
     * @param string|null $locale
     */
    public function setFooter($footer, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->footer[$locale] = $footer;
    }

    /**
     * Get footer.
     *
     * @param string|null $locale
     *
     * @return text
     */
    public function getFooter($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->footer[$locale])) {
            return '';
        }

        return $this->footer[$locale];
    }

    /**
     * Set I18n footer.
     *
     * @param array $footer
     */
    public function setI18nFooter(array $footer)
    {
        $this->footer = $footer;
    }

    /**
     * Get i18n footer.
     *
     * @return array
     */
    public function getI18nFooter()
    {
        return $this->footer;
    }

    /**
     * Set copyright.
     *
     * @param string $copyright
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
    }

    /**
     * Get copyright.
     *
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set license.
     *
     * @param string $license
     */
    public function setLicense($license)
    {
        $this->license = $license;
    }

    /**
     * Get license.
     *
     * @return string $license
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Set line2.
     *
     * @param string      $line2
     * @param string|null $locale
     */
    public function setLine2($line2, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        $this->line2[$locale] = $line2;
    }

    /**
     * Get line2.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getLine2($locale = null)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->line2[$locale])) {
            return '';
        }

        return $this->line2[$locale];
    }

    /**
     * Set I18n line2.
     *
     * @param array $line2
     */
    public function setI18nLine2(array $line2)
    {
        $this->line2 = $line2;
    }

    /**
     * Get i18n line2.
     *
     * @return array
     */
    public function getI18nLine2()
    {
        return $this->line2;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Contains multimediaobject with tags.
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function containsMultimediaObjectWithTag(Tag $tag)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get multimediaobjects with a tag.
     *
     * @param Tag $tag
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjectsWithTag(Tag $tag)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        $r = array();
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsTag($tag)) {
                $r[] = $mmo;
            }
        }

        return $r;
    }

    /**
     * Get one multimedia object with tag.
     *
     * @param Tag $tag
     *
     * @return MultimediaObject|null
     */
    public function getMultimediaObjectWithTag(Tag $tag)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsTag($tag)) {
                return $mmo;
            }
        }

        return;
    }

    /**
     * Get multimediaobjects with all tags.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjectsWithAllTags(array $tags)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        $r = array();
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsAllTags($tags)) {
                $r[] = $mmo;
            }
        }

        return $r;
    }

    /**
     * Get multimediaobject with all tags.
     *
     * @param array $tags
     *
     * @return multimedia_object|null
     */
    public function getMultimediaObjectWithAllTags(array $tags)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsAllTags($tags)) {
                return $mmo;
            }
        }

        return;
    }

    /**
     * Get multimediaobjects with any tag.
     *
     * @param array $tags
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjectsWithAnyTag(array $tags)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        $r = array();
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsAnyTag($tags)) {
                $r[] = $mmo;
            }
        }

        return $r;
    }

    /**
     * Get multimediaobject with any tag.
     *
     * @param array $tags
     *
     * @return MultimediaObject|null
     */
    public function getMultimediaObjectWithAnyTag(array $tags)
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        foreach ($this->multimedia_objects as $mmo) {
            if ($mmo->containsAnyTag($tags)) {
                return $mmo;
            }
        }

        return;
    }

    /**
     * Get filtered multimedia objects with tags.
     *
     * @param array $any_tags
     * @param array $all_tags
     * @param array $not_any_tags
     * @param array $not_all_tags
     *
     * @return ArrayCollection
     */
    public function getFilteredMultimediaObjectsWithTags(array $any_tags = array(), array $all_tags = array(), array $not_any_tags = array(), array $not_all_tags = array())
    {
        throw new \Exception('PMK2.5 PHP7 use service');
        $r = array();
        foreach ($this->multimedia_objects as $mmo) {
            if ($any_tags && !$mmo->containsAnyTag($any_tags)) {
                continue;
            }
            if ($all_tags && !$mmo->containsAllTags($all_tags)) {
                continue;
            }
            if ($not_any_tags && $mmo->containsAnyTag($not_any_tags)) {
                continue;
            }
            if ($not_all_tags && $mmo->containsAllTags($not_all_tags)) {
                continue;
            }

            $r[] = $mmo;
        }

        return $r;
    }

    /**
     * Set textindex.
     *
     * @param array $textindex
     */
    public function setTextIndex($textindex)
    {
        $this->textindex = $textindex;
    }

    /**
     * Get textindex.
     *
     *
     * @return array
     */
    public function getTextIndex()
    {
        return $this->textindex;
    }

    /**
     * Set secondarytextindex.
     *
     * @param array $secondarytextindex
     */
    public function setSecondaryTextIndex($secondarytextindex)
    {
        $this->secondarytextindex = $secondarytextindex;
    }

    /**
     * Get secondarytextindex.
     *
     *
     * @return array
     */
    public function getSecondaryTextIndex()
    {
        return $this->secondarytextindex;
    }
}
