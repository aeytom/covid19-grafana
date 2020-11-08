<?php

use InfluxDB\Database;
use InfluxDB\Point;

define('RecordCount', 500);

require(__DIR__ . '/vendor/autoload.php');

$client = new InfluxDB\Client(getenv('INFLUXDB_HOST'), getenv('INFLUXDB_PORT'), getenv('INFLUXDB_USER'), getenv('INFLUXDB_PASSWORD'));
$database = $client->selectDB(getenv('INFLUXDB_DATABASE'));

$blDiviMap = [
    "BADEN_WUERTTEMBERG" => 'Baden-Württemberg',
    "BAYERN" => 'Bayern',
    "BERLIN" => 'Berlin',
    "BRANDENBURG" => 'Brandenburg',
    "BREMEN" => 'Bremen',
    "HAMBURG" => 'Hamburg',
    "HESSEN" => 'Hessen',
    "MECKLENBURG_VORPOMMERN" => 'Mecklenburg-Vorpommern',
    "NIEDERSACHSEN" => 'Niedersachsen',
    "NORDRHEIN_WESTFALEN" => 'Nordrhein-Westfalen',
    "RHEINLAND_PFALZ" => 'Rheinland-Pfalz',
    "SAARLAND" => 'Saarland',
    "SACHSEN_ANHALT" => 'Sachsen-Anhalt',
    "SACHSEN" => 'Sachsen',
    "SCHLESWIG_HOLSTEIN" => 'Schleswig-Holstein',
    "THUERINGEN" => 'Thüringen',
];


/**
 * 
 */
function import_residents_bl(Database $database)
{
    set_time_limit(10);
    $url = 'https://services2.arcgis.com/jUpNdisbWqRpMo35/arcgis/rest/services/Bundesl%C3%A4nder_2018_mit_Einwohnerzahl/FeatureServer/2/query?where=1%3D1&outFields=OBJECTID,LAN_ew_EWZ,LAN_ew_GEN&outSR=4326&f=json';
    $jdata = queryJson($url);

    $residents = [];
    $date = new DateTimeImmutable('2019-01-01');
    $ts = $date->getTimestamp();
    $points = [];

    foreach ($jdata['features'] as $feature) {
        $attr = $feature['attributes'];

        $residents[$attr['LAN_ew_GEN']] = $attr['LAN_ew_EWZ'];
        $points[] = new Point(
            'residents',
            $attr['LAN_ew_EWZ'],
            [
                'name' => $attr['LAN_ew_GEN'],
                'BEZ' => $attr['LAN_ew_BEZ'],
                'SN_L' => $attr['LAN_ew_SN_L'],
            ],
            [
                "residents" => $attr['LAN_ew_EWZ'],
            ],
            $ts
        );
    }
    if (!$database->writePoints($points, Database::PRECISION_SECONDS)) {
        die('Error writing to influxdb!');
    }

    set_time_limit(0);
    return $residents;
}

/**
 * 
 */
function import_residents_lk(Database $database): array
{
    $date = new DateTimeImmutable('2019-01-01');
    $ts = $date->getTimestamp();

    $residents = [];
    $recordCount = 50;
    $resultOffset = 0;
    $url = 'https://services2.arcgis.com/jUpNdisbWqRpMo35/arcgis/rest/services/Kreisgrenzen_2018_mit_Einwohnerzahl/FeatureServer/1/query';
    do {
        set_time_limit(10);
        $jdata = queryJson($url . '?' . http_build_query([
            'where' => '1=1',
            'outFields' => '*',
            'outSR' => 4326,
            'f' => 'json',
            'resultOffset' => $resultOffset,
            'resultRecordCount' => $recordCount,
        ]));

        $points = [];
        foreach ($jdata['features'] as $feature) {
            $attr = $feature['attributes'];

            $residents[$attr['GEN']] = $attr['EWZ'];
            $points[] = new Point(
                'residents',
                $attr['EWZ'],
                [
                    'name' => $attr['GEN'],
                    'BEZ' => $attr['BEZ'],
                    'SN_L' => $attr['SN_L'],
                ],
                [
                    "residents" => $attr['EWZ'],
                ],
                $ts
            );
        }
        if (!$database->writePoints($points, Database::PRECISION_SECONDS)) {
            die('Error writing to influxdb!');
        }
        $resultOffset += count($jdata['features']);
    } while (true === $jdata['exceededTransferLimit'] || count($jdata['features']) >= $recordCount);
    set_time_limit(0);
    return $residents;
}

/**
 * 
 */
