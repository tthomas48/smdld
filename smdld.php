#!/usr/bin/env php
<?php
require_once ("lib/key.php");
require_once ("lib/phpSmug.php");
require_once ("vendor/autoload.php");

try {
    // $API_KEY and $APP_NAME are globals from key.php
    $f = new phpSmug("APIKey=" . $API_KEY, "AppName=" . $APP_NAME);
    // Login Anonymously
    $f->login();
    // Get list of public albums
    $albums = $f->albums_get('NickName=' . $NICKNAME);
    
    $categories = array();
    foreach ($albums as $album) {
        $categories[$album["Category"]["id"]] = $album["Category"]["Name"];
        if (array_key_exists('SubCategory', $album)) {
            $categories[$album["SubCategory"]["id"]] = $album["Category"]["Name"] . "/" . $album["SubCategory"]["Name"];
            $categories[$album["id"]] = $album["Category"]["Name"] . "/" . $album["SubCategory"]["Name"] . "/" . $album["Title"];
        } else {
            $categories[$album["id"]] = $album["Category"]["Name"] . "/" . $album["Title"];
        }
    }
    natsort($categories);
    foreach ($categories as $category_id => $category_name) {
        cli\line($category_id . ") " . $category_name);
    }
    $id = \cli\prompt("Please enter a gallery id");
    
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
    
    foreach ($dnld as $id => $key) {
        $images = $f->images_get("AlbumID={$id}", "AlbumKey={$key}", "Heavy=1");
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
    cli\line("Downloaded $count images");
} catch (Exception $e) {
    echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
