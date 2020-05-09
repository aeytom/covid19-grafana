<?php

use InfluxDB\Database;
use InfluxDB\Point;

define('RecordCount', 500);

require(__DIR__ . '/vendor/autoload.php');

$client = new InfluxDB\Client(getenv('INFLUXDB_HOST'), getenv('INFLUXDB_PORT'), getenv('INFLUXDB_USER'), getenv('INFLUXDB_PASSWORD'));
$database = $client->selectDB(getenv('INFLUXDB_DATABASE'));

// @see https://de.wikipedia.org/wiki/Liste_der_deutschen_Bundesl%C3%A4nder_nach_Bev%C3%B6lkerung
$residents_bl = [
    'Baden-Württemberg' => 11069533,
    'Bayern' =>  13076721,
    'Berlin' => 3644826,
    'Brandenburg' => 2511917,
    'Bremen' => 682986,
    'Hamburg' => 1841179,
    'Hessen' => 6265809,
    'Mecklenburg-Vorpommern' => 1609675,
    'Niedersachsen' => 7982448,
    'Nordrhein-Westfalen' => 17932651,
    'Rheinland-Pfalz' => 4084844,
    'Saarland' => 990509,
    'Sachsen' => 4077937,
    'Sachsen-Anhalt' => 2208321,
    'Schleswig-Holstein' => 2896712,
    'Thüringen' => 2143145,
];

