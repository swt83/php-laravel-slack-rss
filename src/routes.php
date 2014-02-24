<?php

Route::get('slack/rss/mirror', function()
{
    return Travis\Slack\RSS::mirror();
});