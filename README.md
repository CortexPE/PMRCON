<h1>PMRCON<img src="https://raw.githubusercontent.com/CortexPE/PMRCON/master/pmrcon.png" height="64" width="64" align="left"></img>&nbsp;<img src="https://poggit.pmmp.io/ci.shield/CortexPE/PMRCON/~"></img></h1>
<br />

A PocketMine-MP Virion to easily send RCON Commands to remote Minecraft / PocketMine-MP Servers

# Usage:
Installation is easy, you may get a compiled phar [here](https://poggit.pmmp.io/ci/CortexPE/PMRCON/~) or integrate the virion itself into your plugin.

This virion is purely object oriented. So, to use it you'll just have to import the `RCONClient` object.

## Basic Usage:
### Import the classes
You'll need to import this class in order to easily use it within our code.
```php
<?php

use CortexPE\PMRCON\RCONClient;
use pocketmine\Server; // optional, only used here in the example to easily get the server's logger
```
### Construct the `RCONClient` object
You'll need the remote server's IP Address, Port, and the RCON Password for this.

You'll have to supply the timeout seconds as well, `2` seconds is the recommended value.

Supply the server's logger (You cannot use the plugin's logger) and that's all that's needed.

This will start a new `RCONClientThread` thread and automatically attempt to login.
```php
// $rcon = new RCONClient("tcp://ADDRESS HERE", PORT, "PASSWORD", TIMEOUT, Server::getInstance()->getLogger());
$rconClient = new RCONClient("tcp://127.0.0.1", 19133, "SuperStronkPassword", 2, Server::getInstance()->getLogger());
```
### Sending the commands
You can easily send commands to the remote server now! This will enqueue the command to the `RCONClientThread`s command queue then send it to the remote server, whenever possible.
```php
$rconClient->sendCommand("say Hello World!");
```
Easy as 1-2-3! :tada:
# Sample Code used to test this API earlier:
```php
$rconClient = new RCONClient("tcp://127.0.0.1", 19133, "ILYErinUwU", 2, Server::getInstance()->getLogger());
$rconClient->sendCommand("say IT WORKS!");
$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($rconClient) : void {
	$rcon->stop();
}), 100); // Stop the RCON Client after 5 seconds
```
-----
**This API was made with :heart: by CortexPE, Enjoy!~ :3**