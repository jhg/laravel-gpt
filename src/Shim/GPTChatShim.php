<?php

namespace MalteKuhr\LaravelGPT\Shim;

use Illuminate\Support\Arr;
use Lenorix\Ai\AiText;
use Lenorix\Ai\Chat\CoreMessage;
use Lenorix\Ai\Chat\CoreMessageRole;
use Lenorix\Ai\Provider\OpenAi;
use MalteKuhr\LaravelGPT\Enums\ChatRole;
use MalteKuhr\LaravelGPT\GPTChat;
use MalteKuhr\LaravelGPT\Models\ChatMessage;

abstract class GPTChatShim
{
    /**
     * @var array<CoreMessage>
     */
    public array $messages = [];

    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }
    public function model(): string
    {
        return config('laravel-gpt.default_model');
    }

    public function addMessage(ChatMessage|CoreMessage|string $message): static
    {
        if (is_string($message)) {
            $message = new CoreMessage(
                role: CoreMessageRole::USER,
                content: $message
            );
        }

        if ($message instanceof ChatMessage) {
            $message = static::migrateMessage($message);
        }

        $this->messages[] = $message;

        return $this;
    }

    public static function migrateFrom(GPTChat $chat): static
    {
        $instance = new static();
        foreach ($chat->messages as $message) {
            if ($message instanceof ChatMessage) {
                $message = static::migrateMessage($message);
            }
            $instance->messages[] = $message;
        }

        return $instance;
    }

    public static function migrateMessage(ChatMessage $message): ?CoreMessage
    {
        $role = $message->role;

        if ($role === ChatRole::FUNCTION) {
            return null;
        } else {
            $role = CoreMessageRole::from($role->value);
        }

        return new CoreMessage(
            role: $role,
            content: $message->content,
        );
    }

    public function latestMessage(): CoreMessage|ChatMessage
    {
        $message = Arr::last($this->messages);

        if ($message instanceof ChatMessage) {
            $message = static::migrateMessage($message);
        }

        return $message;
    }

    abstract public function tools(): ?array;

    public function send(): self
    {
        $messages = [];
        foreach ($this->messages as $message) {
            if ($message instanceof ChatMessage) {
                $message = static::migrateMessage($message);
            }
            if ($message instanceof CoreMessage) {
                $messages[] = $message;
            }
        }

        $provider = new OpenAi(
            model: $this->model(),
            apiKey: config('laravel-gpt.api_key'),
            baseUrl: config('laravel-gpt.base_uri'),
        );

        $response = AiText::generate(
            $provider,
            tools: $this->tools(),
            messages: $messages,
            system: $this->systemMessage(),
            temperature: $this->temperature(),
            maxSteps: 5,
        );

        if ($response->messages && count($response->messages) > 0) {
            foreach ($response->messages as $message) {
                $this->addMessage($message);
            }
        }

        $this->messages = $messages;

        return $this;
    }
}
