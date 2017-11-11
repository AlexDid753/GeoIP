<?php
namespace Shop;

/**
 * Класс для работы с GeoIP
 * @package Shop
 */
class GeoIP
{
    /**
     * Поиск информации об IP-адресе (страна, город, координаты)
     * @param null|string|array $IPs IP адрес для поиска или массив адресов
     * @return array Массив с информацие об IP адресе или набор таких массивов при множественном поиске
     */
    static function GetIPInfo($IPs = NULL)
    {
        if (is_null($IPs)) $IPs = $_SERVER['REMOTE_ADDR'];
        if (!is_array($IPs)) $IPs = array($IPs);

        $result = array();
        $stm = MySQL::GetDefaultInstance()->prepare("SELECT * FROM `GeoIP` LEFT JOIN `GeoIPCities` USING (`GeoIPCitiesID`) WHERE `LongIPFrom` <= ? AND `LongIPTo` >= ? LIMIT 1");
        foreach ($IPs as $IP){
            $long_ip = ip2long($IP);
            $stm->execute([$long_ip]);
            $result[$IP] = $stm->fetch();
        }

        if (count($result) == 1) $result = array_pop($result);
        return $result;
    }

    /**
     * Загружает блоки IP-адресов в базу данных
     * @param $CidrFile string Путь к файлу для загрузки
     * @return bool Результат загрузки
     */
    static function GEOBaseImportCidr($CidrFile)
    {
        $SQL = MySQL::GetDefaultInstance();

        if (!is_readable($CidrFile)) return FALSE;
        if (!($file = file($CidrFile))) return FALSE;

        $sql_stm_base = $SQL->prepare("INSERT INTO `GeoIP` (`LongIPFrom`, `LongIPTo`, `IPFrom`, `IPTo`, `Country`, `GeoIPCitiesID`) VALUES(?,?,?,?,?,?)");
        $SQL->query("TRUNCATE TABLE `GeoIP`");

        $SQL->beginTransaction();
        foreach ($file as $row) {
            $row = iconv('windows-1251', 'utf-8', $row);
            $r = explode("\t", $row);
            $range = explode("-", $r[2]);

            $sql_stm_base->execute([trim($r[0]), trim($r[1]), trim($range[0]), trim($range[1]), trim($r[3]), trim($r[4])]);
        }

        return $SQL->commit();
    }

    /**
     * Загружает города IP-адресов в базу данных
     * @param $CitiesFile string Путь к файлу для загрузки
     * @return bool Результат загрузки
     */
    static function GEOBaseImportCities($CitiesFile)
    {
        $SQL = MySQL::GetDefaultInstance();

        if (!is_readable($CitiesFile)) return FALSE;
        if (!($file = file($CitiesFile))) return FALSE;

        $sql_stm_cities = $SQL->prepare("INSERT INTO `GeoIPCities` (`GeoIPCitiesID`, `CityName`, `Region`, `District`, `Latitude`, `Longitude`) VALUES(?,?,?,?,?,?)");
        $SQL->query("TRUNCATE TABLE `GeoIPCities`");

        $SQL->beginTransaction();
        foreach ($file as $row) {
            $row = iconv('windows-1251', 'utf-8', trim($row));
            $r = explode("\t", $row);
            $sql_stm_cities->execute($r);
        }

        return $SQL->commit();
    }
}
