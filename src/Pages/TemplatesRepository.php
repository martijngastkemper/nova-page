<?php

namespace Whitecube\NovaPage\Pages;

use Route;
use Whitecube\NovaPage\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Arr;

class TemplatesRepository
{

    /**
     * The registered Templates
     *
     * @var array
     */
    protected $templates = [];

    /**
     * The registered pages
     *
     * @var array
     */
    protected $pages = [];

    /**
     * The loaded page templates
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * Fill the repository with registered routes
     *
     * @return void
     */
    public function registerRouteTemplates()
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if(!$route->template()) continue;
            $this->register('route', $route->getName(), $route->template());
        }
    }

    /**
     * Fill the repository with the options templates
     * 
     * @return void
     */
    public function registerOptionsTemplates()
    {
        $options = config('novapage.options');
        $allRoutes = Route::getRoutes()->getRoutes();

        foreach ($options as $template => $optionRoutes) {
            if (is_array($optionRoutes)) {
                $this->register('option', implode('+', $optionRoutes), $template);
            } else if (is_string($optionRoutes)) {
                $this->register('option', $optionRoutes, $template);
            }
        }
    }

    /**
     * Get all registered templates
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Get all registered pages
     *
     * @return array
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Get all registered options
     * 
     * @return array
     */
    public function getOptions() {
        return Arr::where($this->pages, function($page, $key) {
            return strpos($key, 'option.') === 0;
        });
    }

    /**
     * Get a registered page template by its key
     *
     * @param string $key
     * @return null|Whitecube\NovaPage\Pages\Template
     */
    public function getPageTemplate($key)
    {
        if(array_key_exists($key, $this->pages)) {
            return $this->templates[$this->pages[$key]];
        }
    }

    /**
     * Load a new Page Template Instance
     *
     * @param string $type
     * @param string $name
     * @param bool $throwOnMissing
     * @return Whitecube\NovaPage\Pages\Template
     */
    public function load($type, $name, $throwOnMissing)
    {
        $key = $type . '.' . $name;

        if(!($template = $this->getPageTemplate($key))) {
            throw new TemplateNotFoundException($this->pages[$key] ?? null, $key);
        }

        if(!isset($this->loaded[$key])) {
            if ($type === 'option') {
                $name = class_basename(get_class($template));
            }
            $this->loaded[$key] = $template->getNewTemplate($type, $key, $name, $throwOnMissing);
        }
        else {
            $this->loaded[$key]->load($throwOnMissing);
        }

        return $this->loaded[$key];
    }

    /**
     * Get a loaded page template by its key
     *
     * @param string $type
     * @param string $key
     * @return null|Whitecube\NovaPage\Pages\Template
     */
    public function getLoaded($type, $key)
    {
        foreach ($this->loaded as $identifier => $template) {
            if($identifier === $type . '.' . $key) return $template;
        }
    }

    /**
     * Add a page template
     *
     * @param string $type
     * @param string $key
     * @param string $template
     * @return Whitecube\NovaPage\Pages\Template
     */
    public function register($type, $key, $template)
    {
        if(!array_key_exists($template, $this->templates)) {
            $this->templates[$template] = new $template;
        }

        $this->pages[$type . '.' . $key] = $template;

        return $this->templates[$template];
    }
    
}