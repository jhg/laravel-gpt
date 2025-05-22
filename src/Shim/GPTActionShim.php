<?php

namespace MalteKuhr\LaravelGPT\Shim;

use Lenorix\Ai\AiText;
use Lenorix\Ai\Chat\ToolFromLambda;
use Lenorix\Ai\Provider\OpenAi;
use MalteKuhr\LaravelGPT\GPTAction;

abstract class GPTActionShim extends GPTAction
{
    public mixed $result = null;

    public function  name(): string
    {
        return $this->functionName();
    }

    public function parameters(): array
    {
        return [];
    }

    public function requiredParameters(): array
    {
        return [];
    }

    public function send(string $message): mixed
    {
        $tool = new ToolFromLambda(
            function (...$params) {
                $this->result = ($this->function())(...$params);
                return $this->result;
            },
            name: $this->name(),
            description: $this->description(),
            parameters: $this->parameters(),
            requiredParameters: $this->requiredParameters(),
        );

        $provider = new OpenAi(
            model: $this->model(),
            apiKey: config('laravel-gpt.api_key'),
            baseUrl: config('laravel-gpt.base_uri'),
        );

        AiText::generate(
            $provider,
            tools: [
                $tool,
            ],
            prompt: $message,
            system: $this->systemMessage(),
            temperature:  $this->temperature(),
            maxSteps: 1,
            toolChoice: 'required',
        );

        return $this->result;
    }
}
