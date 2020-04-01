<?php

use InfluxDB\Database;
use InfluxDB\Point;

define('RecordCount', 500);

require(__DIR__ . '/vendor/autoload.php');

$client = new InfluxDB\Client('influx.smarthome', 8086);
$database = $client->selectDB('corona');

function import(Database $database)
{
    $offset = 0;
    do {
        printf("Import start %s - %d offset %d … \n", date(DATE_RSS), RecordCount, $offset);
        $url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_COVID19/FeatureServer/0/query?' .
        http_build_query([
            'where' => "Meldedatum >= CURRENT_TIMESTAMP - INTERVAL '4' DAY",
            'outFields' => '*',
            'outSR' => 4326,
            'f' => 'json',
            'resultRecordCount' => RecordCount,
            'resultOffset' => $offset
        ]);
        echo "… URL: ${url}\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $resp = curl_exec($ch);
        if (200 != curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            die('Can not get data: ' . curl_error($ch));
        }

        $jdata = json_decode($resp, true);
        if (!is_array($jdata)) {
            die('Broken response: ' . json_last_error());
        }

        $points = [];
        foreach ($jdata['features'] as $feature) {
            $attr = $feature['attributes'];

            printf(
                "%s - %s,%s,%s,%s - %d / %d\n",
                date('Y-m-d', $attr['Meldedatum'] / 1000),
                $attr['Bundesland'],
                $attr['Landkreis'],
                $attr['Altersgruppe'],
                $attr['Geschlecht'],
                $attr['AnzahlFall'],
                $attr['AnzahlTodesfall']
            );

            $points[] = new Point(
                'rki',
                $attr['AnzahlFall'],
                [
                    "Bundesland" => $attr['Bundesland'],
                    "Landkreis" => $attr['Landkreis'],
                    "Altersgruppe" => $attr['Altersgruppe'],
                    "Geschlecht" => $attr['Geschlecht']
                ],
                [
                    "AnzahlFall" => $attr['AnzahlFall'],
                    "AnzahlTodesfall" => $attr['AnzahlTodesfall']
                ],
                $attr['Meldedatum']
            );
        }

        // we are writing unix timestamps, which have a second precision
        if (!$database->writePoints($points, Database::PRECISION_MILLISECONDS)) {
            die('Error writing to influxdb!');
        }

        $offset += RecordCount;
        sleep(1);
    } while (true === $jdata['exceededTransferLimit'] || count($jdata['features']) >= RecordCount);

    echo "done.\n";
}


if ($argv[1] == 'daemon') {
    while (true) {
        import($database);
        sleep(1800);
    }
} else {
    import($database);
}
