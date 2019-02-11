<?php

namespace Asdfx\Phergie\Plugin\URLTitle;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Event\UserEventInterface;
use Zttp\Zttp;

class Plugin extends AbstractPlugin
{

    public function getSubscribedEvents()
    {
        return ['irc.received.privmsg' => 'handleCommand'];
    }

    public function handleCommand(UserEventInterface $event, EventQueueInterface $queue)
    {
        $eventParams = $event->getParams();
        $message_parts = explode(' ', $eventParams['text']);
        foreach ($message_parts as $message_part) {
            if (strpos($message_part, 'http://') === 0 || strpos($message_part, 'https://') ===0) {
                try {
                    $response = Zttp::get($message_part);
                } catch (\Throwable $exception) {
                    return;
                }

                $body = $response->body();
                preg_match("/<title>(.*?)<\/title>/", $body, $matches);

                if (count($matches) > 1) {
                    $title = $matches[1];
                }

                $shortUrl = $this->shorten($message_part);

                if (isset($title) && isset($shortUrl)) {
                    $queue->ircPrivmsg($event->getSource(), sprintf('^ %s → %s', $title, $shortUrl));
                    return;
                }

                if (isset($title)) {
                    $queue->ircPrivmsg($event->getSource(), sprintf('^ %s', $title));
                    return;
                }

                if (isset($shortUrl)) {
                    $queue->ircPrivmsg($event->getSource(), sprintf('→ %s', $shortUrl));
                    return;
                }
            }
        }
    }


    protected function shorten($url)
    {
        $response = Zttp::get(sprintf('https://is.gd/create.php?format=simple&url=%s', $url));
        if ($response->status() === 200) {
            return $response->body();
        }
    }
}
