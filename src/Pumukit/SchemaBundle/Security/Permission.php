<?php

namespace Pumukit\SchemaBundle\Security;

use Pumukit\SchemaBundle\Document\PermissionProfile;

class Permission
{
    const ACCESS_DASHBOARD = 'ROLE_ACCESS_DASHBOARD';
    const ACCESS_MULTIMEDIA_SERIES = 'ROLE_ACCESS_MULTIMEDIA_SERIES';
    const ACCESS_LIVE_CHANNELS = 'ROLE_ACCESS_LIVE_CHANNELS';
    const ACCESS_LIVE_EVENTS = 'ROLE_ACCESS_LIVE_EVENTS';
    const ACCESS_JOBS = 'ROLE_ACCESS_JOBS';
    const ACCESS_PEOPLE = 'ROLE_ACCESS_PEOPLE';
    const ACCESS_TAGS = 'ROLE_ACCESS_TAGS';
    const ACCESS_BROADCASTS = 'ROLE_ACCESS_BROADCASTS';
    const ACCESS_SERIES_TYPES = 'ROLE_ACCESS_SERIES_TYPES';
    const ACCESS_ADMIN_USERS = 'ROLE_ACCESS_ADMIN_USERS';
    const ACCESS_PERMISSION_PROFILES = 'ROLE_ACCESS_PERMISSION_PROFILES';
    const ACCESS_ROLES = 'ROLE_ACCESS_ROLES';
    const ACCESS_IMPORTER = 'ROLE_ACCESS_IMPORTER';
    const ACCESS_GROUPS = 'ROLE_ACCESS_GROUPS';
    const CHANGE_MMOBJECT_STATUS = 'ROLE_CHANGE_MMOBJECT_STATUS';
    const CHANGE_MMOBJECT_PUBCHANNEL = 'ROLE_CHANGE_MMOBJECT_PUBCHANNEL';
    const ACCESS_PUBLICATION_TAB = 'ROLE_ACCESS_PUBLICATION_TAB';
    const ACCESS_ADVANCED_UPLOAD = 'ROLE_ACCESS_ADVANCED_UPLOAD';
    const ACCESS_WIZARD_UPLOAD = 'ROLE_ACCESS_WIZARD_UPLOAD';
    const ACCESS_API = 'ROLE_ACCESS_API';
    const ACCESS_INBOX = 'ROLE_ACCESS_INBOX';
    const MODIFY_OWNER = 'ROLE_MODIFY_OWNER';

    public static $permissionDescription = array(
        Permission::ACCESS_DASHBOARD => array(
            'description' => "Access Dashboard",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_MULTIMEDIA_SERIES => array(
            'description' => "Access Multimedia Series",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_LIVE_CHANNELS => array(
            'description' => "Access Live Channels",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_LIVE_EVENTS => array(
            'description' => "Access Live Events",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_JOBS => array(
            'description' => "Access Jobs",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_PEOPLE => array(
            'description' => "Access People",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_TAGS => array(
            'description' => "Access Tags",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_BROADCASTS => array(
            'description' => "Access Broadcasts",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_SERIES_TYPES => array(
            'description' => "Access Series Types",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_ADMIN_USERS => array(
            'description' => "Access Admin Users",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_GROUPS => array(
            'description' => "Access Groups",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_PERMISSION_PROFILES => array(
            'description' => "Access Permission Profiles",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_ROLES => array(
            'description' => "Access Roles",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_IMPORTER => array(
            'description' => "Access Importer",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::CHANGE_MMOBJECT_STATUS => array(
            'description' => "Change Multimedia Object Status",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::CHANGE_MMOBJECT_PUBCHANNEL => array(
            'description' => "Change Multimedia Object Publication Channel",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_PUBLICATION_TAB => array(
            'description' => "Access Publication Tab",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_ADVANCED_UPLOAD => array(
            'description' => "Access Advanced Upload",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_WIZARD_UPLOAD => array(
            'description' => "Access Wizard Upload",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_API => array(
            'description' => "Access API",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::ACCESS_INBOX => array(
            'description' => "Access Inbox",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
        Permission::MODIFY_OWNER => array(
            'description' => "Modify Owner",
            'dependencies' => array(
                PermissionProfile::SCOPE_GLOBAL => array(),
                PermissionProfile::SCOPE_PERSONAL => array()
            )
        ),
    );
}
