<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'buttons.php';

use Telegram\Bot\Api;


$botToken = "5838764950:AAHAaWh50veRdSBKTUNYEfbNO3KIQp7MmfY";
// https://api.telegram.org/bot5838764950:AAHAaWh50veRdSBKTUNYEfbNO3KIQp7MmfY/setWebhook?url=https://github.com/humoyunmirzo0511/konkurs_bot/blob/main/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new \TelegramBot\Api\Client($botToken);
$telegram = new Api($botToken);

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


$bot->command('start', static function (\TelegramBot\Api\Types\Message $message) use ($majburiykanallar, $connection, $mainReplyButton, $bot) {
    try {
        $chatId = $message->getChat()->getId();
        $firstname = $message->getChat()->getFirstName();
        $isSubscribed = isMember($chatId, $majburiykanallar);
        $is_user = $connection->query("select * from users where chat_id='$chatId'")->num_rows;
        $text = $message->getText();
        $checkSubscribe = "checkSubscribe";

        if (count(explode(" ", $text)) == 2) {
            $phone = $message->getContact();
            var_dump($phone);
            $referralId = explode(" ", $text)[1];
            if ($referralId != $chatId && $is_user == 0 && $isSubscribed) {
                $connection->query("insert into referral (user_id, referral_id) values ('$referralId','$chatId')");
            } else {
                $checkSubscribe = "checkSubscribe_$referralId";
            }
        }

        if ($isSubscribed) {
            $phone = $connection->query("select phone_number from users where chat_id='$chatId'")->fetch_assoc()['phone_number'];
            if ($phone != NULL) {
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, false, true);
                $name = $connection->query("select name from users where chat_id = '$chatId'")->fetch_assoc()['name'];
                $bot->sendMessage($chatId, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
            } else {
                if ($is_user == 0) {
                    $connection->query("insert into users(chat_id) values ('$chatId')");
                }
                $bot->sendMessage($chatId, "Ro'yxatdan o'tish uchun ism va familiyangizni kiriting\n(Na'muna: Akromjon Rahimov )", null, false, false, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => '']]], false, true));
                $connection->query("update users set status = 'fish' where chat_id = '$chatId'");
            }
        } else {
            $button = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
                [['text' => '1️⃣-Kanal', 'url' => 'https://t.me/Stanford_school_ss']],
                [['text' => '2️⃣-Kanal', 'url' => 'https://t.me/JuraevLibraryMrMJ']],
                [['text' => 'A\'zo bo\'ldim✅', 'callback_data' => "$checkSubscribe"]],
            ]);
            $text = "Assalomu alaykum!\n\n“Yangi yil” tanlovi rasmiy botiga xush kelibsiz! Tanlovda ishtirok etish uchun quyidagi kanallarga obuna bo‘ling:";
            $bot->sendMessage($chatId, $text, null, false, null, $button);
        }


    } catch (Exception $exception) {
        //
    }
});


