<?php

class Song
{
    var $id;
    var $redditID;
    var $youtubeID;
    var $title;
    var $link;
    var $genres = array();
    var $flag = false;
    var $pubDate;

    function __construct()
    {
    }

    static function load($redditID)
    {
        $db = DBContext::instance();
        $res = $db->query('select * from song where redditID=:redditID', array(':redditID' => $redditID));
        if(count($res) > 0)
        {
            $row = $res[0];
            $s = new Song();
            $s->id = $row->id;
            $s->redditID = $row->redditID;
            $s->youtubeID = $row->youtubeID;
            $s->title = $row->title;
            $s->link = $row->link;
            $s->flag = $row->flag;
            $s->pubDate = strtotime($row->pubDate);
            return $s;
        }
        return null;
    }

    function existsInDatabase()
    {
        $db = DBContext::instance();
        $res = $db->query('select 1 from song where redditID=:redditID', array(':redditID' => $this->redditID));
        return count($res) > 0;
    }

    function save()
    {
        if(!isset($this->id))
            $this->insert();
        else
            $this->update();
    }

    function insert()
    {
        $db = DBContext::instance();

        // Insert song
        $sql =
            'insert into song (redditID, youtubeID, title, link, flag, dateAdded, pubDate)
             values(:redditID, :youtubeID, :title, :link, :flag, utc_timestamp(), from_unixtime(:pubDate))';
        $vals = array(
            ':redditID'  => $this->redditID,
            ':youtubeID' => $this->youtubeID,
            ':title'     => $this->title,
            ':link'      => $this->link,
            ':flag'      => $this->flag,
            ':pubDate'   => $this->pubDate
        );

        $res = $db->query($sql, $vals);
        $songID = $db->lastInsertID();

        // Insert genres
        $sql = 'insert into genre (name) values(:name) on duplicate key update name=values(name)';
        foreach($this->genres as $genre)
        {
            $db->query($sql, array(':name' => $genre));
        }

        // Insert song_genre entries
        $strGenreList = "'" . implode("', '", $this->genres) . "'";
        $sql =
            'insert into song_genre (songID, genreID)
             select :songID, id from genre where name in ('. $strGenreList .')';
        $db->query($sql, array(':songID' => $songID));
    }

    function update()
    {
        $db = DBContext::instance();
        $sql =
            'update song set redditID=:redditID, youtubeID=:youtubeID, title=:title, link=:link, flag=:flag
             where id = :id';
        $vals = array(
            ':redditID' => $this->redditID,
            ':youtubeID' => $this->youtubeID,
            ':title' => $this->title,
            ':link' => $this->link,
            ':flag' => $this->flag,
            ':id' => $this->id
        );
        $db->query($sql, $vals);
    }
}