function import_residents_lk_rki(Database $database): array
{
    $date = new DateTimeImmutable('2019-01-01');
    $ts = $date->getTimestamp();

    $residents = [];
    $recordCount = 50;
    $resultOffset = 0;
    $url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_Landkreisdaten/FeatureServer/0/query';
    do {
        set_time_limit(10);
        $jdata = queryJson($url . '?' . http_build_query([
            'where' => '1=1',
            'outFields' => '*',
            'outSR' => 4326,
            'f' => 'json',
            'resultOffset' => $resultOffset,
            'resultRecordCount' => $recordCount,
        ]));

        $points = [];
        foreach ($jdata['features'] as $feature) {
            $attr = $feature['attributes'];

            $last_update = strtotime(preg_replace('/,.+$/', '', $attr['last_update']));

            $residents[$attr['GEN']] = $attr['EWZ'];
            $points[] = new Point(
                'rkiregio',
                $attr['cases'],
                [
                    'name' => $attr['GEN'],
                    'BL' => $attr['BL'],
                    'BEZ' => $attr['BEZ'],
                    'SN_L' => $attr['BL_ID']
                ],
                [
                    "residents" => (int)$attr['EWZ'],
                    'cases' => (int)$attr['cases'],
                    'cases_per_100k' => (float)$attr['cases_per_100k'],
                    'cases7_per_100k' => (float)$attr['cases7_per_100k'],
                    'deaths' => (int)$attr['deaths'],
                ],
                $last_update
            );
        }
        if (!$database->writePoints($points, Database::PRECISION_SECONDS)) {
            die('Error writing to influxdb!');
        }
        $resultOffset += count($jdata['features']);
    } while (true === $jdata['exceededTransferLimit'] || count($jdata['features']) >= $recordCount);
    set_time_limit(0);
    return $residents;
}

/**
 * 
 */
function import(Database $database, bool $fullimport)
{
    $residents_bl = $GLOBALS['residents_bl'];
    $residents_lk = $GLOBALS['residents_lk'];

    $offset = 0;
    do {
        set_time_limit(30);
        printf("Import start %s - %d offset %d … ", date(DATE_RSS), RecordCount, $offset);
        $url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_COVID19/FeatureServer/0/query?' .
            http_build_query([
                'where' => $fullimport ? "1=1" : "Meldedatum >= CURRENT_TIMESTAMP - INTERVAL'10' DAY",
                'orderByFields' => 'Meldedatum DESC',
                'outFields' => '*',
                'outSR' => 4326,
                'f' => 'json',
                'resultRecordCount' => RecordCount,
                'resultOffset' => $offset
            ]);
        $jdata = queryJson($url);

        $points = [];
        foreach ($jdata['features'] as $feature) {
            $attr = $feature['attributes'];

            // printf(
            //     "%s - %s,%s,%s,%s - %d / %d\n",
            //     date('Y-m-d', $attr['Meldedatum'] / 1000),
            //     $attr['Bundesland'],
            //     $attr['Landkreis'],
            //     $attr['Altersgruppe'],
            //     $attr['Geschlecht'],
            //     $attr['AnzahlFall'],
            //     $attr['AnzahlTodesfall']
            // );

            $lk = preg_replace('/^(LK|SK)\s+/', '', $attr['Landkreis']);
            $rlk = array_key_exists($lk, $residents_lk) ? $residents_lk[$lk] / 100000 : null;
            $rbl = array_key_exists($attr['Bundesland'], $residents_bl) ? $residents_bl[$attr['Bundesland']] / 100000 : null;
            if ($rlk == null || $rbl === null) {
                error_log(sprintf(
                    "\nERROR %s - %s,%s,%s,%s - %d / %d",
                    date('Y-m-d', $attr['Meldedatum'] / 1000),
                    $attr['Bundesland'],
                    $attr['Landkreis'],
                    $attr['Altersgruppe'],
                    $attr['Geschlecht'],
                    $attr['AnzahlFall'],
                    $attr['AnzahlTodesfall']
                ));
            }
            // printf("%s %25s %30s %f %f\r", 
            //     date('Y-m-d', $attr['Meldedatum'] / 1000),
            //     $attr['Bundesland'], 
            //     $lk, 
            //     $rbl, 
            //     $rlk);
            // flush();

            $points[] = new Point(
                'rki',
                (int)$attr['AnzahlFall'],
                [
                    'Bundesland' => $attr['Bundesland'],
                    'Landkreis' => $attr['Landkreis'],
                    'Altersgruppe' => $attr['Altersgruppe'],
                    'Geschlecht' => $attr['Geschlecht']
                ],
                [
                    'AnzahlFall' => (int)$attr['AnzahlFall'],
                    'AnzahlTodesfall' => (int)$attr['AnzahlTodesfall'],
                    'normFallBl' => ($rbl ? ($attr['AnzahlFall'] / $rbl) : 0.0),
                    'normFallLk' => ($rlk ? ($attr['AnzahlFall'] / $rlk) : 0.0),
                ],
                $attr['Meldedatum']
            );

            $points[] = new Point(
                'rkiref',
                (int)$attr['AnzahlFall'],
                [
                    'Bundesland' => $attr['Bundesland'],
                    'Landkreis' => $attr['Landkreis'],
                    'Altersgruppe' => $attr['Altersgruppe'],
                    'Geschlecht' => $attr['Geschlecht']
                ],
                [
                    'AnzahlFall' => (int)$attr['AnzahlFall'],
                    'AnzahlTodesfall' => (int)$attr['AnzahlTodesfall'],
                    'normFallBl' => ($rbl ? ($attr['AnzahlFall'] / $rbl) : 0.0),
                    'normFallLk' => ($rlk ? ($attr['AnzahlFall'] / $rlk) : 0.0),
                ],
                $attr['Refdatum']
            );
        }

        // we are writing unix timestamps, which have a second precision
        if (!$database->writePoints($points, Database::PRECISION_MILLISECONDS)) {
            die('Error writing to influxdb!');
        }

        $offset += RecordCount;
        sleep(1);
    } while (true === $jdata['exceededTransferLimit'] || count($jdata['features']) >= RecordCount);

    set_time_limit(0);
    echo "done.\n";
}


