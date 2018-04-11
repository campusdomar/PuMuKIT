<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Pumukit\SchemaBundle\Document\Tag.
 *
 * @MongoDB\EmbeddedDocument()
 */
class EmbeddedTag
{
    /**
     * @var int
     *
     * @MongoDB\Id
     */
    private $id;

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $title = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\Raw
     */
    private $description = array('en' => '');

    /**
     * @var string
     *
     * @MongoDB\String
     */
    private $slug;

    /**
     * @var string
     *
     * @MongoDB\String
     * @MongoDB\Index
     */
    private $cod = '';

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $metatag = false;

    /**
     * @var bool
     *
     * @MongoDB\Boolean
     */
    private $display = false;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property.
     *
     * @var locale
     */
    private $locale = 'en';

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $created;

    /**
     * @var date
     *
     * @MongoDB\Date
     */
    private $updated;

    /**
     * @MongoDB\Field(type="string")
     */
    private $path;

    /**
     * @MongoDB\Field(type="int")
     */
    private $level;

    /**
     * Construct.
     */
    public function __construct(Tag $tag)
    {
        if (null !== $tag) {
            $this->id = $tag->getId();
            $this->setI18nTitle($tag->getI18nTitle());
            $this->setI18nDescription($tag->getI18nDescription());
            $this->slug = $tag->getSlug();
            $this->cod = $tag->getCod();
            $this->metatag = $tag->getMetatag();
            $this->display = $tag->getDisplay();
            $this->locale = $tag->getLocale();
            $this->created = $tag->getCreated();
            $this->updated = $tag->getUpdated();
            $this->path = $tag->getPath();
            $this->level = $tag->getLevel();
        }
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string      $title
     * @param string|null $locale
     */
    public function setTitle($title, $locale = null)
    {
        if (null == $locale) {
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
        if (null == $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->title[$locale])) {
            return '';
        }

        return $this->title[$locale];
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
     * Set i18n title.
     *
     * @param array $title
     */
    public function setI18nTitle(array $title)
    {
        $this->title = $title;
    }

    /**
     * Set description.
     *
     * @param string      $description
     * @param string|null $locale
     */
    public function setDescription($description, $locale = null)
    {
        if (null == $locale) {
            $locale = $this->locale;
        }
        $this->description[$locale] = $description;
    }

    /**
     * Get description.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if (null == $locale) {
            $locale = $this->locale;
        }
        if (!isset($this->description[$locale])) {
            return '';
        }

        return $this->description[$locale];
    }

    /**
     * Set i18n description.
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
     * Set slug.
     *
     * @param string $slug
     *
     * @return Tag
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set cod.
     *
     * @param string $cod
     */
    public function setCod($cod)
    {
        $this->cod = $cod;
    }

    /**
     * Get cod.
     *
     * @return string
     */
    public function getCod()
    {
        return $this->cod;
    }

    /**
     * Set metatag.
     *
     * @param bool $metatag
     */
    public function setMetatag($metatag)
    {
        $this->metatag = $metatag;
    }

    /**
     * Get metatag.
     *
     * @return bool
     */
    public function getMetatag()
    {
        return $this->metatag;
    }

    /**
     * Set display.
     *
     * @param bool $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * Get display.
     *
     * @return bool
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set created.
     *
     * @param \Date $created
     *
     * @return Tag
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return Date
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param \Date $updated
     *
     * @return Tag
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return Date
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set translatable locale.
     *
     * @param locale $locale
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
     * to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * Get level.
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Get path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns true if given node is children of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isChildOf($tag)
    {
        if ($this->isDescendantOf($tag)) {
            $suffixPath = substr($this->getPath(), strlen($tag->getPath()), strlen($this->getPath()));
            if (1 === substr_count($suffixPath, '|')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if given node is descendant of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isDescendantOf($tag)
    {
        if ($tag->getCod() == $this->getCod()) {
            return false;
        }

        return substr($this->getPath(), 0, strlen($tag->getPath())) === $tag->getPath();
    }

    /**
     * Returns true if given node is descendant of tag or the same.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function equalsOrDescendantOf($tag)
    {
        return substr($this->getPath(), 0, strlen($tag->getPath())) === $tag->getPath();
    }

    /**
     * Returns true if given node cod is descendant of tag.
     *
     * @param EmbeddedTag|Tag $tag
     *
     * @return bool
     */
    public function isDescendantOfByCod($tagCod)
    {
        if ($tagCod == $this->getCod()) {
            return false;
        }
        if (0 === strpos($this->getPath(), sprintf('%s|', $tagCod))) {
            return true;
        }

        return false === strpos($this->getPath(), sprintf('|%s|', $tagCod)) ? false : true;
    }

    /**
     * @param ArrayCollection $embeddedTags
     * @param EmbeddedTag|Tag $tag
     *
     * @return EmbeddedTag
     */
    public static function getEmbeddedTag($embedTags, $tag)
    {
        if ($tag instanceof self) {
            return $tag;
        } elseif ($tag instanceof Tag) {
            return new self($tag);
        }

        throw new \InvalidArgumentException('Only Tag or EmbeddedTag are allowed.');
    }

    /**
     * Returns true if the tag is a PUB tag (that appears in the Pub tab in the back-office).
     */
    public function isPubTag()
    {
        return $this->isDescendantOfByCod('PUBCHANNELS') || $this->isDescendantOfByCod('PUBDECISIONS');
    }
}
