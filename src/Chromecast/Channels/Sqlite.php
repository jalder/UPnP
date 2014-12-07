<?php

namespace jalder\Upnp\Chromecast\Channels;

class Sqlite implements Channel
{

    private $db; 

    public function __construct()
    {
        
        $this->db = new \PDO('sqlite:'.dirname(__FILE__).'/var/sqlite.db');
        chmod(dirname(__FILE__).'/var/sqlite.db', 0777);
        $this->db->exec('CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY, wait TEXT, message TEXT)');
        $this->db->exec('CREATE TABLE IF NOT EXISTS replies (id INTEGER PRIMARY KEY, wait TEXT, reply TEXT)');
    }

    public function addMessage($message, $execute = true)
    {
        $sql = 'INSERT INTO messages (wait, message) VALUES (:wait, :message)';
        $prep = $this->db->prepare($sql);
        $jmessage = json_encode($message);
        $prep->bindParam(':message', $jmessage);
        if($message['type'] === 'LOAD'){
            $wait = 'RECEIVER_STATUS';
        }
        else{
            $wait = 'MEDIA_STATUS';
        }
        $prep->bindParam(':wait', $wait);
        $prep->execute();
    }

    public function getMessages()
    {
        $results = array();
        $messages = $this->db->query('SELECT wait, message FROM messages');
        foreach($messages as $message){
            $results[$message[0]][] = json_decode($message[1], true);
        }
        $purge = true;
        if($purge){
            $this->db->exec('DELETE FROM messages');
        }
        return $results;
    }

    public function addReply($reply)
    {

    }

    public function getReplies()
    {

    }

}