function import_divi(Database $database)
{
    $blDiviMap = $GLOBALS['blDiviMap'];
    $residents_bl = $GLOBALS['residents_bl'];

    $jdata = queryJson('https://www.intensivregister.de/api/public/reporting/laendertabelle');

    $points = [];
    foreach ($jdata['data'] as $attr) {
        // bundesland	"SCHLESWIG_HOLSTEIN"
        // meldebereichAnz	39
        // faelleCovidAktuell	2
        // faelleCovidAktuellBeatmet	1
        // faelleCovidAktuellBeatmetToCovidAktuellPercent	50
        // intensivBettenBelegt	571
        // intensivBettenFrei	471
        // intensivBettenGesamt	1042
        // covidToIntensivBettenPercent	0.19
        // creationTimestamp	"2020-09-07T05:00:05Z"

        if (!array_key_exists($attr['bundesland'], $blDiviMap)) {
            continue;
        }
        $blName = $blDiviMap[$attr['bundesland']];

        printf(
            "%s - %20s - %6d,%6d,%6d\n",
            $attr['creationTimestamp'],
            $blName,
            $attr['intensivBettenBelegt'],
            $attr['intensivBettenFrei'],
            $attr['intensivBettenGesamt']
        );

        $rbl = array_key_exists($blName, $residents_bl) ? $residents_bl[$blName] / 100000 : null;

        $points[] = new Point(
            'divi',
            (int)$attr['faelleCovidAktuell'],
            [
                'Bundesland' => $blName,
            ],
            [
                'meldebereichAnz' => (int)$attr['meldebereichAnz'],
                'faelleCovidAktuell' => (int)$attr['faelleCovidAktuell'],
                'faelleCovidAktuellBeatmet' => (int)$attr['faelleCovidAktuellBeatmet'],
                'faelleCovidAktuellBeatmetToCovidAktuellPercent' => (int)$attr['faelleCovidAktuellBeatmetToCovidAktuellPercent'],
                'intensivBettenBelegt' => (int)$attr['intensivBettenBelegt'],
                'intensivBettenFrei' => (int)$attr['intensivBettenFrei'],
                'intensivBettenGesamt' => (int)$attr['intensivBettenGesamt'],
                'covidToIntensivBettenPercent' => (int)$attr['covidToIntensivBettenPercent'],
            ],
            strtotime($attr['creationTimestamp'])
        );
    }
    // we are writing unix timestamps, which have a second precision
    if (!$database->writePoints($points, Database::PRECISION_SECONDS)) {
        die('Error writing to influxdb!');
    }
}


function queryJson($url): array
{
    echo "$url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: https://graf.tay-tec.de/?orgId=3',
        'Accept: application/json'
    ]);

    $resp = curl_exec($ch);
    if (200 != curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        throw new Exception('Can not get data:' . curl_error($ch));
    }

    $jdata = json_decode($resp, true);
    if (!is_array($jdata)) {
        throw new Exception('Broken response:' . json_last_error());
    }

    return $jdata;
}

$residents_bl = import_residents_bl($database);
print_r($residents_bl);

$residents_lk =  import_residents_lk_rki($database)  + import_residents_lk($database);
print_r($residents_lk);

if (in_array('-init', $argv)) {
    import_divi($database);
    import($database, true);
}
if ($argv[1] == 'daemon') {
    while (true) {
        sleep(3600 * 6);
        import($database, false);
        import_divi($database);
        import_residents_lk_rki($database);
    }
}
