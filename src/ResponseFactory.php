<?php

namespace Inertia;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Response as BaseResponse;

class ResponseFactory
{
    use Macroable;

    /** @var string */
    protected $rootView = 'app';

    /** @var array */
    protected $sharedProps = [];

    /** @var Closure|string|null */
    protected $version;

    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * @param string|array|Arrayable $key
     * @param mixed                  $value
     */
    public function share($key, $value = null): void
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } elseif ($key instanceof Arrayable) {
            $this->sharedProps = array_merge($this->sharedProps, $key->toArray());
        } else {
            Arr::set($this->sharedProps, $key, $value);
        }
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getShared(string $key = null, $default = null)
    {
        if ($key) {
            return Arr::get($this->sharedProps, $key, $default);
        }

        return $this->sharedProps;
    }

    public function flushShared(): void
    {
        $this->sharedProps = [];
    }

    /**
     * @param Closure|string|null $version
     */
    public function version($version): void
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        $version = $this->version instanceof Closure
            ? App::call($this->version)
            : $this->version;

        return (string) $version;
    }

    public function lazy(callable $callback): LazyProp
    {
        return new LazyProp($callback);
    }

    /**
     * @param array|Arrayable $props
     */
    public function render(string $component, $props = []): Response
    {
        if ($props instanceof Arrayable) {
            $props = $props->toArray();
        }

        return new Response(
            $component,
            array_merge($this->sharedProps, $props),
            $this->rootView,
            $this->getVersion()
        );
    }

    /**
     * @param string|RedirectResponse $url
     */
    public function location($url): \Symfony\Component\HttpFoundation\Response
    {
        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if (Request::inertia()) {
            return BaseResponse::make('', 409, ['X-Inertia-Location' => $url]);
        }

        return new RedirectResponse($url);
    }
}
