#!/usr/bin/php
<?
// Запускается каждый день в 2:00
// Скачивает файлы с базой GeoIP и загружает их в локальную базу данных
// post_info_analize.php --config

require_once(__DIR__.'/incl/functions.inc.php');
require_once(__DIR__.'/../incl/klshop_autoloader.php');

use KLShop\KLConfig, KLShop\KLGeoIP, KLShop\KLMySQL;

$configs = GetParamConfigs();
if (count($configs) == 0) die("   Ошибка: Не задан параметр --config\n");

foreach ($configs as $cfg) {
    $GLOBALS['\\KLShop\\DefaultConfig'] = KLConfig::LoadConfig($cfg);
    if (!is_array($GLOBALS['\\KLShop\\DefaultConfig'])) {
        print "   Ошибка: Не найден конфигурационный файл $cfg.\n";
        continue;
    }

    print "\n";
    print "--- Обработка конфигурационного файла $cfg (" . date('d.m.Y H:i:s') . ")\n";
    print "--- Название магазина: {$GLOBALS['\\KLShop\\DefaultConfig']['ShopName']}\n";
    print "--- Загрузка сведений в базу GeoIP\n\n";

    unset($GLOBALS['\\KLShop\\DefaultSQLObject'], $GLOBALS['\\KLShop\\DefaultModXObject']);
    $cfg = KLConfig::GetDefaultConfig();

    file_put_contents(rtrim($cfg['Path']['Temp'],'/').'/geo_files.zip', file_get_contents('http://ipgeobase.ru/files/db/Main/geo_files.zip'));

    $zip = new ZipArchive;
    if ($zip->open(rtrim($cfg['Path']['Temp'],'/').'/geo_files.zip') === TRUE) {
        $zip->extractTo($cfg['Path']['Temp'], array('cities.txt', 'cidr_optim.txt'));
        $zip->close();
        print_r('Download, unzip IP base finished');
    } else {
        print_r('Download, unzip IP base error');
    }


    KLGeoIP::GEOBaseImportCidr(rtrim($cfg['Path']['Temp'],'/').'/cidr_optim.txt');
    KLGeoIP::GEOBaseImportCities(rtrim($cfg['Path']['Temp'],'/').'/cities.txt');

    
    unlink(rtrim($cfg['Path']['Temp'],'/').'/cities.txt');
    unlink(rtrim($cfg['Path']['Temp'],'/').'/cidr_optim.txt');
    unlink(rtrim($cfg['Path']['Temp'],'/').'/geo_files.zip');
}
