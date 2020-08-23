<?php

use TLCore\Telegram;

{
    #loading framework
    require_once "Telegram.php";
    # creating new instnace of TlCore\Telegram class
    $bot = new Telegram();
    # set needed information
    $bot->ADAPIKey = "BOT_TOKEN";
    $bot->ADUserid = 12345;
    $bot->ADChannel = "@channel_username"; #optional
    # gathering incomming data to communicate with telegram webhooks
    $data = file_get_contents("php://input");
    $data = json_decode($data, false);
    # parsing information from incomming webhook
    $bot->tryParse($data);
    # robot conditions and ...
    if ($bot->msgContains("/start")) {
        $bot->sendmessage($bot->MSChatID, "hello, ihave been started", $bot->MSId);
    } else if ($bot->msgContains("/start")) {
        $dp = $bot->msgContains("/start", true);
        $bot->sendmessage($bot->MSChatID, "this is a deep command , query is `$dp`");
    } else {
        # close script
        exit();
    }
}
?>