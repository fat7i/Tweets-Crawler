<?php

namespace TwitterApi;


class Sqlite
{
    /** dir of databases */
    const DB_DIR = '../data/';


    /**
     * Create database file, than create tweets table
     * @param String $dbFileName database name
     * @return \SQLite3
     */
    public static function makeConnection(String $dbFileName)
    {

        $dbFilePath = self::filePath($dbFileName);

        $connection = new \SQLite3($dbFilePath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

        // Create a tweets table.
        $connection->query('CREATE TABLE IF NOT EXISTS "tweets" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            "tweet_id" INTEGER UNIQUE,
            "user_id" INTEGER,
            "user_name" VARCHAR,
            "user_screen_name" VARCHAR,
            "tweet" TEXT,
            "created_at" DATETIME
        )');

        return $connection;
    }

    /**
     * @param String $dbFileName
     * @return string
     */
    public static function filePath (String $dbFileName)
    {
        $dbFilePath = self::DB_DIR.$dbFileName.'.sqlite';

        if (!file_exists(self::DB_DIR))
        {
            mkdir(self::DB_DIR, 0777);
        }

        return $dbFilePath;
    }

    /**
     * @param String $dbFileName
     * @param array $data
     * @return bool
     */
    public static function insertTweets (String $dbFileName, array $data)
    {
        $connection = self::makeConnection($dbFileName);

        $connection->exec('BEGIN');

        $statement = $connection->prepare('INSERT or IGNORE INTO "tweets" ("created_at", "tweet_id", "user_id", "user_name", "user_screen_name", "tweet") VALUES (:created_at, :tweet_id, :user_id, :user_name, :user_screen_name, :tweet)');

        foreach ($data as $tweet)
        {
            $arrayToDB = self::prepareArray($tweet);

            $statement->bindValue(':created_at', $arrayToDB['created_at']);
            $statement->bindValue(':tweet_id', $arrayToDB['tweet_id']);
            $statement->bindValue(':user_id', $arrayToDB['user_id']);
            $statement->bindValue(':user_name', $arrayToDB['user_name']);
            $statement->bindValue(':user_screen_name', $arrayToDB['user_screen_name']);
            $statement->bindValue(':tweet', $arrayToDB['text']);

            $statement->execute();
        }
        $commit = $connection->exec('COMMIT');
        $connection->close();

        return $commit;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function prepareArray (array $array)
    {
        $output = array();
        $output['created_at'] = date( 'Y-m-d H:i:s',strtotime($array['created_at']));
        $output['tweet_id'] = $array['id'];
        $output['user_id'] = $array['user']['id'];
        $output['user_name'] = $array['user']['name'];
        $output['user_screen_name'] = $array['user']['screen_name'];
        $output['text'] = $array['text'];

        return $output;
    }

    /**
     * @param String $dbFileName
     * @return mixed
     */
    public static function getResult (String $dbFileName, String $oneWeekAgo)
    {
        $connection = self::makeConnection($dbFileName);

        $now = date('Y-m-d H:i:s');

        $oneWeekAgo = date('Y-m-d H:i:s', $oneWeekAgo);

        // - total count of tweets for the past week;
        $output['total_count'] = $connection->querySingle("SELECT count(id) FROM tweets WHERE created_at BETWEEN '".$oneWeekAgo."' AND '".$now."'");

        //  - average count per day over the past week;
        $output['day_average'] = $connection->querySingle("SELECT count(id) / count(distinct date(created_at)) FROM tweets WHERE created_at BETWEEN '".$oneWeekAgo."' AND '".$now."'");

        //  - most active user for the input hashtag;
        $output['most_active_user'] = $connection->query("SELECT distinct(t1.user_id), user_name, user_screen_name, (SELECT count(id) FROM tweets t2 WHERE t2.user_id=t1.user_id ) total_tweets FROM tweets t1 WHERE t1.created_at BETWEEN '".$oneWeekAgo."' AND '".$now."' ORDER BY total_tweets DESC limit 1")->fetchArray(SQLITE3_ASSOC);

        $connection->close();

        return $output;
    }
}