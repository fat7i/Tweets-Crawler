# Tweets Crawler 
Tweets Crawler is a minimal microservice that gathering tweets that have a specific "#hashtag" or "word",

I use [standard api](https://developer.twitter.com/en/docs/tweets/search/overview) and computing some statistics such as total count and most active user etc.

# How to run the application ?

1- add `"TwitterApi\\": "src/"` to your `psr-4` in [composer.json](composer.json) .

2- create a settings array the contain `consumer_key` and `consumer_secret` of your Twitter application

3- and simply follow the example in [app.php](web/app.php).

4- Finally run the app and you should get a JSON array like:

`
{
    status: "ok",
    hashtag: "#twitter",
    result: {
        total_count: 25,
        day_average: 3,
        most_active_user: {
            user_id: 123456789,
            user_name: "abcdef",
            user_screen_name: "abcdef",
            total_tweets: 5
        }
    }
}
` 


# How it works?

- Simply when the application starts, it validates the `#hashtag` string and throws an exception if not valid.

- Then starting to curl tweets by requesting the Twitter API recursively, and Insert the Tweets into SQLite file.

- During the curl, if curl failed, the app will sleep 1 sec and retry to curl it again.

- In case HTTP code equal 429, it means the rate limit exceeded, and the app should wait to reset, the app calculates how much wait to retry by reading header info, then retry to continue curling.

- The Application uses `Application-only authentication`.


For any question feel free to [email me](fat7i.wp@gmail.com) me anytime.