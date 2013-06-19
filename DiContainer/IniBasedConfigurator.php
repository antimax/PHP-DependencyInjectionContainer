<?php namespace DiContainer;

require_once 'IConfigurator.php';

class IniBasedConfigurator implements IConfigurator
{
    const CONFIG_GROUP_TYPE_RULE_MAPPING = 'type_rule_mapping';
    const CONFIG_GROUP_PARAMETER_VALUE_MAPPING = 'parameter_value_mapping';
    const CONFIG_GROUP_TYPE_MAPPING = 'type_mapping';

    const CONFIG_VALUE_PATTERN = 'abstract_type_name_pattern';
    const CONFIG_VALUE_REPLACEMENT = 'implementation_type_name_replacement';

    const CONFIG_VALUE_ABSTRACT_TYPE = 'abstract_type';
    const CONFIG_VALUE_IMPLEMENATION_TYPE = 'implementation_type';

    const CONFIG_VALUE_PARAMETER = 'parameter';
    const CONFIG_VALUE_VALUE = 'value';

    private $config = null;

    public function __construct($pathToConfigFile)
    {
        if (!is_string($pathToConfigFile) || empty($pathToConfigFile)) {
            throw new \InvalidArgumentException('$pathToConfigFile should be non-empty string');
        }

        if (($config = parse_ini_file($pathToConfigFile, true, INI_SCANNER_RAW)) === false || empty($config)) {
            throw new \InvalidArgumentException("Failed to parse INI file '{$pathToConfigFile}'");
        }

        $this->config = $config;
    }

    public function Configure(Container $container)
    {
        if (array_key_exists(self::CONFIG_GROUP_TYPE_RULE_MAPPING, $this->config)) {
            $group = $this->config[self::CONFIG_GROUP_TYPE_RULE_MAPPING];

            if (array_key_exists(self::CONFIG_VALUE_PATTERN, $group)
                && array_key_exists(self::CONFIG_VALUE_REPLACEMENT, $group)
                && count($group[self::CONFIG_VALUE_PATTERN]) == count($group[self::CONFIG_VALUE_REPLACEMENT])
            ) {
                for ($i = 0; $i < count($group[self::CONFIG_VALUE_PATTERN]); $i++) {
                    $container->RegisterTypeMappingRule($group[self::CONFIG_VALUE_PATTERN][$i], $group[self::CONFIG_VALUE_REPLACEMENT][$i]);
                }
            }
        }

        if (array_key_exists(self::CONFIG_GROUP_TYPE_MAPPING, $this->config)) {
            $group = $this->config[self::CONFIG_GROUP_TYPE_MAPPING];

            if (array_key_exists(self::CONFIG_VALUE_ABSTRACT_TYPE, $group)
                && array_key_exists(self::CONFIG_VALUE_IMPLEMENATION_TYPE, $group)
                && count($group[self::CONFIG_VALUE_ABSTRACT_TYPE]) == count($group[self::CONFIG_VALUE_IMPLEMENATION_TYPE])
            ) {
                for ($i = 0; $i < count($group[self::CONFIG_VALUE_ABSTRACT_TYPE]); $i++) {
                    $container->RegisterType($group[self::CONFIG_VALUE_ABSTRACT_TYPE][$i], $group[self::CONFIG_VALUE_IMPLEMENATION_TYPE][$i]);
                }
            }
        }

        if (array_key_exists(self::CONFIG_GROUP_PARAMETER_VALUE_MAPPING, $this->config)) {
            $group = $this->config[self::CONFIG_GROUP_PARAMETER_VALUE_MAPPING];

            if (array_key_exists(self::CONFIG_VALUE_PARAMETER, $group)
                && array_key_exists(self::CONFIG_VALUE_VALUE, $group)
                && count($group[self::CONFIG_VALUE_PARAMETER]) == count($group[self::CONFIG_VALUE_VALUE])
            ) {
                for ($i = 0; $i < count($group[self::CONFIG_VALUE_PARAMETER]); $i++) {
                    $container->RegisterParameterValue($group[self::CONFIG_VALUE_PARAMETER][$i], $group[self::CONFIG_VALUE_VALUE][$i]);
                }
            }
        }
    }
}