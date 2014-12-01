<?php

namespace Travis\Slack;

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

            // name
            $name = \Input::get('name', $hash);

            // build
            $rss = \Travis\Slack\RSS::to_rss($name, $xml);

            // return
            return $rss->getOriginalContent()->render();
        });

        // return
        return \Response::make($results)->header('Content-Type', 'application/rss+xml');
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
        $feed->link = \URL::current().'?'.http_build_query(\Input::all(), '', '&amp;');
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
                // capture
                $title = ex($entry, 'title.value');
                $link = ex($entry, 'link.attr.href');
                $date = ex($entry, 'updated.value');
                $description = ex($entry, 'content.value');

                // if link is google proxy...
                if (preg_match('/google.com/i', $link))
                {
                    // decode url
                    $link = html_entity_decode($link);

                    // breakout
                    parse_str($link, $parts);

                    // get url, or not
                    $link = ex($parts, 'url', $link);
                }

                // fix "&" symbols
                $request = parse_url($link);
                $link = ex($request, 'scheme', 'http').'://'
                    .ex($request, 'host')
                    .ex($request, 'path');
                parse_str(ex($request, 'query'), $arguments); // get query as arguments
                if ($arguments) $link .= '?'.http_build_query($arguments, '', '&amp;');

                // add to feed
                $feed->add(null, null, $link, $date, null); // title, author, link, date, description
            }
        }

        // return
        return $feed->render('atom');
    }

}