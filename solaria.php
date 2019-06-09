<?php

include __DIR__.'/vendor/autoload.php';

use Discord\DiscordCommandClient;


use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;



use Discord\Cache\Cache;
use Discord\Cache\Drivers\RedisCacheDriver;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;



use Discord\Parts\User\Game;
use Discord\Parts\WebSockets\PresenceUpdate;

global $ver;
$ver="Solaria v0.1𝛽 Build 201712151721 ©2017 jason c. kay All rights reserved.";
global $apiKey;
$apiKey = 'GET YOUR OWN KEY...';

$discord = new Discord([
  'token' => '...AND YOUR OWN TOKEN!',
]);


// Utility functions

// Load in blocked users
global $lfg_blocked_users;
$lfg_blocked_users = json_decode(file_get_contents("./blocked_users"), TRUE);
print_r($lfg_blocked_users);


// Add user to block list
function block_user($user, $lfg_blocked_users) {

	$lfg_blocked_users[$user] = $user;

	file_put_contents("./blocked_users", json_encode($lfg_blocked_users));

} // end function block_user

// Remove user from block list
function unblock_user($user, $lfg_blocked_users) {

	unset($lfg_blocked_users[$user]);

	file_put_contents("./blocked_users", json_encode($lfg_blocked_users));

} // end function unblock_user

// Case-insensative function to verify if a message (string) contains given text
function contains($string1 = "", $string2 = "") {

	if (stripos($string1, $string2) === false) {
		$retVal = false;
	} else {
		$retVal = true;
	}

	return $retVal;

} // end function contains

// Case-insensitive function to verify if a message (string) begins with a given string
function begins($string1 = "", $string2 = "") {

	if (stripos($string1, $string2) === FALSE) {
		$retVal = false;
	} else {
		if (stripos($string1, $string2) == 0) {
		$retVal = true;
		} else {
			$retVal = false;
		}
	}
	return $retVal;
} // end function begins


// Destiny-related functions

