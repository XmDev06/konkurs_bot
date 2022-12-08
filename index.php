<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'buttons.php';

use Telegram\Bot\Api;


$botToken = "5423468616:AAEklW24uXpHE6UelS6QKvHSnQ-9I96n1Yk";
// https://api.telegram.org/bot5423468616:AAEklW24uXpHE6UelS6QKvHSnQ-9I96n1Yk/setWebhook?url=https://c704-213-230-72-175.eu.ngrok.io/projects/konkurs/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new \TelegramBot\Api\Client($botToken);
$telegram = new Api($botToken);
session_start();

//functions
function isMember($chatId, array $channelsId)
{
    $bot = $GLOBALS['bot'];
    foreach ($channelsId as $channel) {
        $mstatus = $bot->getChatMember($channel, $chatId)->getStatus();
        if ($mstatus == 'creator' || $mstatus == 'member' || $mstatus == 'administrator') {
        } else {
            return false;
        }
    }
    return true;
}


$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($connection, $mainReplyButton, $bot) {
    try {
        $chatId = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();
        $isSubscribed = isMember($chatId, ["-1001882039432", "-1001671907228"]);
        $is_user = $connection->query("select * from users where chat_id='$chatId'")->num_rows;
        $text = $message->getText();
        $checkSubscribe = "checkSubscribe";

        if (count(explode(" ", $text)) == 2) {
            $referralId = explode(" ", $text)[1];
            if ($referralId != $chatId && $is_user == 0 && $isSubscribed) {
                $connection->query("insert into referral (user_id, referral_id) values ('$referralId','$chatId')");
            } else {
                $checkSubscribe= "checkSubscribe_$referralId";
            }
        }

        if ($isSubscribed) {
            $phone = $connection->query("select phone_number from users where chat_id='$chatId'")->fetch_assoc()['phone_number'];
            if ($phone != NULL) {
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, true, true);
                $name = $connection->query("select name from users where chat_id = '$chatId'")->fetch_assoc()['name'];
                $bot->sendMessage($chatId, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
            } else {
                if ($is_user == 0) {
                    $connection->query("insert into users(chat_id) values ('$chatId')");
                }
                $bot->sendMessage($chatId, "Ro'yxatdan o'tish uchun ism va familiyangizni kiriting\n(Na'muna: Akromjon Rahimov )", null, false, false, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => '']]], true, true));
                $connection->query("update users set status = 'fish' where chat_id = '$chatId'");
            }
        } else {
            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [['text' => '1️⃣-Kanal', 'url' => 'https://t.me/salomaaaat1']],
                [['text' => '2️⃣-Kanal', 'url' => 'https://t.me/salomaaaat2']],
                [['text' => 'A\'zo bo\'ldim✅', 'callback_data' => "$checkSubscribe"]],
            ]);
            $text = "Assalomu alaykum!\n\n“Yangi yil” tanlovi rasmiy botiga xush kelibsiz! Tanlovda ishtirok etish uchun quyidagi kanallarga obuna bo‘ling:";
            $bot->sendMessage($chatId, $text, null, false, null, $button);
        }


    } catch (Exception $exception) {
        //
    }
});


$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($connection, $mainReplyButton, $bot) {
    try {
        $chatId = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $firstname = $callbackquery->getMessage()->getChat()->getFirstName();
        $messageId = $callbackquery->getMessage()->getMessageId();
        $isSubscribed = (isMember($chatId, ["-1001882039432", "-1001671907228"]));
        $is_user = $connection->query("select * from users where chat_id='$chatId'")->num_rows;

        if (strpos($data,"checkSubscribe")!==false) {
            if ($isSubscribed) {
                $referralId = explode("_",$data)[1];
                if ($referralId != $chatId && $is_user == 0) {
                    $connection->query("insert into referral (user_id, referral_id) values ('$referralId','$chatId')");
                }

                $bot->deleteMessage($chatId, $messageId);

                $phone = $connection->query("select phone_number from users where chat_id='$chatId'")->fetch_assoc()['phone_number'];
                if ($phone != NULL) {
                    $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, true, true);
                    $name = $connection->query("select name from users where chat_id = '$chatId'")->fetch_assoc()['name'];
                    $bot->sendMessage($chatId, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
                } else {
                    if ($is_user == 0) {
                        $connection->query("insert into users(chat_id) values ('$chatId')");
                    }
                    $bot->sendMessage($chatId, "Ro'yxatdan o'tish uchun ism va familiyangizni kiriting\n(Na'muna: Akromjon Rahimov )", null, false, false, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => '']]], true, true));
                    $connection->query("update users set status = 'fish' where chat_id = '$chatId'");
                }

            } else {
                //
            }
        }

    } catch (Exception $exception) {
    }
});


