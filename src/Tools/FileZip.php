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
		// $zipname = 'enter_any_name_for_the_zipped_file.zip';
		$zip = new ZipArchive;
		$zip->open($zipname, ZipArchive::CREATE);
		 foreach ($files as $file) {
		   $zip->addFile($file);
		 }
		$zip->close();

		if ($download) {
			///Then download the zipped file.
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename='.$zipname);
			header('Content-Length: ' . filesize($zipname));
			readfile($zipname);
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
