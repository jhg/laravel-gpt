<?php

namespace MalteKuhr\LaravelGPT\Shim;

use Illuminate\Support\Facades\Log;
use Lenorix\Ai\Chat\CoreTool;
use MalteKuhr\LaravelGPT\GPTFunction;

class ToolsShim extends CoreTool
{
    protected \Closure $lambda;
    protected string $name;
    protected string $description;
    protected array $parameters;
    protected ?array $requiredParameters;

    public function __construct(GPTFunction $function, array $parameters, ?array $requiredParameters = null)
    {
        $this->lambda = $function->function();
        $this->name = $function->name();
        $this->description = $function->description();
        $this->parameters = $parameters;
        $this->requiredParameters = $requiredParameters;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function requiredParameters(): array
    {
        return $this->requiredParameters ?: array_keys($this->parameters());
    }

    public function run(...$parameters): mixed
    {
        Log::debug('Tool called by LLM', [
            'tool' => $this->name(),
            'parameters' => $parameters,
        ]);
        return ($this->lambda)(...$parameters);
    }
}
