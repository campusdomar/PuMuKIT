<?php

namespace Pumukit\OpencastBundle\Event;

final class OpencastEvents
{
    /**
     * The import.success event is thrown each time an import is finished successfully
     * in the system.
     *
     * The event listener receives an
     * Pumukit\OpencastBundle\Event\OpencastEvent instance.
     *
     * @var string
     */
    const IMPORT_SUCCESS = 'import.success';

    /**
     * The import.success event is thrown each time an import fails
     * in the system.
     *
     * The event listener receives an
     * Pumukit\OpencastBundle\Event\OpencastEvent instance.
     *
     * @var string
     */
    const IMPORT_ERROR = 'import.error';
}
