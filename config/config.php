<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */


function environmentVariablesParseEnvValue($settingEnvName, $isArray = false)
{
    $envValue = getenv($settingEnvName);
    if ($envValue === false) { return false; }

    // String
    if ($isArray === false) { return $envValue; }

    // Array
    $arrayValue = array_map(function($item) {
        return rtrim($item);
    }, explode("\n", $envValue));

    // Filter empty strings that creep into env line separated arrays
    return array_filter($arrayValue, function ($item) { return $item !== ""; });
}

return array(
    'Piwik\Config' => DI\decorate(function ($previous, \Psr\Container\ContainerInterface $c) {
        $settings = $c->get(\Piwik\Application\Kernel\GlobalSettingsProvider::class);

        $ini = $settings->getIniFileChain();
        $all = $ini->getAll();
        foreach ($all as $category => $settings) {
            $categoryEnvName = 'MATOMO_' . strtoupper($category);

            foreach ($settings as $settingName => $value) {
                $general = $previous->$category;

                // String
                $settingEnvName  = $categoryEnvName . '_' .strtoupper($settingName);
                $envValue = environmentVariablesParseEnvValue($settingEnvName);
                if ($envValue !== false) {
                    $general[$settingName] = $envValue;
                    $previous->$category = $general;
                    // Do not try to load as an array if we already found it as string
                    continue;
                }

                // Array
                $settingEnvName  = $categoryEnvName . '_' . strtoupper($settingName) . '_ARRAY';
                $envValue = environmentVariablesParseEnvValue($settingEnvName, true);
                if ($envValue !== false) {
                    $general[$settingName] = $envValue;
                    $previous->$category = $general;
                }
            }
        }

        return $previous;
    }),
);
