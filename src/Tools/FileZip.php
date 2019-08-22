<?php

namespace Amulet\Tools;

// +----------------------------------------------------------------------
// | Date 03-21
// +----------------------------------------------------------------------
// | Author: fushengfu <shengfu8161980541@qq.com>
// +----------------------------------------------------------------------

class FileZip
{

	/**
	 * 压缩文件
	 * @param $files array 文件列表
	 * @param $fileName string 压缩文件名
	 */
	public static function compression($files, $zipname, $download = true)
	{
		$zip = new \ZipArchive;
		if ($zip->open($zipname, \ZipArchive::CREATE) === true) {

			$dirPath = str_replace('.'.strchr($zipname, '.'), '/', $zipname);
			foreach ($files as $file) {
			 	$arr = explode('/', $file);

			 	if (!copy($file, $dirPath.$arr[count($arr) - 1])) {
			 		throw new Exception("failed to copy $file...", 1);
			 	}

				$zip->addFile($dirPath.$arr[count($arr) - 1]);
			}
			$zip->close();

			if ($download) {
				///Then download the zipped file.
				header('Content-Type: application/zip');
				header('Content-disposition: attachment; filename='.$zipname);
				header('Content-Length: ' . filesize($zipname));
				readfile($zipname);
			} else {
				return true;
			}
			
		} else {
			return false;
		}
	}

	/**
	 * 解压缩文件
	 * @param $fileName string 压缩文件名
	 */
	public static function extract($zipname, $path)
	{
		$zip = new ZipArchive;
		if ($zip->open($zipname) === true) {
			$zip->extractTo($path);
			$zip->close();
			return true;
		} else {
			return false;
		}
	}
}
