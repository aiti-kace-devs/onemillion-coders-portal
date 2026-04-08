<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GhanaConstituencyAndDistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $regionConstituencies = [
            'Ahafo' => [
                'Asunafo North',
                'Asunafo South',
                'Asutifi North',
                'Asutifi South',
                'Tano North',
                'Tano South',
            ],
            'Ashanti' => [
                'Adansi-Asokwa',
                'Afigya Sekyere East',
                'Afigya Kwabre North',
                'Afigya Kwabre South',
                'Ahafo Ano North',
                'Ahafo Ano South East',
                'Ahafo Ano South West',
                'Akrofuom',
                'Asante-Akim Central',
                'Asante-Akim North',
                'Asante-Akim South',
                'Asawase',
                'Asokwa',
                'Atwima-Kwanwoma',
                'Atwima-Mponua',
                'Atwima-Nwabiagya North',
                'Atwima-Nwabiagya South',
                'Bantama',
                'Bekwai',
                'Bosome-Freho',
                'Bosomtwe',
                'Effiduase-Asokore',
                'Ejisu',
                'Ejura-Sekyedumase',
                'Fomena',
                'Juaben',
                'Kumawu',
                'Kwabre East',
                'Kwadaso',
                'Mampong',
                'Manhyia North',
                'Manhyia South',
                'Manso Edubia',
                'Manso Nkwanta',
                'New Edubease',
                'Nhyiaeso',
                'Nsuta-Kwamang-Beposo',
                'Obuasi East',
                'Obuasi West',
                'Odotobri',
                'Offinso North',
                'Offinso South',
                'Oforikrom',
                'Old Tafo',
                'Sekyere Afram plains',
                'Suame',
                'Subin',
            ],
            'Bono' => [
                'Banda',
                'Berekum East',
                'Berekum West',
                'Dormaa Central',
                'Dormaa East',
                'Dormaa West',
                'Jaman North',
                'Jaman South',
                'Sunyani East',
                'Sunyani West',
                'Tain',
                'Wenchi',
            ],
            'Bono East' => [
                'Atebubu Amantin',
                'Kintampo North',
                'Kintampo South',
                'Nkoranza North',
                'Nkoranza South',
                'Pru East',
                'Pru West',
                'Sene East',
                'Sene West',
                'Techiman North',
                'Techiman South',
            ],
            'Central' => [
                'Abura Asebu Kwamankese',
                'Agona East',
                'Agona West',
                'Ajumako-Enyan-Esiam',
                'Assin Central',
                'Assin North',
                'Assin South',
                'Asikuma-Odoben-Brakwa',
                'Awutu Senya East',
                'Awutu Senya West',
                'Cape Coast North',
                'Cape Coast South',
                'Effutu',
                'Ekumfi',
                'Gomoa Central',
                'Gomoa East',
                'Gomoa West',
                'Komenda-Edina-Eguafo-Abirem',
                'Mfantseman',
                'Twifo-Atii Morkwaa',
                'Upper Denkyira East',
                'Upper Denkyira West',
                'Hemang Lower Denkyira',
            ],
            'Eastern' => [
                'Abetifi',
                'Abirem',
                'Achiase',
                'Afram Plains North',
                'Afram Plains South',
                'Akim Abuakwa North',
                'Akim Abuakwa South',
                'Akim Oda',
                'Akim Swedru',
                'Akuapem North',
                'Akwapim South',
                'Akwatia',
                'Asene Akroso-Manso',
                'Asuogyaman',
                'Atiwa East',
                'Atiwa West',
                'Ayensuano',
                'Fanteakwa North',
                'Fanteakwa South',
                'Kade',
                'Lower Manya',
                'Lower West Akim',
                'Mpraeso',
                'New Juaben North',
                'New Juaben South',
                'Nkawkaw',
                'Nsawam Adoagyiri',
                'Ofoase-Ayirebi',
                'Okere',
                'Suhum',
                'Upper Manya',
                'Upper West Akim',
                'Yilo Krobo',
            ],
            'Greater Accra' => [
                'Ablekuma Central',
                'Ablekuma North',
                'Ablekuma South',
                'Ablekuma West',
                'Ada',
                'Adenta',
                'Amasaman',
                'Anyaa-Sowutuom',
                'Ashaiman',
                'Ayawaso Central',
                'Ayawaso East',
                'Ayawaso North',
                'Ayawaso West',
                'Bortianor-Ngleshie-Amanfrom',
                'Dade Kotopon',
                'Domeabra-Obom',
                'Dome-Kwabenya',
                'Klottey Korle',
                'Kpone-Katamanso',
                'Krowor',
                'Ledzokuku',
                'Madina',
                'Ningo-Prampram',
                'Odododiodio',
                'Okaikwei Central',
                'Okaikwei North',
                'Okaikwei South',
                'Sege',
                'Shai-Osudoku',
                'Tema Central',
                'Tema East',
                'Tema West',
                'Trobu',
                'Weija-Gbawe',
            ],
            'North East' => [
                'Bunkpurugu',
                'Chereponi',
                'Nalerigu',
                'Walewale',
                'Yagaba-Kubori',
                'Yunyoo',
            ],
            'Northern' => [
                'Bimbilla',
                'Gushegu',
                'Karaga',
                'Kpandai',
                'Kumbungu',
                'Mion',
                'Nanton',
                'Saboba',
                'Sagnarigu',
                'Savelugu',
                'Tamale Central',
                'Tamale North',
                'Tamale South',
                'Tatale-Sanguli',
                'Tolon',
                'Wulensi',
                'Yendi',
                'Zabzugu',
            ],
            'Oti' => [
                'Akan',
                'Biakoye',
                'Buem',
                'Guan',
                'Krachi East',
                'Krachi Nchumuru',
                'Krachi West',
                'Nkwanta North',
                'Nkwanta South',
            ],
            'Savannah' => [
                'Bole',
                'Daboya-Mankarigu',
                'Damango',
                'Salaga North',
                'Salaga South',
                'Sawla-Tuna-Kalba',
                'Yapei-Kusawgu',
            ],
            'Upper East' => [
                'Bawku Central',
                'Binduri',
                'Bolgatanga Central',
                'Bolgatanga East',
                'Bongo',
                'Builsa North',
                'Builsa South',
                'Chiana-Paga',
                'Garu',
                'Nabdam',
                'Navrongo Central',
                'Pusiga',
                'Talensi',
                'Tempane',
                'Zebilla',
            ],
            'Upper West' => [
                'Daffiama-Bussie-Issa',
                'Jirapa',
                'Lambussie',
                'Lawra',
                'Nadowli Kaleo',
                'Nandom',
                'Sissala East',
                'Sissala West',
                'Wa Central',
                'Wa East',
                'Wa West',
            ],
            'Volta' => [
                'Adaklu',
                'Afadjato South',
                'Agotime-Ziope',
                'Akatsi North',
                'Akatsi South',
                'Anlo',
                'Central Tongu',
                'Ho Central',
                'Ho West',
                'Hohoe',
                'Keta',
                'Ketu North',
                'Ketu South',
                'Kpando',
                'North Dayi',
                'North Tongu',
                'South Dayi',
                'South Tongu',
            ],
            'Western' => [
                'Ahanta West',
                'Amenfi Central',
                'Amenfi East',
                'Amenfi West',
                'Effia',
                'Ellembelle',
                'Essikado-Ketan',
                'Evalue-Gwira',
                'Jomoro',
                'Kwesimintsim',
                'Mpohor',
                'Prestea-Huni Valley',
                'Sekondi',
                'Shama',
                'Takoradi',
                'Tarkwa-Nsuaem',
                'Wassa East',
            ],
            'Western North' => [
                'Aowin',
                'Bia East',
                'Bia West',
                'Bibiani-Anhwiaso-Bekwai',
                'Bodi',
                'Juabeso',
                'Sefwi-Wiawso',
                'Sefwi-Akontombra',
                'Suaman',
            ],
        ];

        $regionDistricts = [
            // =========================
            // GREATER ACCRA (29)
            // =========================
            'Greater Accra' => [
                'Accra Metropolitan',
                'Tema Metropolitan',
                'Adentan Municipal',
                'Ashaiman Municipal',
                'Ga Central Municipal',
                'Ga East Municipal',
                'Ga West Municipal',
                'Ga South Municipal',
                'Ga North Municipal',
                'Ledzokuku Municipal',
                'Krowor Municipal',
                'La Dade Kotopon Municipal',
                'La Nkwantanang Madina Municipal',
                'Ablekuma North Municipal',
                'Ablekuma Central Municipal',
                'Ablekuma West Municipal',
                'Ayawaso East Municipal',
                'Ayawaso North Municipal',
                'Ayawaso West Municipal',
                'Ayawaso Central Municipal',
                'Okaikwei North Municipal',
                'Korle Klottey Municipal',
                'Weija Gbawe Municipal',
                'Tema West Municipal',
                'Kpone Katamanso Municipal',
                'Ningo Prampram District',
                'Shai Osudoku District',
                'Ada East District',
                'Ada West District'
            ],

            'Upper East' => [
                // ================= Upper East Region =================
                'Bawku Municipal',
                'Bawku West',
                'Binduri',
                'Bolgatanga East',
                'Bolgatanga Municipal',
                'Bongo',
                'Builsa North',
                'Builsa South',
                'Garu',
                'Kassena-Nankana Municipal',
                'Kassena-Nankana West',
                'Paga',
                'Talensi',
                'Zebilla',
                'Nabdam',
                'Pusiga',
                'Tempane',
            ],

            'Bono' => [
                // ================= Bono Region =================
                'Banda',
                'Berekum East Municipal',
                'Berekum West',
                'Dormaa Central Municipal',
                'Dormaa East',
                'Dormaa West',
                'Jaman North',
                'Jaman South Municipal',
                'Sunyani Municipal',
                'Sunyani West',
                'Tain',
                'Wenchi Municipal',
            ],

            'Ashanti' => [
                // =========================
                // ASHANTI (43)
                // =========================
                'Kumasi Metropolitan',
                'Asokore Mampong Municipal',
                'Obuasi Municipal',
                'Obuasi East District',
                'Ejisu Municipal',
                'Juaben Municipal',
                'Bekwai Municipal',
                'Bosomtwe District',
                'Adansi North District',
                'Adansi South District',
                'Adansi Asokwa District',
                'Akrofuom District',
                'Amansie West District',
                'Amansie Central District',
                'Amansie South District',
                'Asante Akim Central Municipal',
                'Asante Akim North District',
                'Asante Akim South Municipal',
                'Asokwa Municipal',
                'Atwima Kwanwoma District',
                'Atwima Mponua District',
                'Atwima Nwabiagya Municipal',
                'Afigya Kwabre North District',
                'Afigya Kwabre South District',
                'Ejura Sekyedumase Municipal',
                'Kwabre East Municipal',
                'Kwadaso Municipal',
                'Mampong Municipal',
                'Offinso Municipal',
                'Offinso North District',
                'Old Tafo Municipal',
                'Oforikrom Municipal',
                'Suame Municipal',
                'Sekyere East District',
                'Sekyere Kumawu District',
                'Sekyere Afram Plains District',
                'Ahafo Ano North Municipal',
                'Ahafo Ano South East District',
                'Ahafo Ano South West District',
                'Konongo Odumase Municipal',
                'Manso Adubia District',
                'Tafo Pankrono Municipal',
            ],

            'Eastern' => [
                // ================= EASTERN (33) =================
                'New Juaben South Municipal',
                'New Juaben North Municipal',
                'Akuapim North Municipal',
                'Akuapim South District',
                'Akyemansa District',
                'Asene Manso Akroso District',
                'Atiwa East District',
                'Atiwa West District',
                'Ayensuano District',
                'Birim Central Municipal',
                'Birim North District',
                'Birim South District',
                'Denkyembour District',
                'East Akim Municipal',
                'Fanteakwa North District',
                'Fanteakwa South District',
                'Kwaebibirem Municipal',
                'Kwahu Afram Plains North District',
                'Kwahu Afram Plains South District',
                'Kwahu East District',
                'Kwahu South District',
                'Kwahu West Municipal',
                'Lower Manya Krobo Municipal',
                'Upper Manya Krobo District',
                'Nsawam Adoagyiri Municipal',
                'Okere District',
                'Suhum Municipal',
                'Upper West Akim District',
                'West Akim Municipal',
                'Yilo Krobo Municipal',
                'Abuakwa North Municipal',
                'Abuakwa South Municipal',
                'Achiase District',
            ],

            'Central' => [
                // ================= CENTRAL (22) =================
                'Cape Coast Metropolitan',
                'Komenda Edina Eguafo Abirem Municipal',
                'Abura Asebu Kwamankese District',
                'Agona East District',
                'Agona West Municipal',
                'Ajumako Enyan Essiam District',
                'Asikuma Odoben Brakwa District',
                'Assin Central Municipal',
                'Assin North District',
                'Assin South District',
                'Awutu Senya East Municipal',
                'Awutu Senya West District',
                'Effutu Municipal',
                'Ekumfi District',
                'Gomoa East District',
                'Gomoa West District',
                'Mfantseman Municipal',
                'Twifo Atti Morkwa District',
                'Twifo Hemang Lower Denkyira District',
                'Upper Denkyira East Municipal',
                'Upper Denkyira West District',
                'Gomoa Central District',
            ],

            'Western' => [
                // ================= WESTERN (14) =================
                'Sekondi Takoradi Metropolitan',
                'Shama District',
                'Wassa East District',
                'Ahanta West Municipal',
                'Nzema East Municipal',
                'Ellembelle District',
                'Jomoro Municipal',
                'Tarkwa Nsuaem Municipal',
                'Prestea Huni Valley Municipal',
                'Wassa Amenfi East Municipal',
                'Wassa Amenfi Central District',
                'Wassa Amenfi West Municipal',
                'Mpohor District',
                'Effia Kwesimintsim Municipal',
            ],

            'Western North' => [
                // ================= WESTERN NORTH (9) =================
                'Sefwi Wiawso Municipal',
                'Bibiani Anhwiaso Bekwai Municipal',
                'Bodi District',
                'Juaboso District',
                'Akontombra District',
                'Aowin Municipal',
                'Suaman District',
                'Bia East District',
                'Bia West District',
            ],

            'Volta' => [
                // ================= VOLTA (18) =================
                'Ho Municipal',
                'Ho West District',
                'Adaklu District',
                'Agotime Ziope District',
                'Akatsi South Municipal',
                'Akatsi North District',
                'Anloga District',
                'Keta Municipal',
                'Ketu North Municipal',
                'Ketu South Municipal',
                'Central Tongu District',
                'North Tongu District',
                'South Tongu District',
                'Afadzato South District',
                'Hohoe Municipal',
                'Jasikan District',
                'Kadjebi District',
                'Kpando Municipal',
            ],

            'Oti' => [
                // ================= OTI (9) =================
                'Dambai Municipal',
                'Biakoye District',
                'Jasikan District',
                'Kadjebi District',
                'Krachi East Municipal',
                'Krachi Nchumuru District',
                'Krachi West District',
                'Nkwanta North District',
                'Nkwanta South Municipal',
            ],

            'Northern' => [
                // ================= NORTHERN (16) =================
                'Tamale Metropolitan',
                'Sagnarigu Municipal',
                'Yendi Municipal',
                'Mion District',
                'Gushegu Municipal',
                'Karaga District',
                'Kpandai District',
                'Kumbungu District',
                'Nanumba North Municipal',
                'Nanumba South District',
                'Saboba District',
                'Savelugu Municipal',
                'Tatale Sanguli District',
                'Tolon District',
                'Zabzugu District',
                'Nanton District',
            ],


            'North East' => [
                // ================= North East Region =================
                'Bunkpurugu-Nyankpanduri',
                'Chereponi',
                'East Mamprusi',
                'Mamprugu-Moagduri',
                'West Mamprusi',
                'Yunyoo-Nasuan',
            ],

            'Savannah' => [
                // ================= Savannah Region =================
                'Bole',
                'Central Gonja',
                'East Gonja',
                'North Gonja',
                'North East Gonja',
                'Sawla-Tuna-Kalba',
                'West Gonja',
            ],

            'Upper West' => [
                // ================= Upper West Region =================
                'Daffiama-Bussie-Issa',
                'Jirapa',
                'Lambussie Karni',
                'Lawra',
                'Nadowli-Kaleo',
                'Nandom',
                'Sissala East',
                'Sissala West',
                'Wa East',
                'Wa Municipal',
                'Wa West',
            ],

            'Bono East' => [
                // ================= Bono East Region =================
                'Atebubu-Amantin Municipal',
                'Kintampo North Municipal',
                'Kintampo South',
                'Nkoranza North',
                'Nkoranza South Municipal',
                'Pru East',
                'Pru West',
                'Sene East',
                'Sene West',
                'Techiman Municipal',
                'Techiman North',
            ],

            'Ahafo' => [
                // ================= Ahafo Region =================
                'Asunafo North Municipal',
                'Asunafo South',
                'Asutifi North',
                'Asutifi South',
                'Tano North Municipal',
                'Tano South Municipal',
            ],
        ];

        $regionAliases = [
            'Ahafo' => ['Ahafo', 'Ahafo Region'],
            'Ashanti' => ['Ashanti', 'Ashanti Region'],
            'Bono' => ['Bono', 'Bono Region'],
            'Bono East' => ['Bono East', 'Bono East Region'],
            'Central' => ['Central', 'Central Region'],
            'Eastern' => ['Eastern', 'Eastern Region'],
            'Greater Accra' => ['Greater Accra', 'Greater Accra Region'],
            'North East' => ['North East', 'North East Region', 'Northeast'],
            'Northern' => ['Northern', 'Northern Region'],
            'Oti' => ['Oti', 'Oti Region'],
            'Savannah' => ['Savannah', 'Savannah Region'],
            'Upper East' => ['Upper East', 'Upper East Region'],
            'Upper West' => ['Upper West', 'Upper West Region'],
            'Volta' => ['Volta', 'Volta Region'],
            'Western' => ['Western', 'Western Region'],
            'Western North' => ['Western North', 'Western North Region'],
        ];

        $branchIds = $this->resolveBranchIds($regionAliases);

        // add constituencies and districts using the region aliases keys
        foreach ($regionAliases as $region => $a) {
            $branchId = $branchIds[$region] ?? null;
            if ($branchId === null) {
                throw new Exception("Unable to resolve branch for region: {$region}");
            }

            $currentRegionConstituencies = $regionConstituencies[$region] ?? [];
            $currentRegionDistricts = $regionDistricts[$region] ?? [];

            $constituencyRows = [];
            $districtRows = [];

            foreach ($currentRegionConstituencies as $constituency) {
                $constituencyRows[] = [
                    'title' => $constituency,
                    'branch_id' => $branchId,
                    'status' => 1,
                ];
            }

            foreach ($currentRegionDistricts as $district) {
                $districtRows[] = [
                    'title' => $district,
                    'branch_id' => $branchId,
                    'status' => 1,
                ];
            }

            DB::table('constituencies')->insert($constituencyRows);
            DB::table('districts')->insert($districtRows);
        }
    }

    /**
     * @param array<string, array<int, string>> $regionAliases
     * @return array<string, int>
     */
    private function resolveBranchIds(array $regionAliases): array
    {
        $normalizedToId = DB::table('branches')
            ->select(['id', 'title'])
            ->get()
            ->reduce(function (array $carry, $branch) {
                $carry[$this->normalize((string) $branch->title)] = (int) $branch->id;
                return $carry;
            }, []);

        $resolved = [];

        foreach ($regionAliases as $region => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalize($alias);
                if (isset($normalizedToId[$normalizedAlias])) {
                    $resolved[$region] = $normalizedToId[$normalizedAlias];
                    break;
                }
            }

            if (!isset($resolved[$region])) {
                $branchId = DB::table('branches')->insertGetId([
                    'title' => $region,
                    'status' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $resolved[$region] = $branchId;
                $normalizedToId[$this->normalize($region)] = $branchId;
            }
        }

        return $resolved;
    }

    private function normalize(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('/\bregion\b/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
