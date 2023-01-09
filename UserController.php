<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    function test()
    {
        $url = 'https://cdn.jsdelivr.net/gh/apilayer/restcountries@3dc0fb110cd97bce9ddf27b3e8e1f7fbe115dc3c/src/main/resources/countriesV2.json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15"));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        $JsonRequest = json_decode($result, true);
        $rawData = [];
        $neededDatas = [];
        $populationLimit = 0;
        $currencyUnique = [];
        $currencyNonUnique = [];
        $currencyFilteredCountries = [];
        $finalCountryList = [];
        foreach ($JsonRequest as $countryData) {
            if ($countryData['population'] >= $populationLimit) {

                $rawData[$countryData['name']] = [
                    "name" => $countryData['name'],
                    "population" => $countryData['population'],
                    "currencies" => $countryData['currencies'],
                    "latlng" => $countryData['latlng']
                ];
                $neededDatas[$countryData['name']] = $countryData['population'];
                foreach ($countryData['currencies'] as $currency) {
                    if (array_key_exists($currency["code"], $currencyUnique)) {
                        array_push($currencyNonUnique, $currency["code"]);
                        unset($currencyUnique[$currency["code"]]);
                        continue;
                    }
                    if (in_array($currency["code"], $currencyNonUnique)) {
                        unset($currencyUnique[$currency["code"]]);
                        continue;
                    }
                    $currencyUnique[$currency["code"]] = $countryData['name'];
                }
            }
        }
        foreach ($currencyUnique as $contryName) {
            if (array_key_exists($contryName, $neededDatas)) {
                $currencyFilteredCountries[$contryName] = $neededDatas[$contryName];
            }
        }
        // arsort($currencyFilteredCountries);
        $currencyFilteredCountries = array_keys(array_slice($currencyFilteredCountries, 0, 20));

        $totalDistance = 0;
        for ($i = 0; $i < count($currencyFilteredCountries) - 1; $i++) {
            for ($j = $i+1; $j < count($currencyFilteredCountries); $j++) {
                printf($currencyFilteredCountries[$i] . " -> " . $currencyFilteredCountries[$j] . "<br>");
                $totalDistance += $this->latLongToKM(
                    $rawData[$currencyFilteredCountries[$i]]["latlng"][0]??0,
                    $rawData[$currencyFilteredCountries[$i]]["latlng"][1]??0,
                    $rawData[$currencyFilteredCountries[$j]]["latlng"][1]??0,
                    $rawData[$currencyFilteredCountries[$j]]["latlng"][1]??0
                );
            }
        }

        dd(round($totalDistance,2));
    }

    function latLongToKM(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo
    ) {
        $long1 = deg2rad(round($longitudeFrom,2));
        $long2 = deg2rad(round($longitudeTo,2));
        $lat1 = deg2rad(round($latitudeFrom,2));
        $lat2 = deg2rad(round($latitudeTo,2));

        //Haversine Formula
        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;

        $val = pow(sin($dlati / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlong / 2), 2);

        $res = 2 * asin(sqrt($val));

        $radius = 6371;

        return ($res * $radius);
    }
}
