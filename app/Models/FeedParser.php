<?php

/**
 * Parses each item in a Reddit feed and adding it to our database if it does
 * not already exist.
 *
 */
class FeedParser
{
    var $songs = array();
    var $extractor;

    function __construct()
    {
        $this->extractor = new GenreExtractor();
    }

    /**
     * RSS feed url with no parameters
     *
     * @param string $feedUrl
     * @return void
     *
     */
    function load($feedUrl)
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->load($feedUrl);

        $xpath = new DomXPath($doc);
        $items = $xpath->query('/rss/channel/item');

        $this->songs = array();

        foreach($items as $item)
        {
            $title = $this->single($item->childNodes, 'title');
            $link = $this->single($item->childNodes, 'link');
            $description = $this->single($item->childNodes, 'description');
            $pubDate = $this->single($item->childNodes, 'pubDate');
            $pubDate = strtotime($pubDate->nodeValue);
            $redditID = $this->extractRedditID($link->nodeValue);
            $ytid = $this->extractYoutubeID($description->nodeValue);

            $s = new Song();
            $s->redditID = $redditID;
            $s->youtubeID = $ytid;
            $s->title = $title->nodeValue;
            $s->link = $link->nodeValue;
            $s->genres = $this->extractGenres($title->nodeValue);
            $s->flag = count($s->genres) == 0;
            $s->pubDate = $pubDate;
            $this->songs[] = $s;
        }
    }

    function single($nodes, $nodeName)
    {
        foreach($nodes as $node)
        {
            if($node->nodeName == $nodeName)
            {
                return $node;
            }
        }
        return null;
    }

    // TODO: Move to test suite
    function test()
    {
        $songs = array(
            'Some random song, I forgot to add generes!' =>
                array(),
            'Amon Tobin- Easy Muffin [atmospheric/experimental/downtempo/eletronic]' =>
                array('atmospheric', 'experimental', 'downtempo', 'electronic'),
            'Utada Hikaru - Kremlin Dusk [Experimental Pop/Rock]' =>
                array('experimental pop', 'rock'),
            'Can - Mushroom [experimental rock]' =>
                array('experimental rock'),
            'Panda Bear - "Boneless" [Noah Lennox of Animal Collective] [experimental, noise pop, lo-fi]' =>
                array('experimental', 'noise pop', 'lo-fi'),
            'The Books - Smells Like Content [experimental]' =>
                array('experimental'),
            'Joan of Arc - A Tell-Tale Penis [indie, experimental, art rock]' =>
                array('indie', 'experimental', 'art rock'),
            'Slowblow - Within Tolerance [icelandic/acoustic/experimental...ish]' =>
                array('icelandic', 'acoustic', 'experimental'),
            'The Microphones - Map [Lo-fi/experimental/Indie]' =>
                array('lo-fi', 'experimental', 'indie'),
            'Neu! - Negativland [krautrock, experimental, proto-industrial, 1972]' =>
                array('krautrock', 'experimental', 'proto-industrial', '70s'),
            'Micachu - Vulture [ Alternative? / Experimental ]' =>
                array('alternative', 'experimental'),
            'Motionless Battle - Epicsky II [Nintendocore, 8-bit, Chiptunes]' =>
                array('nintendocore', '8-bit', 'chiptune'),
            'Headphones On Your Heart - LEENI [ 8-bit Techno, but actually good, ]' =>
                array('8-bit techno'),
            'Tomatito & ? - Flamenco soufi [moroccan music? & flamenco] - unbelievable life performance' =>
                array('moroccan', 'flamenco'),
            'The Art Of Noise - The Holy Egoism Of Genius (1999) [ drum&bass / electronic / ambient ]' =>
                array('drum & bass', 'electronic', 'ambient'),
            'Steve Earle - Colorado Girl [Townes Van Zandt cover][live on Letterman]' =>
                array('cover', 'live'),
            'Phoenix and AIR: Kelly watch the stars [LIVE on Jools Holland]' =>
                array('live'),
            'Gang Of Four - To Hell With Poverty [post-punk / major influence on fugazi ]' =>
                array('post-punk'),
            'Josh Pyke - Middle of the Hill [folk pop, singer-songwriter]' =>
                array('folk pop', 'singer-songwriter'),
            'Jenny Owen Youngs - Hot In Herre [Singer/Songwriter Cover]' =>
                array('singer-songwriter', 'cover'),
            'Soko - I\'ll Kill Her (Acoustic Ver.) [Singer-songwriter]' =>
                array('singer-songwriter'),
            'John Frusciante - Going Inside [singer-songwriter, alternative, rock] (He is the guitarist in RHCP)' =>
                array('singer-songwriter', 'alternative', 'rock'),
            'Jackie Leven - Call Mother a Lonely Field [singer/songwriter]' =>
                array('songwriter'),
            'Static-X - The Trance Is The Motion (live) [Industrial / Nu-Metal]' =>
                array('industrial', 'nu metal'),
            'FreQ Nasty vs. Bassnectar - Viva Tibet (east and west mix) [Breaks, Nu Breaks, OMFGBASSLINE]' =>
                array('breaks', 'nu breaks'),
            'Decyfer Down - Break Free [alternativ/nu metal]' =>
                array('alternative', 'nu metal'),
            'Genuflect - Slowlyfallingfaster [nu metal, 2009] formerly Reveille. Nu metal lives on.' =>
                array('nu metal', '2009'),
            'Supreme Beings of Leisure - Never The Same [Nu Jazz]' =>
                array('nu jazz'),
            'Girl Talk - Set it off [pop mashup]' =>
                array('pop mashup'),
            'Super Mash Bros - Broseidon, Lord of the Brocean [Mashup, Hip-Hop, Pop, Dance]' =>
                array('mashup', 'hip-hop', 'pop', 'dance'),
            'Bobby Martini - Kill Bill in the Air Tonight [Mashup, Phil Collins, Kill Bill Theme]' =>
                array('mashup'),
            'Saw these guys at the weekend! Blew my mind! [Infected Mushroom - I Wish] (not the same gig :( )' =>
                array(),
            'Anya Marina - Whatever you like [Acoustic folk T.I. cover]' =>
                array(),
            'Ergo Ego - Open (live)[rock/a band i used to be in]' =>
                array('rock'),
            'The Jacksons - Blame it on the Boogie [I just cannot get tired of this]' =>
                array(),
            'Jordy van Loon - Verliefdheid [I really don\'t know...]' =>
                array(),
            'Knorkator - Ich erschoss den Kommissar [Offbeat cover of "I shot the Sherriff"]' =>
                array(),
            'Ulver - The Future Sound of Music [IDM I guess]' =>
                array(),
            'Saul Williams - Talk To Strangers [spoken word - alt. hiphop]' =>
                array(),
            'Tom Waits - Frank\'s Wild Years [bluesy spoken word]' =>
                array(),
            'Neon Indian - Mind, Drips [synth-pop/psychedelic]' =>
                array('synth pop', 'psychedelic'),
            'Michelle Shocked: Anchorage [80s Folk]' =>
                array(),
            'Vibrators - "Disco in Moscow" [UK Punk, 1988]' =>
                array('uk punk', '80s'),
            'The Three Amigos - "Blue Shadows" [Western, 1986]' =>
                array('western', '80s'),
            'Stars - Personal (In Our Bedroom After The War, 2007) [indie/downtempo/depressing/online dating]' =>
                array('indie', 'downtempo'),
            'Polaris- Waiting for October [Indie, 90s, Pete and Pete!]' =>
                array('indie', '90s'),
            'Yacht - Psychic City [Indie-Pop]' =>
                array('indie pop')
        );

        echo '<div style="font-family:monospace;">';
        foreach($songs as $title => $genres)
        {
            $res = $this->extractGenres($title);

            $color = 'red';
            if(count($res) == count($genres) && count(array_diff($res, $genres)) == 0)
            {
                $color = 'green';
            }

            echo "<span style='color:$color;'>$title<br />E:" . print_r($genres, true) . "<br />A:" . print_r($res, true);
            echo '</span><hr />';
        }
    }

    /**
     * Extract the YouTube ID from a Url.
     * Source: http://www.bytemycode.com/snippets/snippet/680/
     *
     * @param string $url
     * @return string YouTube ID or FALSE if match failed.
     *
     */
    function extractYoutubeID($url)
    {
        if(preg_match('%youtube\.com/(watch\?v=|v/|vi/)(.[^"&]+)%', $url, $match))
        {
            return $match[2];
        }
        return false;
    }

    function extractGenres($title)
    {
        $result = $this->extractor->parseTitle($title);
        return $result;
    }

    function extractRedditID($link)
    {
        if(preg_match('%listentothis/comments/([a-z0-9]+)/%', $link, $match))
        {
            return $match[1];
        }
        return false;
    }
}

