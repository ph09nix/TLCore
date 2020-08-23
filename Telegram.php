<?php
date_default_timezone_set("Asia/Tehran");
namespace TLCore;
{
    class Telegram
    {

        public $ADAPIKey;
        public $ADUserid;
        public $ADChannel = "";

        public $MSChatID;
        public $MSChatUSERNAME;
        public $MSId;
        public $MSMessageContent;
        public $ISFile=false;
        public $MSFileID;
        public $MSFileName;
        public $MSFileMim;
        public $MSFileSize;

        public function checkData()
        {
            if (strlen($this->ADAPIKey) < 45) {
                return false;
            }
            if (strlen($this->ADUserid) < 3) {
                return false;
            }
            return true;
        }

        public function tryParse($data)
        {
            if (isset($data->message)) {
                if (isset($data->message->chat->id)) {
                    $this->MSChatID = $data->message->chat->id;
                    $this->MSChatUSERNAME = $data->message->chat->username;
                }
                $this->MSId = $data->message->message_id;
                if (isset($data->message->text)) {
                    $this->MSMessageContent = $data->message->text;
                } else if (isset($data->message->document)) {
                    $this->ISFile=true;
                    $this->MSFileID = $data->message->document->file_id;
                    $this->MSFileMim = $data->message->document->mime_type;
                    $this->MSFileSize = $data->message->document->file_size;
                    $this->MSFileName = $data->message->document->file_name;
                }
            } else if (isset($data->callback_query)) {
                $this->MSChatID = $data->callback_query->message->chat->id;
                $this->MSChatUSERNAME = $data->callback_query->message->chat->username;
                $this->MSMessageContent = $data->callback_query->data;
                $this->MSId = $data->callback_query->message->message_id;
            }
        }

        public function sendmessage($chatID, $text, $message_id = null, $keyboard = null, $noti = false, $parsemod = "html")
        {
            $this->sendAction($chatID, "typing");
            return $this->BotInstance('sendMessage', [
                'chat_id' => $chatID,
                'text' => $text,
                'disable_web_page_preview' => TRUE,
                'parse_mode' => $parsemod,
                'reply_to_message_id' => $message_id,
                'reply_markup' => $keyboard,
                'resize_keyboard' => true,
                'disable_notification' => $noti,
            ]);
        }

        private function sendAction($chat_id, $action)
        {
            $this->BotInstance('sendchataction', [
                'chat_id' => $chat_id,
                'action' => $action,
            ]);
            sleep(1);
        }

        #region UsageFunctions
        private function BotInstance($method, $datas = [])
        {
            $url = "https://api.telegram.org/bot" . $this->ADAPIKey . "/" . $method;
            $ch = curl_init();
            curl_setopt_array($ch,
                [
                    CURLOPT_URL => $url,
                    CURLOPT_POSTFIELDS => $datas,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_RETURNTRANSFER => true,
                ]);
            $res = curl_exec($ch);
            if (curl_error($ch)) {
                return "Error";
            } else {
                if (strlen($res) > 0) {
                    return json_decode($res);
                } else {
                    return "Error";
                }
            }
        }

        public function deletemessage($chatID, $msg_id)
        {
            $this->BotInstance('deleteMessage', [
                'chat_id' => $chatID,
                'message_id' => $msg_id,
            ]);
        }

        public function editmessage($chatID, $text, $message_id, $keybaord = null)
        {
            $this->sendAction($chatID, "typing");
            $this->BotInstance('editMessageText', [
                'chat_id' => $chatID,
                'text' => $text,
                'parse_mode' => 'HTML',
                'message_id' => $message_id,
                'reply_markup' => $keybaord,
            ]);
        }

        public function sendFile($chatID, $document, $caption=null)
        {
            $this->sendAction($chatID, "upload_document");
            $this->BotInstance('sendDocument', [
                'chat_id' => $chatID,
                'document' => new \CURLFile($document),
                'caption' => $caption,
            ]);
        }

        public function downloadFile($fileId, $filenName)
        {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.telegram.org/bot" . $this->ADAPIKey . "/getFile",
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => "file_id=$fileId",
            ]);
            $res = curl_exec($curl);
            $data = json_decode($res);
            $dataPath = $data->result->file_path;
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.telegram.org/file/bot" . $this->ADAPIKey . "/" . $dataPath,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $res = curl_exec($curl);
            file_put_contents($filenName, $res);
            return "done";
        }

        public function sendFileID($chatID, $document, $caption)
        {
            $this->sendAction($chatID, "upload_document");
            $this->BotInstance('sendDocument', [
                'chat_id' => $chatID,
                'document' => $document,
                'caption' => $caption,
            ]);
        }

        public function is_in_channel($channelid, $chatid)
        {
            $res = ($this->BotInstance("getChatMember", [
                "chat_id" => $channelid,
                "user_id" => $chatid
            ]));
            switch ($res->result->status) {
                case "creator":
                    return true;
                case "administrator":
                    return true;
                case "member":
                    return true;
                default:
                    return false;
            }
        }

        public function msgContains($textData, $deep_command = false, $setcustom = "")
        {
            $target = $this->MSMessageContent;
            if (strlen($setcustom) > 0) {
                $target = $setcustom;
            }
            $finalRegex = '';
            $len = strlen($textData) - 1;
            for ($i = 0; $i <= $len; $i++) {
                $var = strtolower(strval($textData[$i]));
                $var2 = strtoupper($var);
                $finalRegex .= "[$var2$var]";
            }
            $finalRegex = "%$finalRegex%";
            if (preg_match($finalRegex, $target)) {
                if ($deep_command) {
                    return substr(preg_replace($finalRegex . " ", "", $target), 1);
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
        #endregion
    }
}
?>