<?php

namespace Travis\Slack;

class RSS {

    /**
     * Filters for incoming links (find => replace).
     *
     * @var     array
     */
    protected static $filters = array(
        'https://www.google.com/url?q=' => ''
    );

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
        $feed->description = '';
        $feed->lang = 'en';

        // get entries
        $entries = ex($results, 'feed.entry', array());

        // check keys (a single entry jacks things up)
        $keys = array_keys($entries);

        // if this is single entry...
        if (!is_numeric($keys[0]))
        {
            // fix
            $entries = array($entries);
        }

        // search and replace
        $find = array_keys(static::$filters);
        $replace = array_values(static::$filters);

        // foreach result...
        foreach ($entries as $entry)
        {
            // vars
            $link = str_ireplace($find, $replace, ex($entry, 'link.attr.href'));
            $date = ex($entry, 'updated.value');

            // add to feed
            $feed->add(null, null, $link, $date, null); // title, author, link, date, description
        }

        // return
        return $feed->render('atom');
    }

}