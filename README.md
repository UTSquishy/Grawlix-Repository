# Grawlix — the CMS for comics

See http://www.getgrawlix.com/docs/

## Installation instructions

#### New install

* Upload the contents of the `grawlix-1.0.3` folder to your web host.
* Rename `htaccess.txt` to `.htaccess`.
* Visit `yoursite.com/firstrun.php` and follow the prompts.

Upon successful install, you should delete `firstrun.php` as a security precaution. You can also delete this readme.

#### Upgrading from 1.0.2

Upload these files/folders from `grawlix-1.0.3` to your web host, replacing those items in your current Grawlix installation.

* `_admin`
* `_system`
* `functions.inc.php`
* `index.php`
* `htaccess.txt` then rename it to `.htaccess`

From `grawlix-1.0.3/assets/` upload these folders to the Grawlix `assets` on your web host.

* `book`
* `patterns`
* `scripts`
* `snippets`
* `system`

The css files of the Indotherm theme have been updated. If you’ve heavily customized yours you may not need to bother with these.

## Version history

##### 1.0.3 — 25 June 2015
* Added a second tone to Indotherm theme.
* Fixed image display bug when creating new comic pages.
* Added confirmation dialog when deleting static pages.
* Fixed problem some users had with PHP headers and quotes.
* Comic permalinks now contain full URL.
* Reworked how themes and tones are installed within the Panel.

##### 1.0.2 — 03 May 2015
* Updated styling of Panel sidebar menu.
* Added preview images for themes.
* Fixed the display of selected tone for static pages in theme manager.

##### 1.0.1 — 07 April 2015
* Restructure some data for easier use with microdata.
* Added version number to database.
* Fixed a bug in the FTP importer that added extra slashes to path names.

##### 1.0 — 01 April 2015
* Initial release.
