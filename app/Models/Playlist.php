<?php

class Playlist
{
    var $prevsong;
    var $nextsong;

    var $db;
    var $c_currsong;
    var $queryHistory = array();

    function __construct()
    {
        $this->db = DBContext::instance();

        $seed = $this->getSeed();
        $genre = $this->getGenre();

        $sqlFilter = '';
        switch($genre)
        {
            case 'all':
                break;
            case 'today':
                $sqlFilter = '
                    WHERE ns.pubDate >= NOW() - INTERVAL 1 DAY
                ';
                break;
            default:
                $sqlFilter = sprintf("
                    INNER JOIN song_genre sg ON sg.songID = ns.ID
                    INNER JOIN genre g on g.ID = sg.genreID
                    WHERE g.name='%s'", $genre);
                break;
        }

        $sql = "
            SELECT t1.redditID AS currsong, t2.redditID AS prevsong, t3.redditID AS nextsong
            FROM
            (SELECT @a := 0) tmp1,
            (SELECT @b := 0) tmp2,
            (SELECT @c := 0) tmp3,
            (SELECT @numrows := (SELECT COUNT(*) FROM song AS ns $sqlFilter)) tmp4,
            (
                SELECT s.redditID, (@a := @a+1) AS rownum
                FROM (SELECT redditID FROM song AS ns $sqlFilter ORDER BY RAND($seed)) AS s
            ) t1
            INNER JOIN (
                SELECT s.redditID, (@b := @b+1) MOD @numrows + 1 AS rownum
                FROM (SELECT redditID FROM song AS ns $sqlFilter ORDER BY RAND($seed)) AS s
            ) t2 ON t2.rownum = t1.rownum
            INNER JOIN (
                SELECT s.redditID, ((@c := @c+1) + @numrows - 2) MOD @numrows + 1 AS rownum
                FROM (SELECT redditID FROM song AS ns $sqlFilter ORDER BY RAND($seed)) AS s
            ) t3 ON t3.rownum = t1.rownum
            WHERE t1.redditID = :redditID
        ";

        $song = $this->getCurrentSong();
        if($song != null)
        {
            $params = array(
                ':redditID' => $song->redditID
            );

            $this->queryHistory[] = $sql;
            $res = $this->db->query($sql, $params);
            if(count($res) > 0)
            {
                $row = $res[0];
                $this->prevsong = $row->prevsong;
                $this->nextsong = $row->nextsong;
            }
        }
    }

    function getNextSongUrl()
    {
        return sprintf('?seed=%s&song=%s&genre=%s',
            $this->getSeed(),
            $this->nextsong,
            urlencode($this->getGenre()));
    }

    function getPreviousSongUrl()
    {
        return sprintf('?seed=%s&song=%s&genre=%s',
            $this->getSeed(),
            $this->prevsong,
            urlencode($this->getGenre()));
    }

    function getSeed()
    {
        $seed = isset($_GET['seed']) ? $_GET['seed'] : null;
        if($seed != null)
        {
            if(filter_var($seed, FILTER_VALIDATE_INT))
            {
                return $seed;
            }
        }
        return rand();
    }

    function getGenre()
    {
        $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
        if($genre == null)
        {
            return 'all';
        }
        return $genre;
    }

    function getCurrentSong()
    {
        if(isset($this->c_currsong))
        {
            return $this->c_currsong;
        }

        $genre = $this->getGenre();

        $redditID = isset($_GET['song']) ? $_GET['song'] : null;
        if($redditID == null)
        {
            $redditID = $this->getRandomSongRedditID($genre);
        }
        if($redditID == null)
        {
            return null;
        }

        $sql = '
            SELECT id, redditID, youtubeID, title, link
            FROM song
            WHERE redditID=:redditID
        ';
        $this->queryHistory[] = $sql;
        $res = $this->db->query($sql, array(':redditID' => $redditID));
        if(count($res) > 0)
        {
            $row = $res[0];
            $s = new Song();
            $s->id = $row->id;
            $s->redditID = $row->redditID;
            $s->youtubeID = $row->youtubeID;
            $s->title = $row->title;
            $s->link = $row->link;
            $this->c_currsong = $s;
            return $s;
        }
        return new Song();
    }

    function getRandomSongRedditID($genre)
    {
        $sqlFilter = '';
        $params = array();
        switch($genre)
        {
            case 'all':
                break;
            case 'today':
                $sqlFilter = '
                    WHERE ns.pubDate >= NOW() - INTERVAL 1 DAY
                ';
                break;
            default:
                $sqlFilter = '
                    INNER JOIN song_genre sg ON sg.songID = ns.ID
                    INNER JOIN genre g on g.ID = sg.genreID
                    WHERE g.name=:genre
                ';
                $params[':genre'] = $this->getGenre();
                break;
        }

        $seed = $this->getSeed();
        $sql = "
            SELECT redditID
            FROM song ns
            $sqlFilter
            ORDER BY RAND($seed)
            LIMIT 1
        ";
        $this->queryHistory[] = $sql;
        $res = $this->db->query($sql, $params);
        if(count($res) > 0)
        {
            $row = $res[0];
            return $row->redditID;
        }
        return null;
    }
}
