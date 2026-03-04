<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GhanaConstituencySeeder extends Seeder
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
        $rows = [];

        foreach ($regionConstituencies as $region => $constituencies) {
            $branchId = $branchIds[$region] ?? null;
            if ($branchId === null) {
                throw new RuntimeException("Unable to resolve branch for region: {$region}");
            }

            foreach ($constituencies as $constituency) {
                $rows[] = [
                    'title' => $constituency,
                    'branch_id' => $branchId,
                    'status' => 1,
                ];
            }
        }

        DB::table('constituencies')->insert($rows);
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
                throw new RuntimeException("Branch record not found for region '{$region}'. Seed or create regions first.");
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
