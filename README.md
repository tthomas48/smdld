smdld
=====

SmugMug image downloader (smdld) is a simple PHP command line utility to download and backup smugmug galleries. 

It currently operates anonymously so it can only download public images.

Getting Started
---------------

  * Copy lib/key.php.sample to lib/key.php
  * [Apply for an API key](http://www.smugmug.com/hack/apikeys) from SmugMug
  * Put your API key, nickname and application name in lib/key.php
  * Download [composer](http://getcomposer.org/) and run composer install from the root directory
  
That's it! Run smdld and you'll be prompted with the gallery to download. Downloading a gallery
with sub-galleries will download all sub-galleries. 

smdld uses [phpSmug](http://phpsmug.com/docs) to do the API heavy lifting.