$bot->on(static function () {
},
    static function (\TelegramBot\Api\Types\Update $update) use ($mainReplyButton, $connection, $bot) {

        try {
            $chat_id = $update->getMessage()->getChat()->getId();
            $text = $update->getMessage()->getText();
            $messageId = $update->getMessage()->getMessageId();
            $status = $connection->query("select status from users where chat_id='$chat_id'")->fetch_assoc()['status'];


            if ($status == 'fish') {
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => 'Telefon raqamni jo‘natish', 'request_contact' => true]]], true, true);
                $connection->query("update users set name = '$text' where chat_id = '$chat_id'");

                $bot->sendMessage($chat_id, "📲 Ro‘yxatdan o‘tishni yakunlash uchun “Telefon raqamni jo‘natish” tugmasini bosing.\n(Telefon raqamni o‘zingiz yozmang, faqat pastdagi tugmachani bosish orqali yuboring).", null, false, null, $button);

                $connection->query("update users set status = 'phone' where chat_id = '$chat_id'");
            }

            if ($status == "phone") {
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, true, true);
                $contact = $update->getMessage()->getContact()->getPhoneNumber();
                $connection->query("update users set phone_number = '$contact' where chat_id = '$chat_id'");
                $connection->query("update users set status = null where chat_id = '$chat_id'");
                $name = $connection->query("select name from users where chat_id = '$chat_id'")->fetch_assoc()['name'];
                $bot->sendMessage($chat_id, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
            }

            if ($text == '🗒 Tanlov shartlari') {
                $text2 = "🎓 TANLOV SHARTLARI
@kanal_link kanallari tomonidan tashkil etilgan MEGA KONKURSda 10 ta g‘oliblar to‘plagan ballariga qarab aniqlanadi.

❓ Ballar qanday to‘planadi?

✅ BOTda keltirilgan 3 ta kanalga obuna bo‘lgach, «Obuna bo‘ldim» tugmasini bosishingiz bilan, sizga maxsus referal link (havola) beriladi. O‘sha link orqali obuna bo‘lgan har bir inson uchun sizga +1 balldan berib boriladi. Qancha ko‘p ball yig‘sangiz, g‘olib bo‘lish imkoniyatingiz shuncha ortib boradi.

💠 10-dekabr kuni 23:59 da ball yig'ish to'xtatiladi va eng ko'p ball yig'gan 10 ishtirokchi pul yutuqlari bilan taqdirlanadi:

🥇 1-o'rin — 5 million so'm
🥈 2-o'rin — 2 million so'm
🥉 3-o'rin — 1 million so'm
🏅 4-o'rin — 500 ming so'm
🎖 5-o'rin — 400 ming so'm
🎗 6-7-o'rinlar — 300 ming so'm
🎗 8-9-o'rinlar — 200 ming so'm
🎗 10-o'rin — 100 ming so'm

💠 Tanlovda bonus sifatida 30 ball yig'gan barcha ishtirokchilarga bot orqali ID raqam beriladi random (tasodifiy raqamlar generatori) orqali 3 nafar g'olib aniqlanib ularga 100 ming so'mdan pul beriladi. (bu mukofot tepadagi 10ta o'ringa beriladigan yutuqlardan tashqari)

🙂 Faol bo‘ling va pul yutuqlaridan birini yutib oling. Barchaga omad!";

                $bot->sendMessage($chat_id, $text2);
            }

            if ($text == '☎ Murojaat') {
                $text1 = "Murojat uchun @humoyunmirzo_7979";
                $bot->sendMessage($chat_id, $text1);
            }


            if ($text == '♻Tanlovda ishtirok etish') {
                $bot->sendMessage($chat_id, "Ball toʼplash uchun quyida beriladigan referal (maxsus) link orqali odam taklif qilishingiz kerak boʼladi. Taklif etilgan har bir odam uchun 1 ball beriladi");

                $link = "https://t.me/konkurs_roobot?start=$chat_id";
                $textp = "MEGA KONKURSda qatnashing va pul mukofotlarini birini yutib oling. Tanlovda ishtirok etish uchun 👇\n\n $link";
                $photo = new CURLFile('photo.jpg');
                $bot->sendPhoto($chat_id, $photo, $textp);
            }


        } catch (Exception $exception) {
        }
    });


$bot->run();