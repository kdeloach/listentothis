<?php

header('Content-Type: text/html; charset=utf-8');

class IndexController extends Controller
{
    function __construct()
    {
        parent::__construct();
    }

    function index()
    {
        $playlist = new Playlist();
        $seed = $playlist->getSeed();
        srand($seed);

        $title = '';
        $song = $playlist->getCurrentSong();

        if($song == null)
        {
            die('no songs');
        }

        $title = $song->title;

        $vars = array (
            'nextUrl' => htmlentities($playlist->getNextSongUrl()),
            'prevUrl' => htmlentities($playlist->getPreviousSongUrl()),
            'genreList' => $this->getGenreList(),
            'seed' => $seed,
            'currentSong' => $song,
            'title' => $title,
            'siteUrl' => (string)$this->config->setting('siteUrl'),
            'songTitle' => htmlentities($song->title)
        );
        echo $this->render('index', $vars);
    }

    function test()
    {
        $fp = new FeedParser();
        $fp->load('http://www.reddit.com/r/listentothis/search.rss?sort=old&count=201&q=listentothis&limit=100&before=t3_futdf');
        var_dump($fp);
    }

    function update()
    {
        set_time_limit(0);
        $parser = new FeedParser();

        $lastSong = new Song();
        $keepGoing = true;
        $totalSongsImported = 0;
        $failedAttempts = 0;

        while($keepGoing)
        {
            if($failedAttempts > 10)
            {
                echo 'Failed to load feed.<br />';
                break;
            }

            $songsImported = 0;
            $keepGoing = false;

            $url = sprintf('http://www.reddit.com/r/listentothis/search.rss?q=listentothis&restrict_sr=on&sort=new&limit=100&after=t3_%s', $lastSong->redditID);
            echo sprintf("Loading %s<br />", $url);

            try
            {
                $parser->load($url);
            }
            catch(Exception $ex)
            {
                $keepGoing = true;
                $failedAttempts++;
                sleep(10);
                continue;
            }

            $songs = $parser->songs;

            if(count($songs) == 0)
            {
                echo 'There are no more songs to import.<br />';
                break;
            }

            foreach($songs as $s)
            {
                if($s->youtubeID === false)
                {
                    echo "Skipping " . $s->title . "<br />";
                    continue;
                }
                if($s->existsInDatabase())
                {
                    echo "Song already exists " . $s->title . "<br />";
                    continue;
                }
                echo "Saving " . $s->title . "<br />";
                $s->save();
                $songsImported++;
                // Keep going if at least one new song appears in feed
                $keepGoing = true;
            }

            $totalSongsImported += $songsImported;

            echo sprintf('Imported %s songs.<br />', $songsImported);
            echo sprintf('Total: %s<br />', $totalSongsImported);
            echo '<hr />';

            flush();
            ob_flush();

            $lastSong = end($songs);
            if( $lastSong == null )
            {
                echo 'Could not determine last song added.<br/>';
                break;
            }

            sleep(3);
        }
    }

    function getLastSongAdded()
    {
        $db = DBContext::instance();
        $res = $db->query('select redditID from song order by id desc limit 1');
        if(count($res) > 0)
        {
            $row = $res[0];
            return Song::load($row->redditID);
        }
        return null;
    }

    function getOldestSong()
    {
        $db = DBContext::instance();
        $res = $db->query('select redditID from song order by pubDate limit 1');
        if(count($res) > 0)
        {
            $row = $res[0];
            return Song::load($row->redditID);
        }
        return null;
    }

    function getGenreList()
    {
        $db = DBContext::instance();
        $sql = "
            SELECT name, value, total
            FROM (
                SELECT 'all songs' AS name, 'all' AS value, COUNT(*) AS total
                FROM song
                UNION ALL

                SELECT 'added today' AS name, 'recent' AS value, COUNT(*) AS total
                FROM song
                WHERE pubDate >= NOW() - INTERVAL 1 DAY
                UNION ALL

                (SELECT g.name, g.name AS value, COUNT(*) AS total
                FROM song_genre sg
                INNER JOIN genre g ON g.id = sg.genreid
                GROUP BY g.name
                HAVING total >= 15
                ORDER BY g.name)
            ) t1
        ";
        $res = $db->query($sql);
        $genreList = array();
        foreach($res as $row)
        {
            $li = new ListItem();
            $li->value = $row->value;
            $li->text = htmlentities(sprintf('%s (%s)', $row->name, $row->total));
            $li->selected = isset($_GET['genre']) && $_GET['genre'] == $row->value;
            $genreList[] = $li;
        }
        return $genreList;
    }
}