// @see https://de.wikipedia.org/wiki/Liste_der_Landkreise_in_Deutschland
$residents_lk = [
    'LK Aachen, Städteregion[FN 1]' => 555465,
    'Ahrweiler' => 129727,
    'Aichach-Friedberg' => 133596,
    'Alb-Donau-Kreis' => 196047,
    'Altenburger Land' => 90118,
    'Altenkirchen (Westerwald)' => 128705,
    'Altmarkkreis Salzwedel' => 83765,
    'Altötting' => 111210,
    'Alzey-Worms' => 129244,
    'Amberg-Sulzbach' => 103109,
    'Ammerland' => 124071,
    'Anhalt-Bitterfeld' => 159854,
    'Ansbach' => 183949,
    'Aschaffenburg' => 174208,
    'Augsburg' => 251534,
    'Aurich' => 189848,
    'Bad Dürkheim' => 132660,
    'Bad Kissingen' => 103218,
    'Bad Kreuznach' => 158080,
    'Bad Tölz-Wolfratshausen' => 127227,
    'Bamberg' => 147086,
    'Barnim' => 182760,
    'Bautzen' => 300880,
    'Bayreuth' => 103656,
    'Berchtesgadener Land' => 105722,
    'Bergstraße' => 269694,
    'Bernkastel-Wittlich' => 112262,
    'Biberach' => 199742,
    'Birkenfeld' => 80720,
    'Böblingen' => 391640,
    'Bodenseekreis' => 216227,
    'Börde' => 171734,
    'Borken' => 370676,
    'Breisgau-Hochschwarzwald' => 262795,
    'Burgenlandkreis' => 180190,
    'Calw' => 158397,
    'Celle' => 178936,
    'Cham' => 127882,
    'Cloppenburg' => 169348,
    'Coburg' => 86906,
    'Cochem-Zell' => 61587,
    'Coesfeld' => 219929,
    'Cuxhaven' => 198213,
    'Dachau' => 153884,
    'Dahme-Spreewald' => 169067,
    'Darmstadt-Dieburg' => 297399,
    'Deggendorf' => 119326,
    'Diepholz' => 216886,
    'Dillingen an der Donau' => 96021,
    'Dingolfing-Landau' => 96217,
    'Dithmarschen' => 133210,
    'Donau-Ries' => 133496,
    'Donnersbergkreis' => 75101,
    'Düren' => 263722,
    'Ebersberg' => 142142,
    'Eichsfeld' => 100380,
    'Eichstätt' => 132341,
    'Eifelkreis Bitburg-Prüm' => 98561,
    'Elbe-Elster' => 102638,
    'Emmendingen' => 165383,
    'Emsland' => 325657,
    'Ennepe-Ruhr-Kreis' => 324296,
    'Enzkreis' => 198905,
    'Erding' => 137660,
    'Erlangen-Höchstadt' => 136271,
    'Erzgebirgskreis' => 337696,
    'Esslingen' => 533859,
    'Euskirchen' => 192840,
    'Forchheim' => 116099,
    'Freising' => 179116,
    'Freudenstadt' => 117935,
    'Freyung-Grafenau' => 78355,
    'Friesland' => 98460,
    'Fulda' => 222584,
    'Fürstenfeldbruck' => 219320,
    'Fürth' => 117387,
    'Garmisch-Partenkirchen' => 88467,
    'Germersheim' => 129075,
    'Gießen' => 268876,
    'Gifhorn' => 175920,
    'Göppingen' => 257253,
    'Görlitz' => 254894,
    'Goslar' => 137014,
    'Gotha' => 135452,
    'Göttingen' => 328074,
    'Grafschaft Bentheim' => 136511,
    'Greiz' => 98159,
    'Groß-Gerau' => 274526,
    'Günzburg' => 125747,
    'Gütersloh' => 364083,
    'Hameln-Pyrmont' => 148559,
    'Hannover, Region[FN 1]' => 1157624,
    'Harburg' => 252776,
    'Harz' => 214446,
    'Haßberge' => 84599,
    'Havelland' => 161909,
    'Heidekreis' => 139755,
    'Heidenheim' => 132472,
    'Heilbronn' => 343068,
    'Heinsberg' => 254322,
    'Helmstedt' => 91307,
    'Herford' => 250783,
    'Hersfeld-Rotenburg' => 120829,
    'Herzogtum Lauenburg' => 197264,
    'Hildburghausen' => 63553,
    'Hildesheim' => 276594,
    'Hochsauerlandkreis' => 260475,
    'Hochtaunuskreis' => 236564,
    'Hof' => 95311,
    'Hohenlohekreis' => 112010,
    'Holzminden' => 70975,
    'Höxter' => 140667,
    'Ilm-Kreis' => 106622,
    'Jerichower Land' => 89928,
    'Kaiserslautern' => 106057,
    'Karlsruhe' => 444232,
    'Kassel' => 236633,
    'Kelheim' => 122258,
    'Kitzingen' => 90909,
    'Kleve' => 310974,
    'Konstanz' => 285325,
    'Kronach' => 67135,
    'Kulmbach' => 71845,
    'Kusel' => 70526,
    'Kyffhäuserkreis' => 75009,
    'Lahn-Dill-Kreis' => 253777,
    'Landsberg am Lech' => 120071,
    'Landshut' => 158698,
    'Leer' => 169809,
    'Leipzig' => 257763,
    'Lichtenfels' => 66838,
    'Limburg-Weilburg' => 172083,
    'Lindau (Bodensee)' => 81669,
    'Lippe' => 348391,
    'Lörrach' => 228639,
    'Lüchow-Dannenberg' => 48424,
    'Ludwigsburg' => 543984,
    'Ludwigslust-Parchim' => 212618,
    'Lüneburg' => 183372,
    'Main-Kinzig-Kreis' => 418950,
    'Main-Spessart' => 126365,
    'Main-Tauber-Kreis' => 132321,
    'Main-Taunus-Kreis' => 237735,
    'Mainz-Bingen' => 210889,
    'Mansfeld-Südharz' => 136249,
    'Marburg-Biedenkopf' => 246648,
    'Märkischer Kreis' => 412120,
    'Märkisch-Oderland' => 194328,
    'Mayen-Koblenz' => 214259,
    'Mecklenburgische Seenplatte' => 259130,
    'Meißen' => 242165,
    'Merzig-Wadern' => 103366,
    'Mettmann' => 485684,
    'Miesbach' => 99726,
    'Miltenberg' => 128756,
    'Minden-Lübbecke' => 310710,
    'Mittelsachsen' => 306185,
    'Mühldorf am Inn' => 115250,
    'München' => 348871,
    'Neckar-Odenwald-Kreis' => 143535,
    'Neu-Ulm' => 174200,
    'Neuburg-Schrobenhausen' => 96680,
    'Neumarkt in der Oberpfalz' => 133561,
    'Neunkirchen' => 132206,
    'Neustadt an der Aisch-Bad Windsheim' => 100364,
    'Neustadt an der Waldnaab' => 94352,
    'Neuwied' => 181941,
    'Nienburg/Weser' => 121386,
    'Nordfriesland' => 165507,
    'Nordhausen' => 83822,
    'Nordsachsen' => 197673,
    'Nordwestmecklenburg' => 156729,
    'Northeim' => 132765,
    'Nürnberger Land' => 170365,
    'Oberallgäu' => 155362,
    'Oberbergischer Kreis' => 272471,
    'Oberhavel' => 211249,
    'Oberspreewald-Lausitz' => 110476,
    'Odenwaldkreis' => 96798,
    'Oder-Spree' => 178658,
    'Offenbach' => 354092,
    'Oldenburg' => 130144,
    'Olpe' => 134775,
    'Ortenaukreis' => 429479,
    'Osnabrück' => 357343,
    'Ostalbkreis' => 314002,
    'Ostallgäu' => 140316,
    'Osterholz' => 113517,
    'Ostholstein' => 200581,
    'Ostprignitz-Ruppin' => 99078,
    'Paderborn' => 306890,
    'Passau' => 192043,
    'Peine' => 133965,
    'Pfaffenhofen an der Ilm' => 127151,
    'Pinneberg' => 314391,
    'Plön' => 128647,
    'Potsdam-Mittelmark' => 214664,
    'Prignitz' => 76508,
    'Rastatt' => 231018,
    'Ravensburg' => 284285,
    'Recklinghausen' => 615261,
    'Regen' => 77656,
    'Regensburg' => 193572,
    'Rems-Murr-Kreis' => 426158,
    'Rendsburg-Eckernförde' => 272775,
    'Reutlingen' => 286748,
    'Rhein-Erft-Kreis' => 470089,
    'Rheingau-Taunus-Kreis' => 187157,
    'Rhein-Hunsrück-Kreis' => 102937,
    'Rheinisch-Bergischer Kreis' => 283455,
    'Rhein-Kreis Neuss' => 451007,
    'Rhein-Lahn-Kreis' => 122308,
    'Rhein-Neckar-Kreis' => 547625,
    'Rhein-Pfalz-Kreis' => 154201,
    'Rhein-Sieg-Kreis' => 599780,
    'Rhön-Grabfeld' => 79690,
    'Rosenheim' => 260983,
    'Rostock' => 215113,
    'Rotenburg (Wümme)' => 163455,
    'Roth' => 126958,
    'Rottal-Inn' => 120659,
    'Rottweil' => 139455,
    'Saale-Holzland-Kreis' => 83051,
    'Saalekreis' => 184582,
    'Saale-Orla-Kreis' => 80868,
    'Saalfeld-Rudolstadt' => 104142,
    'Saarbrücken, Regionalverband[FN 1]' => 329708,
    'Saarlouis' => 195201,
    'Saarpfalz-Kreis' => 142631,
    'Sächsische Schweiz-Osterzgebirge' => 245611,
    'Salzlandkreis' => 190560,
    'Schaumburg' => 157781,
    'Schleswig-Flensburg' => 200025,
    'Schmalkalden-Meiningen' => 125646,
    'Schwalm-Eder-Kreis' => 180222,
    'Schwandorf' => 147189,
    'Schwarzwald-Baar-Kreis' => 212381,
    'Schwäbisch Hall' => 195861,
    'Schweinfurt' => 115106,
    'Segeberg' => 276032,
    'Siegen-Wittgenstein' => 278210,
    'Sigmaringen' => 130873,
    'Soest' => 301902,
    'Sömmerda' => 69655,
    'Sonneberg' => 58410,
    'Spree-Neiße' => 114429,
    'St. Wendel' => 87397,
    'Stade' => 203102,
    'Starnberg' => 136092,
    'Steinburg' => 131347,
    'Steinfurt' => 447614,
    'Stendal' => 111982,
    'Stormarn' => 243196,
    'Straubing-Bogen' => 100649,
    'Südliche Weinstraße' => 110356,
    'Südwestpfalz' => 95113,
    'Teltow-Fläming' => 168296,
    'Tirschenreuth' => 72504,
    'Traunstein' => 177089,
    'Trier-Saarburg' => 148945,
    'Tübingen' => 227331,
    'Tuttlingen' => 140152,
    'Uckermark' => 119552,
    'Uelzen' => 92572,
    'Unna' => 394782,
    'Unstrut-Hainich-Kreis' => 102912,
    'Unterallgäu' => 144041,
    'Vechta' => 141598,
    'Verden' => 136792,
    'Viersen' => 298935,
    'Vogelsbergkreis' => 105878,
    'Vogtlandkreis' => 227796,
    'Vorpommern-Greifswald' => 236697,
    'Vorpommern-Rügen' => 224684,
    'Vulkaneifel' => 60603,
    'Waldeck-Frankenberg' => 156953,
    'Waldshut' => 170619,
    'Warendorf' => 277783,
    'Wartburgkreis' => 119726,
    'Weilheim-Schongau' => 135348,
    'Weimarer Land' => 81947,
    'Weißenburg-Gunzenhausen' => 94393,
    'Werra-Meißner-Kreis' => 101017,
    'Wesel' => 459809,
    'Wesermarsch' => 88624,
    'Westerwaldkreis' => 201597,
    'Wetteraukreis' => 306460,
    'Wittenberg' => 125840,
    'Wittmund' => 56882,
    'Wolfenbüttel' => 119960,
    'Wunsiedel im Fichtelgebirge' => 73178,
    'Würzburg' => 161834,
    'Zollernalbkreis' => 188935,
    'Zwickau' => 317531,

];

