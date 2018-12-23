<?php

/**
 *
 *  ___ __  __ ___  ___ ___  _  _
 * | _ \  \/  | _ \/ __/ _ \| \| |
 * |  _/ |\/| |   / (_| (_) | .` |
 * |_| |_|  |_|_|_\\___\___/|_|\_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Written by @CortexPE <https://CortexPE.xyz>
 * Intended for use on SynicadeNetwork <https://synicade.com>
 */

declare(strict_types = 1);

namespace CortexPE\PMRCON;


use pocketmine\Thread;

class RCONClientThread extends Thread {

	private const REQUEST_TYPE_LOGIN = 3;
	private const REQUEST_TYPE_COMMAND = 2;

	/** @var resource */
	protected $socket;
	/** @var \ThreadedLogger */
	protected $logger;
	/** @var bool */
	protected $stopped;
	/** @var \Volatile */
	protected $commandQueue;
	/** @var string */
	private $host;
	/** @var int */
	private $port;
	/** @var string */
	private $password;
	/** @var string */
	private $timeout;

	public function __construct(string $host, int $port = 19132, string $password, int $timeout = 2, \ThreadedLogger $logger){
		$this->logger = $logger;

		$this->stopped = false;
		$this->commandQueue = new \Volatile();

		$this->host = $host;
		$this->port = $port;
		$this->password = $password;
		$this->timeout = $timeout;

		$this->start(PTHREADS_INHERIT_NONE);
	}

	public function enqueueCommand(string $command): void{
		$this->logger->info("[PMRCON] Enqueued command: " . $command);
		$this->commandQueue[] = $command;
	}

	public function run(){
		$this->registerClassLoader();

		$this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
		stream_set_timeout($this->socket, $this->timeout, 0);
		if(!$this->socket){
			$this->logger->error("[PMRCON] [ERROR " . $errno . "] " . $errstr);
		}

		$requestID = mt_rand(1000, 9999);

		$this->sendPacket($requestID, self::REQUEST_TYPE_LOGIN, $this->password);
		$response = $this->receivePacket();

		if($requestID == -1 || $requestID != $response["requestId"]){
			$this->logger->error("[PMRCON] Authentication failed.");
			$this->stop();
		}else{
			$this->logger->info("[PMRCON] Login successful!");

			while(!$this->stopped){
				if($this->commandQueue->count() > 0){
					$cmd = $this->commandQueue->pop();

					$this->logger->info("[PMRCON] Sending command: \"" . $cmd . "\"");
					$this->sendPacket($requestID, self::REQUEST_TYPE_COMMAND, $cmd);

					$response = $this->receivePacket();
					$this->logger->info("[PMRCON] Received response: \"" . $response["payload"] . "\"");
				}
			}
		}
		fclose($this->socket);
	}

	private function sendPacket(int $requestID, int $packetType, string $payload){
		$pk = pack("VV", $requestID, $packetType) . $payload . "\x00\x00";
		$pk = pack("V", strlen($pk)) . $pk;

		return fwrite($this->socket, $pk);
	}

	private function receivePacket(): array{
		return [
			"size"       => ($size = unpack("V", fread($this->socket, 4))[1]),
			"requestId"  => unpack("V", fread($this->socket, 4))[1],
			"packetType" => unpack("V", fread($this->socket, 4))[1],
			"payload"    => substr(fread($this->socket, $size + 2), 0, -2),
		];
	}

	public function stop(){
		$this->stopped = true;
		$this->logger->info("[PMRCON] Stopping RCON Client Thread...");
	}
}