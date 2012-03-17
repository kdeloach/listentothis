<?php

class GenreExtractor
{
    // Dictionary of music genres
    var $dict = null;

    function __construct()
    {
        $this->loadDictionary();
    }

    function loadDictionary()
    {
        $config = ConfigurationManager::instance();
        $webPath = (string)$config->setting('webPath');
        $this->dict = file($webPath . 'content/dict.txt');
        $this->dict = array_map('trim', $this->dict);
    }

    function parseTitle($title)
    {
        $result = array();
        $title = $this->cleanTitle($title);
        $blocks = $this->getBlocks($title);

        foreach($blocks as $block)
        {
            /* This gives us more genre coverage.
             * Without space delim, "trance pop/rock" becomes [experimental, rock]
             * WITH space delim, "trance pop/rock" becomes [trance, pop/rock]
             */
            $genres_autodelim = $this->parseBlock($block);
            $genres_spacedelim = $this->parseBlock($block, ' ');

            $genres = array_merge($genres_autodelim, $genres_spacedelim);
            $result = array_merge($result, $genres);
        }

        $result = array_unique($result);
        return $result;
    }

    /**
     * Get all matches of "[...]"
     *
     * @return array
     *
     */
    function getBlocks($title)
    {
        if(preg_match_all("%\[(.[^\]]+)\]%", $title, $matches))
        {
            return $matches[1];
        }
        return array();
    }

    /**
     * Parse all the stuff between [...]
     *
     * @return array List of genres
     */
    function parseBlock($block, $delim=null)
    {
        $result = array();
        $words = array();

        $block = trim($block);

        if($delim == null)
        {
            $delim = $this->mostFrequentDelim($block);
        }

        if($delim === false)
        {
            // Block has no delims so just use entire block as a word.
            $words[] = $block;
        }
        else
        {
            $words = explode($delim, $block);
        }

        $words = array_map('trim', $words);

        // Preserve "date" words that may be stripped after spellcheck
        $dateWords = $this->parseDates($words);

        // Spellcheck
        foreach($words as $k => $word)
        {
            $found = $this->closest_word($word);
            if($found === false)
            {
                unset($words[$k]);
                continue;
            }
            $words[$k] = $found;
        }

        $words = array_merge($words, $dateWords);

        $result = $words;
        return $result;
    }

    function mostFrequentDelim($str)
    {
        preg_match_all('%(, ?| ?/ ?)%', $str, $matches);

        $freq = array(',' => 0, '/' => 0);
        foreach($matches[1] as $delim)
        {
            $k = $delim;
            // To prevent "," and ", " from becoming two different keys, etc.
            if(strlen($k) > 1)
            {
                $k = trim($k);
            }
            if(!isset($freq[$k]))
            {
                $freq[$k] = 1;
            }
            else
            {
                $freq[$k]++;
            }
        }

        // Apply weights.
        $weighted = $freq;
        foreach($freq as $delim => $count)
        {
            switch($delim)
            {
                case ',':
                    $count *= 2;
                    break;
            }
            $weighted[$delim] = $count;
        }
        $freq = $weighted;

        asort($freq, SORT_NUMERIC);

        $highest = end($freq);
        $highestItems = $this->whereEqual($freq, $highest);

        if(count($highestItems) == 0)
        {
            return key($highestItems);
        }

        $orderOfPrecedence = array(',', '/');
        foreach($orderOfPrecedence as $delim)
        {
            if(isset($highestItems[$delim]))
            {
                return $delim;
            }
        }

        return key($highestItems);
    }

    function whereEqual($items, $term)
    {
        $result = array();
        foreach($items as $item)
        {
            if($item == $term)
            {
                $result[] = $item;
            }
        }
        return $result;
    }

    function parseDates($words)
    {
        $result = array();
        foreach($words as $word)
        {
            // Replace full year with shorthand (Ex. 1970 -> 70s)
            if(preg_match('/^19(\d)\d$/', $word, $matches))
            {
                $thirdDigit = $matches[1];
                $word = sprintf('%s0s', $thirdDigit);
                $result[] = $word;
            }
            // Preserve shorthand years (Ex. 90s, 80s, etc.)
            elseif(preg_match('/^\d0s$/', $word, $matches))
            {
                $result[] = $matches[0];
            }
            // Preserve 20xx dates
            elseif(preg_match('/^20\d{2}$/', $word, $matches))
            {
                $result[] = $matches[0];
            }
        }
        return $result;
    }

    /**
     * Perform some work on title to improve format for parsing.
     *
     * @param string $title
     * @return string
     *
     */
    function cleanTitle($title)
    {
        $title = strtolower($title);
        $title = $this->removePhraseIsh($title);
        return $title;
    }

    /**
     * Remove "ish" phrases such as: "rock...ish", "rock-ish"
     *
     * @param string $title Song title
     * @return string
     *
     */
    function removePhraseIsh($title)
    {
        $result = preg_replace("/(\.*ish|-ish)/i", '', $title);
        if($result != null)
        {
            return $result;
        }
        return $title;
    }

    /*
     * Source: http://us3.php.net/manual/en/function.levenshtein.php
     */
    function closest_word($input, $words=null)
    {
        if($words == null)
        {
            $words = $this->dict;
        }
        $shortest = -1;
        foreach($words as $word)
        {
            $lev = levenshtein($input, $word);
            if ($lev == 0)
            {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0)
            {
                $closest  = $word;
                $shortest = $lev;
            }
        }

        $percent = 1 - levenshtein($input, $closest) / max(strlen($input), strlen($closest));

        if($percent < 0.75)
        {
            return false;
        }

        return $closest;
    }
}
