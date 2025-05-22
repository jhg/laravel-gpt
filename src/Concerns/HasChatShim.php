<?php

namespace MalteKuhr\LaravelGPT\Concerns;

use Illuminate\Support\Arr;
use Lenorix\Ai\Chat\CoreMessage;
use MalteKuhr\LaravelGPT\Enums\ChatRole;
use MalteKuhr\LaravelGPT\Models\ChatMessage;
use MalteKuhr\LaravelGPT\Shim\GPTChatShim;

trait HasChatShim
{
    /**
     * @var array<CoreMessage>
     */
    public array $messages = [];

    /**
     * @param ChatMessage|CoreMessage|string $message
     * @return static
     */
    public function addMessage(ChatMessage|CoreMessage|string $message): static
    {
        if (is_string($message)) {
            $message = ChatMessage::from(
                role: ChatRole::USER,
                content: $message
            );
        }
        if ($message instanceof ChatMessage) {
            $message = GPTChatShim::migrateMessage($message);
        }

        $this->messages[] = $message;

        return $this;
    }

    /**
     * @return CoreMessage|ChatMessage
     */
    public function latestMessage(): CoreMessage|ChatMessage
    {
        return Arr::last($this->messages);
    }
}
