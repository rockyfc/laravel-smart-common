<?php

namespace Smart\Common\Helpers;

/**
 * 将目录压缩成zip包
 */
class Zip
{
    /**
     * Zip a folder (include itself).
     * Usage:
     *   Zip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $sourcePath path of directory to be zip
     * @param string $outZipPath path of output zip file
     */
    public static function zipDir($sourcePath, $outZipPath)
    {
        $pathInfo = pathinfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new \ZipArchive();
        $z->open($outZipPath, \ZipArchive::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("{$parentPath}/"));
        $z->close();
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength number of text to be exclusived from the file path
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ('.' != $f && '..' != $f) {
                $filePath = "{$folder}/{$f}";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }
}
