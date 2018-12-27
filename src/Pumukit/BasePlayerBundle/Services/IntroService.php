<?php

namespace Pumukit\BasePlayerBundle\Services;

/**
 * Wrapper around the pumukit2.intro parameter.
 */
class IntroService
{
    private $intro = null;

    public function __construct($intro)
    {
        $this->intro = $intro;
    }

    /**
     * Returns the intro url if introParameter is null or 'true'.
     *
     * @param mixed $introParameter request parameter null|'false'|'true'
     *
     * @return string|null
     */
    public function getIntro($introParameter = null)
    {
        $hasIntro = (bool) $this->intro;

        $showIntro = true;
        if (null !== $introParameter && false === filter_var($introParameter, FILTER_VALIDATE_BOOLEAN)) {
            $showIntro = false;
        }

        if ($hasIntro && $showIntro) {
            return $this->intro;
        }

        return false;
    }

    /**
     * Returns the intro url if introParameter is null or 'true' and not exist an introProperty.
     * Returns the intro property if it is a string and introParameter is null or 'true'.
     *
     * @param null $introProperty
     * @param null $introParameter
     *
     * @return bool|null
     */
    public function getIntroForMultimediaObject($introProperty = null, $introParameter = null)
    {
        $showIntro = true;
        if (null !== $introParameter && false === filter_var($introParameter, FILTER_VALIDATE_BOOLEAN)) {
            $showIntro = false;
        }

        $hasIntro = (bool) $this->intro;
        if ($hasIntro && $showIntro && null === $introProperty) {
            return $this->intro;
        }

        $hasCustomIntro = (bool) $introProperty;
        if ($hasCustomIntro && $showIntro) {
            return $introProperty;
        }

        return false;
    }
}
