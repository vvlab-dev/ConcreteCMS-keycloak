<?php

namespace KeycloakAuth\Claim\Conversion;

use Concrete\Core\Application\Application;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ConverterFactory
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $converterCreator;

    /**
     * @var \KeycloakAuth\Claim\Conversion\Converter[]
     */
    private $converters = [];

    public function __construct(Filesystem $filesystem, Application $converterCreator)
    {
        $this->filesystem = $filesystem;
        $this->converterCreator = $converterCreator;
    }

    /**
     * @return $this
     */
    public function registerConverter(Converter $converter)
    {
        $this->converters[] = $converter;

        return $this;
    }

    /**
     * @return $this
     */
    public function registerDefaultConverters()
    {
        $dir = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', __DIR__), '/') . '/Converters';
        if (!$this->filesystem->isDirectory($dir)) {
            throw new RuntimeException(t('Failed to find the directory %s', "'{$dir}'"));
        }
        foreach ($this->filesystem->allFiles($dir) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $className = __NAMESPACE__ . '\\Converters\\' . $file->getBasename('.php');
            if (!class_exists($className)) {
                continue;
            }
            if (!is_a($className, Converter::class, true)) {
                continue;
            }
            $this->registerConverter($this->converterCreator->make($className));
        }

        return $this;
    }

    /**
     * @return \KeycloakAuth\Claim\Conversion\Converter[]
     */
    public function getRegisteredConverters()
    {
        return $this->converters;
    }

    /**
     * @param string $handle
     *
     * @return \KeycloakAuth\Claim\Conversion\Converter[]
     */
    public function getConvertersForAttributeType($handle)
    {
        return array_values(
            array_filter(
                $this->getRegisteredConverters(),
                static function (Converter $converter) use ($handle) {
                    return in_array($handle, $converter->getSupportedAttributeTypes());
                }
            )
        );
    }
}