function import_residents(Database $database, string $tag, array $data)
{
    $points = [];
    $date = new DateTimeImmutable('2019-01-01');
    $ts = $date->getTimestamp();
    foreach ($data as $key => $residents) {
        $points[] = new Point(
            'residents',
            $residents,
            [
                $tag => $key
            ],
            [
                "residents" => $residents,
            ],
            $ts
        );
    }
    if (!$database->writePoints($points, Database::PRECISION_SECONDS)) {
        die('Error writing to influxdb!');
    }
}

function import(Database $database, bool $fullimport)
{
    $residents_bl = $GLOBALS['residents_bl'];
    $residents_lk = $GLOBALS['residents_lk'];
    print_r($residents_bl);
    print_r($residents_lk);

    $offset = 0;
    $ch = curl_init();
    do {
        printf("Import start %s - %d offset %d … \n", date(DATE_RSS), RecordCount, $offset);
        $url = 'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/RKI_COVID19/FeatureServer/0/query?' .
            http_build_query([
                'where' => $fullimport ? "1=1" : "Meldedatum >= CURRENT_TIMESTAMP - INTERVAL'10' DAY",
                'outFields' => '*',
                'outSR' => 4326,
                'f' => 'json',
                'resultRecordCount' => RecordCount,
                'resultOffset' => $offset
            ]);
        echo "… URL: ${url}\n";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

        $resp = curl_exec($ch);
        if (200 != curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            die('Can not get data:' . curl_error($ch));
        }

        $jdata = json_decode($resp, true);
        if (!is_array($jdata)) {
            die('Broken response:' . json_last_error());
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

            $lk = preg_replace('/^(LK|SK)\s+/', '', $attr['Landkreis']);
            $rlk = array_key_exists($lk, $residents_lk) ? $residents_lk[$lk] / 100000 : null;
            $rbl = array_key_exists($attr['Bundesland'], $residents_bl) ? $residents_bl[$attr['Bundesland']] / 100000 : null;
            printf("%s %s %s %f %f\n", $attr['Bundesland'], $attr['Landkreis'], $lk, $rbl, $rlk);

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


//import_residents($database, 'Bundesland', $residents_bl);
//import_residents($database, 'Landkreis', $residents_lk);
import($database, true);
if ($argv[1] == 'daemon') {
    while (true) {
        sleep(7200);
        import($database, false);
    }
}
