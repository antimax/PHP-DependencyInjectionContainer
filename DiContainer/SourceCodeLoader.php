<?php namespace DiContainer;

class SourceCodeLoader
{
    static function Load(array $directories)
    {
        foreach ($directories as $dir => $recursive) {
            $directoryIterator = empty($recursive) ?
                new \DirectoryIterator($dir) :
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

            /** @var $file \DirectoryIterator */
            foreach (new \RegexIterator($directoryIterator, '~\.php~i') as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                require_once $file->getPathname();
            }
        }
    }
}