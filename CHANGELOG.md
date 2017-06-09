#CHANGELOG

Web version of the changelog in http://pumukit.org/pmk-2-x-release-archive/
To get the diff for a specific change, go to https://github.com/campusdomar/PuMuKIT2/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/campusdomar/PuMuKIT2/compare/2.0.0...2.1.0-rc1

## [2.3.0][2.3.0] (2017-03-30)
- Update Symfony 2.6 to Symfony 2.8.
- Use LDAP Symfony component.
- Adding microsites (WebTVBundle).
- Multiple email send and multiple languages emails (NotificationBundle).
- Added groups for users (SchemaBundle).
- Create custom pages dynamically (TemplateBundle).
- Adding embeddedBroadcast to MultimediaObject document (SchemaBundle).
- Adding key Keywords to MultimediaObject and Series document, the key Keyword is deprecated, but methods keep running and isn't required refactor code (SchemaBundle).
- Adding Pumukit bundle versions list (CoreBundle).
- Adding multimedia object list to backoffice (NewAdminBundle).
- Adding new command to import file on multimediaobject track.
- Improved documentation and fixed minor bugs .


## [2.2.0][2.2.0] (2016-04-28)
- Added a responsive WebTV portal bundle. The old not responsive web portal is maintained as legacy to not break the compatibility.
- Added PumukitStatsUI bundle as default. (Adds statistics of series and multimedia objects to the back-office)
- Added 'personal scope' support for auto-publishing to the back-office.
- Added 'Permission Profiles' to the back-office.
- Improved CAS support.
- Added new LDAP broadcast.
- Added support to switch default portal player.
- Improved documentation and fixed minor bugs .

## [2.1.1][2.1.1] (2016-04-28)
- Improve performance.
- Bug fixes.

## [2.1.0][2.1.0] (2015-11-16)
- Added migration path from PuMuKIT1.7 to PuMuKIT2
- Removed MoodleBundle out of the project to be used as a third party bundle.
- Production version
- Bootstrap based Material design AdminUI

## 2.0.0 (2015-02-12)
- Initial concept of technologies


[Unreleased]:https://github.com/campusdomar/PuMuKIT2/compare/2.1.0...HEAD
[2.1.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.0.0...2.1.0
[2.1.1]:https://github.com/campusdomar/PuMuKIT2/compare/2.1.0...2.1.1
[2.2.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.1.1...2.2.0
[2.3.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.2.0...2.3.0
