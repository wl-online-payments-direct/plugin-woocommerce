<?php

namespace Composer\Installers;

class HuradInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('plugin' => 'plugins/{$name}/', 'theme' => 'plugins/{$name}/');
    /**
     * Format package name to CamelCase
     */
    public function inflectPackageVars(array $vars): array
    {
        $nameParts = explode('/', $vars['name']);
        foreach ($nameParts as &$value) {
            $value = strtolower($this->pregReplace('/(?<=\w)([A-Z])/', 'Syde\Vendor\Worldline\_\1', $value));
            $value = str_replace(array('-', '_'), ' ', $value);
            $value = str_replace(' ', '', ucwords($value));
        }
        $vars['name'] = implode('/', $nameParts);
        return $vars;
    }
}