$bot->callbackQuery(static function (\TelegramBot\Api\Types\CallbackQuery $callbackquery) use ($majburiykanallar, $connection, $mainReplyButton, $bot) {
    try {
        $chatId = $callbackquery->getMessage()->getChat()->getId();
        $data = $callbackquery->getData();
        $firstname = $callbackquery->getMessage()->getChat()->getFirstName();
        $messageId = $callbackquery->getMessage()->getMessageId();
        $isSubscribed = (isMember($chatId, $majburiykanallar));
        $is_user = $connection->query("select * from users where chat_id='$chatId'")->num_rows;

        if (strpos($data, "checkSubscribe") !== false) {
            if ($isSubscribed) {
                $referralId = explode("_", $data)[1];
                if ($referralId != $chatId && $is_user == 0) {
                    $connection->query("insert into referral (user_id, referral_id) values ('$referralId','$chatId')");
                }

                $bot->deleteMessage($chatId, $messageId);

                $phone = $connection->query("select phone_number from users where chat_id='$chatId'")->fetch_assoc()['phone_number'];
                if ($phone != NULL) {
                    $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, false, true);
                    $name = $connection->query("select name from users where chat_id = '$chatId'")->fetch_assoc()['name'];
                    $bot->sendMessage($chatId, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
                } else {
                    if ($is_user == 0) {
                        $connection->query("insert into users(chat_id) values ('$chatId')");
                    }
                    $bot->sendMessage($chatId, "Ro'yxatdan o'tish uchun ism va familiyangizni kiriting\n(Na'muna: Akromjon Rahimov )", null, false, false, new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => '']]], false, true));
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
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => 'Telefon raqamni jo‘natish', 'request_contact' => true]]], false, true);
                $connection->query("update users set name = '$text' where chat_id = '$chat_id'");

                $bot->sendMessage($chat_id, "📲 Ro‘yxatdan o‘tishni yakunlash uchun “Telefon raqamni jo‘natish” tugmasini bosing.\n(Telefon raqamni o‘zingiz yozmang, faqat pastdagi tugmachani bosish orqali yuboring).", null, false, null, $button);

                $connection->query("update users set status = 'phone' where chat_id = '$chat_id'");
            }

            if ($status == "phone") {
                $button = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($mainReplyButton, false, true);
                if ($text) {
                    $bot->sendMessage($chat_id,"Telefon raqamni o‘zingiz yozmang, faqat pastdagi tugmachani bosish orqali yuboring");
                } else {
                    $contact = $update->getMessage()->getContact()->getPhoneNumber();
                    $connection->query("update users set phone_number = '$contact' where chat_id = '$chat_id'");
                    $connection->query("update users set status = null where chat_id = '$chat_id'");
                    $name = $connection->query("select name from users where chat_id = '$chat_id'")->fetch_assoc()['name'];
                    $bot->sendMessage($chat_id, "$name, siz konkursimiz a'zosiga aylandingiz!\n❗️Iltimos ortiqcha savollar ko'paymasligi uchun avval «Tanlov nizomi» tugmachasini bosib, yaxshilab tanishib chiqing.\nBoshlash uchun «♻Tanlovda ishtirok etish» tugmasini bosing 👇", null, false, false, $button);
                }
            }

            if ($text == '🗒 Tanlov shartlari') {
                $text2 = "🎓 TANLOV SHARTLARI
@Stanford_school_ss va @JuraevLibraryMrMJ kanallari tomonidan tashkil etilgan konkursda 5 ta g‘oliblar to‘plagan ballariga qarab aniqlanadi.

❓ Ballar qanday to‘planadi?

✅ BOTda keltirilgan 2 ta kanalga obuna bo‘lgach, «A'zo bo‘ldim» tugmasini bosishingiz bilan, sizga maxsus referal link (havola) beriladi. O‘sha link orqali obuna bo‘lgan har bir inson uchun sizga +1 balldan berib boriladi. Qancha ko‘p ball yig‘sangiz, g‘olib bo‘lish imkoniyatingiz shuncha ortib boradi.

💠 31-dekabr kuni 23:59 da ball yig'ish to'xtatiladi va eng ko'p ball yig'gan 5 ishtirokchi sovg'alar bilan taqdirlanadi:

🥇 1 - o’ringa smartwach
🥈 2 - o’ringa airpods
🥉 3 - o’ringa powerbank
🏅 4 - o’ringa Sherlock Holmes kitobi(ingliz tilida)
🎖 5 - o’ringa Al Kimyogar (ingliz tilida)

🙂 Faol bo‘ling va sovg'alardan birini yutib oling. Barchaga omad!";

                $bot->sendMessage($chat_id, $text2);
            }

            if ($text == '☎ Murojaat') {
                $text1 = "Konkurs haqidagi murojaatlar uchun @Stanford_English_School bilan bog'laning\n Bot haqidagi taklif va shikoyatlar uchun @humoyunmirzo_7979";
                $bot->sendMessage($chat_id, $text1);
            }

            if ($text == '♻Tanlovda ishtirok etish') {
                $bot->sendMessage($chat_id, "Ball toʼplash uchun quyida beriladigan referal (maxsus) link orqali odam taklif qilishingiz kerak boʼladi. Taklif etilgan har bir odam uchun 1 ball beriladi");

                $link = "https://t.me/Stanford_konkurs_bot?start=$chat_id";
                $textp = "Stanford school konkursida qatnashing va sovg'alardan birini yutib oling. Tanlovda ishtirok etish uchun 👇\n\n $link";
                $photo = new CURLFile('photo.jpg');
                $bot->sendPhoto($chat_id, $photo, $textp);
            }

            if ($text == "📊 Reyting") {
                $data = $connection->query("SELECT COUNT(referral_id), user_id FROM referral GROUP BY user_id ORDER BY COUNT(referral_id) DESC")->fetch_all();

                if (count($data) < 10) {
                    $data = array_splice($data, 0, 9);
                }
                $nimadir = "";
                foreach ($data as $key => $user) {
                    $key++;
                    $name = $connection->query("select name from users where chat_id = '$user[1]'")->fetch_assoc()['name'];
                    $nimadir .= "🏅 $key-oʼrin: $name • $user[0] ball\n";
                }

                $ball = $connection->query("select * from referral where user_id = $chat_id")->num_rows;
                $reyting = "📊 Botimizga eng koʼp doʼstini taklif qilib ball toʼplaganlar roʼyhati:\n\n$nimadir\n\n✅ Sizda $ball ball. Ko'proq do'stlaringizni taklif etib ballaringizni ko'paytiring!\n\n‼️ Nakrutka qilganlar konkursdan chetlashtiriladi. ‼️";
                $bot->sendMessage($chat_id, $reyting);
            }

        } catch (Exception $exception) {
        }
    });


$bot->run();
