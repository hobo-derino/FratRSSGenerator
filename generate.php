<?php
    require 'vendor/autoload.php';

    use iTunesPodcastFeed\Channel;
    use iTunesPodcastFeed\FeedGenerator;
    use iTunesPodcastFeed\Item;
    
    // TODO - fetch this crap from a WordPress content endpoint and manage these links in WP
    $feeds = [
        'http://burningboots.libsyn.com/rss',
        'https://www.thedailyliberator.com/feed/podcast/',
        'https://anchor.fm/s/14240950/podcast/rss',
        'https://anchor.fm/s/108df1ac/podcast/rss',
        'http://thegaslighthour.libsyn.com/rss',
        'https://feed.podbean.com/seanvplanet/feed.xml',
        'https://anchor.fm/s/8febb38/podcast/rss',
        'https://anchor.fm/s/ae07450/podcast/rss',
        'https://anchor.fm/s/10becb88/podcast/rss',
        'https://anchor.fm/s/10f98cf0/podcast/rss',
        'https://anchor.fm/s/12927d9c/podcast/rss',
        'https://anchor.fm/s/1b8ac2ec/podcast/rss',
        'https://anchor.fm/s/13450afc/podcast/rss'
    ];

    $title = '(DEMO FEED) The FRAT House: Beer-battered Liberty Podcasts';
    $link = 'http://frathousepodcasts.com';
    $author = 'The FRAT House';
    $email = 'frathousepodcasts@gmail.com';
    $image = 'https://i.imgur.com/mbVuuEQ.png';
    $explicit = true;
    $categories = [
        'News',
        'Culture',
        'Comedy',
        'Politics'
    ];
    $description = 'The FRAT House is a fraternity of podcasting men who love liberty and beer.';
    $lang = 'en';
    $copyright = 'Intellectual property is gay';
    $ttl = 43200;

    $channel = new Channel(
        $title, $link, $author, $email,
        $image, $explicit, $categories,
        $description, $lang, $copyright, $ttl
    );

    $episodes = array();
    $rss = new SimplePie();

    //Loop through each of the feed endpoints
    foreach($feeds as $feed){
        $rss->set_feed_url($feed);
        $rss->enable_cache(false);
        $success = $rss->init();
        $rss->handle_content_type('text/plain');
        // If it's valid
        if ($success){
            // Loop through each episode and push to a master list of $episodes
            $items = $rss->get_items();
            foreach($items as &$episode){
                $item = new stdClass();
                $item->title = $rss->get_title() . ' - ' . $episode->get_title();
                $item->fileUrl = $episode->get_enclosures()[0]->link;
                $item->duration = gmdate("H:i:s", $episode->get_enclosures()[0]->duration);
                $item->description = $episode->get_content();
                $item->date = strtotime($episode->get_date());
                $item->filesize = $episode->get_enclosures()[0]->length;
                $item->mime = $episode->get_enclosures()[0]->type;
                array_push($episodes, $item);
                unset($item);
            }
        }
    }

    $feedItems = array();

    // Sort all of the $episodes by release date descending
    usort($episodes, function($a, $b){
        return $a->date < $b->date ? 1 : -1;
    });

    // Loop each episode and add it to $feedItems with standard iTunes Generator properties
    foreach ($episodes as $episode){
        if($episode->fileUrl){
            $item = new Item(
                $episode->title, 
                $episode->fileUrl, 
                $episode->duration,
                $episode->description, 
                $episode->date, 
                $episode->filesize == null ? 0 : $episode->filesize,
                $episode->mime
            );
            array_push($feedItems, $item);
            unset($item);    
        }
    }    

    // Generate the new XML feed
    $feed = new FeedGenerator($channel, ...$feedItems);

    // header('Content-Type: application/xml; charset=utf-8');

    // Write to a file which will be displayed with index.php
    $file = fopen("feed.xml", "w");
    fwrite($file, $feed->getXml());
    fclose($file);
?>