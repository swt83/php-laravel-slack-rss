<?php

namespace Travis\Slack;

use Travis\Shorty;

class RSS {

    /**
     * Handle incoming mirror request.
     *
     * @return  string
     */
    public static function mirror()
    {
        // capture
        $url = \Input::get('url');

        // catch error...
        if (!$url) trigger_error('No URL provided.');

        // hash
        $hash = md5(serialize($url));

        // load from cache...
        $results = \Cache::remember('slack_rss_'.$hash, 5, function() use($hash, $url)
        {
            // convert
            $xml = \Travis\XML::from_url($url)->to_array();

            // return
            return $xml;
        });

        // name
        $name = \Input::get('name', $hash);

        // return
        return static::to_rss($name, $results);
    }

    /**
     * Return an RSS feed w/ a given name.
     *
     * @param   string  $name
     * @param   mixed   $results
     * @return  string
     */
    protected static function to_rss($name, $results)
    {
        // new feed
        $feed = \Feed::make();

        // set title
        $feed->title = 'rss ['.strtolower($name).']';
        $feed->description = $feed->title;
        $feed->link = static::filter(\URL::current().'?'.http_build_query(\Input::all()));
        $feed->lang = 'en';

        // get entries
        $entries = ex($results, 'feed.entry', array());

        // if results...
        if ($entries)
        {
            // check keys (a single entry jacks things up)
            $keys = array_keys($entries);

            // if this is single entry...
            if (!is_numeric($keys[0]))
            {
                // fix
                $entries = array($entries);
            }

            // foreach result...
            foreach ($entries as $entry)
            {
                // vars
                $link = static::filter(ex($entry, 'link.attr.href'));
                $date = ex($entry, 'updated.value');

                // detect google feed...
                if (preg_match('/google.com/i', $link))
                {
                    // shortify
                    $link = Shorty::run($link);
                }

                // add to feed
                $feed->add(null, null, $link, $date, null); // title, author, link, date, description
            }
        }

        // return
        return $feed->render('atom');
    }

    /**
     * Return a filtered URL string.
     *
     * @param   string  $string
     * @return  string
     */
    protected static function filter($string)
    {
        // make filter
        $filters = array();
        $filters['&'] = '&amp;';

        // search and replace
        $find = array_keys($filters);
        $replace = array_values($filters);

        // return
        return str_ireplace($find, $replace, $string);
    }

}