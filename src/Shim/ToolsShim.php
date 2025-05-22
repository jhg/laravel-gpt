<?php

namespace MalteKuhr\LaravelGPT\Shim;

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

    public function parameters(): object
    {
        return (object) $this->parameters;
    }

    public function requiredParameters(): array
    {
        return $this->requiredParameters ?: array_keys((array) $this->parameters());
    }

    public function execute(...$parameters): mixed
    {
        return ($this->lambda)(...$parameters);
    }
}
