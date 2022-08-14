<?php

namespace App;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use RuntimeException;

class RequestHandler
{
    public static function handleGET(Request $request) : array
    {
        if (
            $request->has(['hub_mode','hub_challenge','hub_verify_token'])
            && $request->get('hub_mode') === 'subscribe'
            && $request->get('hub_verify_token') === Config::getVerifyToken()
        ) {
            return [$request->get('hub_challenge'), 200];
        }

        return ['', 403];
    }

    public static function handlePOST(Request $request) : array
    {
        try {
            self::HMACChecker($request);
            $jsonBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (($jsonBody['object'] ?? null) !== 'page') {
                throw new \RuntimeException('object is not page');
            }

            foreach ($jsonBody['entry'] ?? [] as $entry) {
                foreach ($entry['messaging'] ?? [] as $event) {
                    if (!isset($event['sender']['id'])) throw new RuntimeException('No sender id found');
                    if (isset($event['message']) && $event['message']) {
                        $messageToSend = "I do not understand, please say it correctly.\n\nsend 'help' for details";
                        if (isset($event['message']['text']) && $event['message']['text']) {
                            if (trim($event['message']['text']) === 'help') {
                                $messageToSend = "Find a word with least character.\n\n'?' for zero,one or more unknown character\n'-' for one or more unknown character.\n'_' for one unknown character.\n\nEx: a_t will return ant";
                            } else {
                                $matches = [];
                                if (preg_match('/([\w\-?]*):?(\d*)/', trim($event['message']['text']), $matches) && count($matches) === 3) {
                                    $result = WordRepository::search($matches[1], 10, $matches[2]);
                                    $messageToSend = implode("\n", $result);
                                }
                            }
                        }

                        if ($messageToSend === '') {
                            $messageToSend = 'Ummm... Empty result. Please try again';
                        }

                        self::sendMessage($event['sender']['id'], $messageToSend);
                    }
                }
            }
        } catch (\Exception $ex) {
            return ['', 422];
        }
        return ['', 200];
    }

    protected static function HMACChecker(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            throw new \RuntimeException('No hub signature found');
        }

        $signature = str_replace('sha256=', '', $signature);

        if (!hash_equals(hash_hmac('sha256', $request->getContent(), Config::getAppSecret()), $signature)) {
            throw new \RuntimeException('Signature error');
        }
    }

    private static function sendMessage($id, string $messageToSend)
    {
        $messageToSend = trim($messageToSend);
        if ($messageToSend === '') return false;

        $client = new PendingRequest(new Factory());
        $response =$client->send('POST', 'https://graph.facebook.com/v14.0/me/messages?access_token='.Config::getAccessToken(), [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'recipient' => ['id' => $id],
                'message' => ['text' => $messageToSend]
            ], JSON_THROW_ON_ERROR)
        ]);

        return $response->ok();
    }
}