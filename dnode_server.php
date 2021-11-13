<?

<?php
// Include Composer-generated autoloader
require(__DIR__.'vendor/dnode/dnode/autoload.php');
// This is the class we're exposing to DNode
class Zinger
{
    // Public methods are made available to the network
    public function zing($n, $cb)
    {
        // Dnode is async, so we return via callback
        $cb($n * 100);
    }
}
$loop = new StreamSelectLoop::StreamSelectLoop();
// Create a DNode serverS
$server = new DNode\DNode($loop, new Zinger());
$server->listen(7070);
$loop->run();

?>