// Clan Challenge Leaderboard
function clan_challenge($message) {

		$outmessage = shell_exec('./show-game.sh '.escapeshellarg(substr($message->content, 16)));
		$message->channel->sendMessage('Here is the current season\'s clan leaderboard:
'.$outmessage);

} // end function clan_challenge

//RNG Shaming
function shame($message) {
		$shame_user = substr($message->content, 6);
		$outmessage = 'http://solarian.net/boot/image.php?'.urlencode($shame_user);
		$message->channel->sendMessage($outmessage);

} // end function shame

// Main message loop

$discord->on('ready', function ($discord) {

    // LOG: Initialisation
    echo "Bot is ready.", PHP_EOL;

    // Message processing function
    $discord->on('message', function ($message) {
    // LOG: User, Channel, Message
    echo "[{$message->channel->name}] {$message->author->username}#{$message->author->discriminator}: {$message->content}", PHP_EOL;

   $lfg_blocked_users = json_decode(file_get_contents("./blocked_users"), TRUE);

   // LFG Blocked users
   if ((begins($message->content, "!lfg") === TRUE) && (in_array($message->author->username, $lfg_blocked_users) === TRUE)) {
	//LOG: LFG Blocked User
	echo "LFG Request from {$message->author->username}#{$message->author->discriminator} in {$message->channel->name} blocked due to blocked user",PHP_EOL;
	$outmessage = "you are currently blocked from sending LFG messages";
	$message->reply($outmessage);
   } else {  // Not an LFG blocked user

   	// LFG Request
   	if ((begins($message->content, "!lfg") === TRUE) && (($message->channel->name == "pc-lfg" ) || ($message->channel->name == "ps4-lfg" ) || ($message->channel->name == "xbox-lfg" ))) {
 		// LOG: LFG Request
		echo "LFG Request from {$message->author->username}#{$message->author->discriminator} in {$message->channel->name}",PHP_EOL;
		$outmessage = "<@{$message->author->id}> is seeking players @here for:".PHP_EOL.substr($message->content, 5);
		$message->channel->sendMessage($outmessage);

   	}; // end LFG Request

   	// LFG Request in the wrong channel
   	if ((begins($message->content, "!lfg") === TRUE) && (contains($message->channel->name, "lfg") === FALSE)) {
 		// LOG: LFG Request in wrong channel
		echo "FAILED LFG Request from {$message->author->username}#{$message->author->discriminator} in {$message->channel->name}",PHP_EOL;
		$outmessage = "**<@{$message->author->id}>**: the `!lfg` command may only be run in the LFG channels.";
		$message->channel->sendMessage($outmessage);

   	}; // end failed LFG Request

   }; // end LFG Blocked User outer check

   // Clan Challenge leaderboard request
   if (begins($message->content, "clan companions") === TRUE) {
	// LOG: Clan challenge leaderboard requested
	clan_challenge($message);
   }; // end Clan Challenge

   // RNG Shaming request
   if (begins($message->content, 'shame') === TRUE) {

	// LOG: RNG Shaming request
	echo "COMMENCING SHAMING OF ".$shame_user."!", PHP_EOL;
	shame($message);

   } // end RNG Shaming


	// Current online players requests
	if ((begins($message->content, 'who\'s on') === TRUE) || (begins($message->content, 'whos on') === TRUE) || (begins($message->content, 'whose on') === TRUE)) {
		if ((contains($message->content, 'ps') === TRUE) || (contains($message->content, 'playstation') === TRUE)) {
			echo "Displaying PS4 Players online...". PHP_EOL;
			$outmessage = file_get_contents("https://solarian.org/clanonline2.php?p=2");
			$message->channel->sendMessage("<:sol:546429380419649536> \n".$outmessage);
		} elseif ( contains($message->content, 'xb') === TRUE ) {
                        echo "Displaying Xbox Players online...". PHP_EOL;
                        $outmessage = file_get_contents("https://solarian.org/clanonline2.php?p=1");
                        $message->channel->sendMessage("<:sol:546429380419649536> \n".$outmessage);
		} elseif ((contains($message->content, 'pc') === TRUE) || (contains($message->content, 'bat') === TRUE) || (contains($message->content, 'bn') === TRUE)) {
                        echo "Displaying PC Players online...". PHP_EOL;
                        $outmessage = file_get_contents("https://solarian.org/clanonline2.php?p=4");
                        $message->channel->sendMessage("<:sol:546429380419649536> \n".$outmessage);
		} else {
                        echo "Displaying all players online...". PHP_EOL;
                        $outmessage = file_get_contents("https://solarian.org/clanonline2.php");
                        $message->channel->sendMessage("<:sol:546429380419649536> \n".$outmessage);
		}
	} // end Current players request

// New User Registration Section

// Registration successful, select platform
if (contains($message->author->username, 'Charlemagne') === TRUE) {
	if ($message->channel->name == 'welcome') {
		if (contains($message->content, 'Successfully synced') === TRUE) {
			$outmessage = 'Now, just say `!ps`, `!pc`, or `!xbox` and you\'re all set.'; 
			$message->channel->sendMessage($outmessage);
		}; //end message check Successfully synced
	}; //endif channel is welcome
}; // endif Charlemagne

// "Listen to Soren" section

if (contains($message->author->username, 'soren42') === TRUE) {

	// Soren says
	if (begins($message->content, 'say ') === TRUE) {
		$message->channel->sendMessage(substr($message->content, 4));
		echo 'I said:',substr($message->content, 5), PHP_EOL;
	}; // end "say" clause

	// Block user from LFG commands
	if (begins($message->content, '!lfgblock ') === TRUE) {
		$user_to_block = substr($message->content, 10); 
		echo "Adding {$user_to_block} to LFG Block List", PHP_EOL;
		block_user($user_to_block, $lfg_blocked_users);
	}; // end add lfg block

	// Unblock user from LFG commands
	if (begins($message->content, '!lfgunblock ') === TRUE) {
		$user_to_unblock = substr($message->content, 12); 
		echo "Removing {$user_to_unblock} from LFG Block List", PHP_EOL;
		unblock_user($user_to_unblock, $lfg_blocked_users);
	}; // end remove lfg block

	// Display Discord construct
	if (begins($message->content, '!explain ') === TRUE) {
		$component = substr($message->content, 9);
		$message->channel->SendMessage(print_r($message->{$component}, TRUE));
		echo "Displaying variable {$component}", PHP_EOL;
	}; // end display Discord content

//WORKING SECTION
	if ((contains($message->content, 'where') === TRUE) && ((contains($message->content, 'xur') === TRUE) || (contains($message->content, 'xûr') === TRUE))) {
	       $message->channel->SendMessage('Looking for Xur');
		$pQueryHandle = curl_init();
    		$pQueryURL = 'https://discordapp.com/api/webhooks/543671519822217227/f6H4eqTqTliSHroVsLS4vxbCrRde5xopBbGOvEBqXtA9rrmvpFokvkXLdfjhTu7nnaFG';
		$json_body = '{
  "username": "Xûr Locator",
  "avatar_url": "https://wtfix.xyz/favicon.png",
  "embeds": [{
    "title": "Xûr is on Nessus",
    "description": ":xur584280891216494592: on the floating barge"
  },
    {
      "title": "Last Updated",
      "color": 14177041,
      "description", "Last-Updated: Fri 07 Jun 2019"
    }]
}';

    		curl_setopt($pQueryHandle, CURLOPT_URL,$pQueryURL);
    		curl_setopt($pQueryHandle, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($pQueryHandle, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json'
                        ]);
        curl_setopt($pQueryHandle, CURLOPT_POST, true);
	curl_setopt($pQueryHandle, CURLOPT_POSTFIELDS, $json_body);

        $pQueryReturn = curl_exec($pQueryHandle);
	curl_close($pQueryHandle);

	print_r($pQueryReturn . PHP_EOL );
	}; // end of find Xûr

}; // end "Listen to Soren" section

// Podcast commands
if ((contains($message->author->username, 'soren42') === TRUE) || (contains($message->author->username, 'justgeo42') === TRUE)) {

	if (begins($message->content, 'podcast start') === TRUE) {
		$message->channel->sendMessage("<:craig:561463031297736706> join SolNotes");
		echo "Craig was instructed to join SolNotes by ".$message->author->username, PHP_EOL;
	}; //end podcast start

	if (begins($message->content, 'podcast stop') === TRUE) {
		$message->channel->sendMessage("<:craig:561463031297736706> leave");
		echo "Craig was instructed to leave SolNotes by ".$message->author->username, PHP_EOL;
	}; //end podcast stop

}; // end Podcast commands

// "Listen for Friendly_Death_" section
if (contains($message->author->username, 'Friendly_Death_') === TRUE) {

	if (contains($message->content, 'fuck') === TRUE) {

		if ((contains($message->content, 'blueberr') === TRUE) || (contains($message->content, 'meta') === TRUE) || (contains($message->content, 'crucible') === TRUE) || (contains($message->content, 'pvp') === TRUE) || (contains($message->content, 'bagging') === TRUE) || (contains($message->content, 'shotgun') === TRUE)) {
			if (rand(0,100) < 10) {
				$outmessage="<@369505822331371530> https://thumbs.gfycat.com/FoolishAcceptableLeafhopper-size_restricted.gif";
				$message->channel->sendMessage($outmessage);
				echo "Taunting Friendly... ", PHP_EOL;
			} //endif 10% chance

		}; // endif massive list of Friendly-triggering terms

	}; // endif "fuck"

}; //end "Listen for Friendy_Death_" section

// Add more functionality/message checks here

   }); //end  on-$message function

}); //end Main Message loop


$discord->run();


?>
