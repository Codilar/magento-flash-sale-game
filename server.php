<?php
$host = 'localhost'; //host
$port = '9000'; //port
$null = NULL; //NULL value
if(count($argv)<2){
	echo "500";
	exit(0);
}
$timer = new Timer($argv[1]);
$file = new File();
$file->makeFile(".flashsale", "");
$timer->start();

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);
//listen to port
socket_listen($socket);
//create & add listning socket to the list
$clients = array($socket);
//start endless loop, so that our script doesn't stop
while (!$timer->over()) {
	//manage multipal connections
	$changed = $clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	//check for new socket
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //accpet new socket
		$clients[] = $socket_new; //add socket to client array
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		
		// $ip = getIPFromSocket($socket_new); //get ip address of connected socket
		// $scores[$ip] = 0;
		// $response = getScores(); //prepare json data
		// send_message($response); //notify all users about new connection
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$msg = json_decode($received_text); //json decode
			$message = $msg->message;
			$delElem = $msg->delete;
			$newElem = $msg->new;
			if($delElem){
				$response = array(
					"delete"	=>	$delElem,
					"message"	=>	$msg->player." added ".truncateWithDots($msg->title, 30)." to Cart"
				);
			}
			else if($newElem){
				$response = array(
					"new"	=>	$newElem,
					"message"	=>	$newElem." has joined the sale!"
				);
				var_dump($timer->remaining());
				$msg = mask(json_encode(array(
					"timer"	=>	$timer->remaining()
				)));
				@socket_write($changed_socket,$msg,strlen($msg));
			}
			else if($msg->lock){
				$response = array(
					"lock"	=>	$msg->lock
				);
				$response_text = mask(json_encode($response));
				send_message($response_text); //send data
				break 2;
			}
			else if($msg->unlock){
				$response = array(
					"unlock"	=>	$msg->unlock
				);
				$response_text = mask(json_encode($response));
				send_message($response_text); //send data
				echo $msg->unlock;
				break 2;
			}
			else{
				$id = $message->id; //product id
				$left = $message->left; //product left
				$top = $message->top; //product top
				$ip = getIPFromSocket($changed_socket);
				$response = array(
					"delete"	=>	$delElem,
					"message"	=>	array(
										"ip"	=>	$ip,
										"id"	=>	$id,
										"left"	=>	$left,
										"top"	=>	$top
									)
				);
			}
			//prepare data to be sent to client
			$response_text = mask(json_encode($response));
			send_message($response_text); //send data
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			getIPFromSocket($changed_socket);
			unset($clients[$found_socket]);
			
			//notify all users about disconnected connection
			// $response = getScores();
			// send_message($response);
		}
	}
}
// close the listening socket
socket_close($socket);
unlink(".flashsale");

function truncateWithDots($str, $len){
	return strlen($str) > $len ? substr($str,0,$len)."..." : $str;
}

function send_message($msg)
{
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}




function getIPFromSocket($socket){
	@socket_getpeername($socket, $ip);
	return $ip;
}








class Timer{
	private $_time,
			$_starttime;

	public function __construct($time){
		$this->_time = $time;
	}

	public function start(){
		$this->_starttime = time();
	}

	public function remaining(){
		$now = time()-$this->_starttime;
		return $this->_time - $now;
	}

	public function over(){
		$now = time()-$this->_starttime;
		if($now > $this->_time){
			return true;
		}
		return false;
	}
}


class File{

	public function makeDirectoryIfNotExists($dirName){
		if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.$dirName)) {
		    mkdir(__DIR__.DIRECTORY_SEPARATOR.$dirName, 0777, true);
		}
		return __DIR__.DIRECTORY_SEPARATOR.$dirName;
	}

	public function makeFile($filePath, $data){
		try{
			$file = fopen($filePath, "w+");
			fwrite($file, $data);
			fclose($file);
			return true;
		}
		catch(Exception $e){
			return false;
		}
	}
}