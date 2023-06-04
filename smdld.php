#!/usr/bin/env php
<?php
require_once ("lib/key.php");
require_once ("vendor/autoload.php");

function download($client, $id) {
    $response = $client->get($id . "!albumlist");

    foreach($response->AlbumList as $album) {
      if (! file_exists("Download/" . $album->UrlPath)) {
          mkdir("Download/" . $album->UrlPath, 0777, true);
      }

      $albumUri = $album->Uri . "!images";
      do {
        $albumResponse = $client->get($albumUri, [
//         '_filter' => ['Uri','FileName','OriginalSize', 'ImageDownload'],
//         '_filteruri' => ['Image'],
        ]);
        $count = 0;
        foreach ($albumResponse->AlbumImage as $image) {
          $path = "Download" . $album->UrlPath . "/" . $image->FileName;
          if (file_exists($path) && filesize($path) == $image->OriginalSize) {
              cli\line("Skipping " . $path);
              continue;
          }
          if (isset($image->Uris->LargestVideo)) {
            $url = $image->Uris->LargestVideo->Uri;
            $contents = $client->get($url);
            if (file_exists($path) && filesize($path) == $contents->LargestVideo->Size) {
                cli\line("Skipping " . $path);
                continue;
            }
            cli\line("Downloading " . $path);
            file_put_contents($path, file_get_contents($contents->LargestVideo->Url));
          } else {
            $url = $image->Uris->LargestImage->Uri;
            $contents = $client->get($url);

            cli\line("Downloading " . $path);
            file_put_contents($path, file_get_contents($contents->LargestImage->Url));
          }

          $count++;
        }

        $albumUri = isset($albumResponse->Pages->NextPage) ? $albumResponse->Pages->NextPage : null;
      } while($albumUri);
      
      // rint_r($albumResponse); exit();
      return $count;
    }
}


try {
 // $API_KEY and $APP_NAME are globals from key.php
    $jar = new \GuzzleHttp\Cookie\CookieJar;
    $options = [
      "AppName" => $APP_NAME,
      "cookies" => $jar,
    ];
    $client = new phpSmug\Client($API_KEY, $options);
    if (!empty($UNLOCK_PASSWORD)) {
        $info = $client->get("folder/user/$NICKNAME");
        $unlockUri = $info->Folder->Uris->UnlockFolder->Uri;
        $client->post($unlockUri, ["Password" => $UNLOCK_PASSWORD ]);
    }

    $albums = [];
    $url = "folder/user/$NICKNAME!folderlist";
    do { 
      cli\line("Requesting $url");
      $response = $client->get($url, [
         '_filter' => ['UrlPath', 'Uri'],
         '_filteruri' => ['Album'],
      ]);
      $albums = array_merge($albums, $response->FolderList);
      print_r($response);
      $url = "";
      if (property_exists($response, 'Pages') && property_exists($response->PAGES, 'NextPage')) {
        $url = $response->Pages->NextPage;
      }

    } while(!empty($url));

    $categories = array();
    foreach ($albums as $album) {
        $categories[$album->Uri] = $album->UrlPath;
    }
    natsort($categories);
    $count = 0;
    foreach ($categories as $category_id => $category_name) {
        cli\line("Downloading $category_name");
        $count += download($client, $category_id);
        // cli\line($category_id . ") " . $category_name);
    }
    //  $id = \cli\prompt("Please enter a gallery id");

/*    
    $key = NULL;
    $dnld = array();
    foreach ($albums as $album) {
        if ($album["id"] == $id) {
            $dnld[$album["id"]] = $album["Key"];
        } elseif (array_key_exists('Category', $album) && $album["Category"]["id"] == $id) {
            $dnld[$album["id"]] = $album["Key"];
        } elseif (array_key_exists('SubCategory', $album) && $album["SubCategory"]["id"] == $id) {
            $dnld[$album["id"]] = $album["Key"];
        }
    }
*/
   
/* 
    foreach ($dnld as $id => $key) {
        $images = $client->images_get("AlbumID={$id}", "AlbumKey={$key}", "Heavy=1");
        if (! file_exists("Download/" . $categories[$id])) {
            mkdir("Download/" . $categories[$id], 0777, true);
        }
        $count = 0;
        foreach ($images["Images"] as $image) {
            $path = "Download/" . $categories[$id] . "/" . $image["FileName"];
            if (file_exists($path) && filesize($path) == $image["Size"]) {
                cli\line("Skipping " . $image["FileName"]);
                continue;
            }
            cli\line("Downloading " . $image["FileName"]);
            
            if (array_key_exists("Video1280URL", $image)) {
                file_put_contents($path, file_get_contents($image["Video1280URL"]));
            } else {
                file_put_contents($path, file_get_contents($image["OriginalURL"]));
            }
            $count ++;
        }
    }
*/
    cli\line("Downloaded $count images");
} catch (Exception $e) {
    echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
