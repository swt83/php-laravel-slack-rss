# RSS for Slack

A Laravel PHP package for building a Slack integration for RSS feeds.

Currently only supports mirroring existing feeds, like those from [Google](https://www.google.com/alerts/manage).  The value added is that it strips out all the junk and generates cleaner feeds for your Slack integration.

## Install

Normal install via Composer.

### Providers

Register the service providers in your ``app/config/app.php`` file:

```php
'Roumen\Feed\FeedServiceProvider',
'Travis\Slack\RSS\Provider',
```

Also, add the facades:

```php
'Feed' => 'Roumen\Feed\Facades\Feed',
```

## Usage

Currently only supporting the mirror feature:

```
http://<YOURDOMAIN>/slack/rss/mirror?url=<YOURURL>
```

Add a ``name=<YOURNAME>`` parameter to the query to name the RSS feed as it appears in Slack.

Results are cached for 5 minutes.