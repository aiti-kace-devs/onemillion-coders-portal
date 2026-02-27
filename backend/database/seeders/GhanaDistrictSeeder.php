<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GhanaDistrictSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('districts')->insert([


            // =========================
            // GREATER ACCRA (29)
            // =========================
            ['title'=>'Accra Metropolitan','branch_id'=>1,'status'=>1],
            ['title'=>'Tema Metropolitan','branch_id'=>1,'status'=>1],
            ['title'=>'Adentan Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ashaiman Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ga Central Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ga East Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ga West Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ga South Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ga North Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ledzokuku Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Krowor Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'La Dade Kotopon Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'La Nkwantanang Madina Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ablekuma North Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ablekuma Central Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ablekuma West Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ayawaso East Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ayawaso North Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ayawaso West Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ayawaso Central Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Okaikwei North Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Korle Klottey Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Weija Gbawe Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Tema West Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Kpone Katamanso Municipal','branch_id'=>1,'status'=>1],
            ['title'=>'Ningo Prampram District','branch_id'=>1,'status'=>1],
            ['title'=>'Shai Osudoku District','branch_id'=>1,'status'=>1],
            ['title'=>'Ada East District','branch_id'=>1,'status'=>1],
            ['title'=>'Ada West District','branch_id'=>1,'status'=>1],


                // ================= Upper East Region =================
            ['title' => 'Bawku Municipal', 'branch_id' => 2,'status'=>1],
            ['title' => 'Bawku West', 'branch_id' => 2,'status'=>1],
            ['title' => 'Binduri', 'branch_id' => 2,'status'=>1],
            ['title' => 'Bolgatanga East', 'branch_id' => 2,'status'=>1],
            ['title' => 'Bolgatanga Municipal', 'branch_id' => 2,'status'=>1],
            ['title' => 'Bongo', 'branch_id' => 2,'status'=>1],
            ['title' => 'Builsa North', 'branch_id' => 2,'status'=>1],
            ['title' => 'Builsa South', 'branch_id' => 2,'status'=>1],
            ['title' => 'Garu', 'branch_id' => 2,'status'=>1],
            ['title' => 'Kassena-Nankana Municipal', 'branch_id' => 2,'status'=>1],
            ['title' => 'Kassena-Nankana West', 'branch_id' => 2,'status'=>1],
            ['title' => 'Paga', 'branch_id' => 2,'status'=>1],
            ['title' => 'Talensi', 'branch_id' => 2,'status'=>1],
            ['title' => 'Zebilla', 'branch_id' => 2,'status'=>1],
            ['title' => 'Nabdam', 'branch_id' => 2,'status'=>1],
            ['title' => 'Pusiga', 'branch_id' => 2,'status'=>1],
            ['title' => 'Tempane', 'branch_id' => 2,'status'=>1],




            // ================= Bono Region =================
            ['title' => 'Banda', 'branch_id' => 3,'status'=>1],
            ['title' => 'Berekum East Municipal', 'branch_id' => 3,'status'=>1],
            ['title' => 'Berekum West', 'branch_id' => 3,'status'=>1],
            ['title' => 'Dormaa Central Municipal', 'branch_id' => 3,'status'=>1],
            ['title' => 'Dormaa East', 'branch_id' => 3,'status'=>1],
            ['title' => 'Dormaa West', 'branch_id' => 3,'status'=>1],
            ['title' => 'Jaman North', 'branch_id' => 3,'status'=>1],
            ['title' => 'Jaman South Municipal', 'branch_id' => 3,'status'=>1],
            ['title' => 'Sunyani Municipal', 'branch_id' => 3,'status'=>1],
            ['title' => 'Sunyani West', 'branch_id' => 3,'status'=>1],
            ['title' => 'Tain', 'branch_id' => 3,'status'=>1],
            ['title' => 'Wenchi Municipal', 'branch_id' => 3,'status'=>1],


            // =========================
            // ASHANTI (43)
            // =========================
            ['title'=>'Kumasi Metropolitan','branch_id'=>4,'status'=>1],
            ['title'=>'Asokore Mampong Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Obuasi Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Obuasi East District','branch_id'=>4,'status'=>1],
            ['title'=>'Ejisu Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Juaben Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Bekwai Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Bosomtwe District','branch_id'=>4,'status'=>1],
            ['title'=>'Adansi North District','branch_id'=>4,'status'=>1],
            ['title'=>'Adansi South District','branch_id'=>4,'status'=>1],
            ['title'=>'Adansi Asokwa District','branch_id'=>4,'status'=>1],
            ['title'=>'Akrofuom District','branch_id'=>4,'status'=>1],
            ['title'=>'Amansie West District','branch_id'=>4,'status'=>1],
            ['title'=>'Amansie Central District','branch_id'=>4,'status'=>1],
            ['title'=>'Amansie South District','branch_id'=>4,'status'=>1],
            ['title'=>'Asante Akim Central Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Asante Akim North District','branch_id'=>4,'status'=>1],
            ['title'=>'Asante Akim South Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Asokwa Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Atwima Kwanwoma District','branch_id'=>4,'status'=>1],
            ['title'=>'Atwima Mponua District','branch_id'=>4,'status'=>1],
            ['title'=>'Atwima Nwabiagya Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Afigya Kwabre North District','branch_id'=>4,'status'=>1],
            ['title'=>'Afigya Kwabre South District','branch_id'=>4,'status'=>1],
            ['title'=>'Ejura Sekyedumase Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Kwabre East Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Kwadaso Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Mampong Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Offinso Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Offinso North District','branch_id'=>4,'status'=>1],
            ['title'=>'Old Tafo Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Oforikrom Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Suame Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Sekyere East District','branch_id'=>4,'status'=>1],
            ['title'=>'Sekyere Kumawu District','branch_id'=>4,'status'=>1],
            ['title'=>'Sekyere Afram Plains District','branch_id'=>4,'status'=>1],
            ['title'=>'Ahafo Ano North Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Ahafo Ano South East District','branch_id'=>4,'status'=>1],
            ['title'=>'Ahafo Ano South West District','branch_id'=>4,'status'=>1],
            ['title'=>'Konongo Odumase Municipal','branch_id'=>4,'status'=>1],
            ['title'=>'Manso Adubia District','branch_id'=>4,'status'=>1],
            ['title'=>'Tafo Pankrono Municipal','branch_id'=>4,'status'=>1],



            // ================= EASTERN (33) =================
            ['title'=>'New Juaben South Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'New Juaben North Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Akuapim North Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Akuapim South District','branch_id'=>6,'status'=>1],
            ['title'=>'Akyemansa District','branch_id'=>6,'status'=>1],
            ['title'=>'Asene Manso Akroso District','branch_id'=>6,'status'=>1],
            ['title'=>'Atiwa East District','branch_id'=>6,'status'=>1],
            ['title'=>'Atiwa West District','branch_id'=>6,'status'=>1],
            ['title'=>'Ayensuano District','branch_id'=>6,'status'=>1],
            ['title'=>'Birim Central Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Birim North District','branch_id'=>6,'status'=>1],
            ['title'=>'Birim South District','branch_id'=>6,'status'=>1],
            ['title'=>'Denkyembour District','branch_id'=>6,'status'=>1],
            ['title'=>'East Akim Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Fanteakwa North District','branch_id'=>6,'status'=>1],
            ['title'=>'Fanteakwa South District','branch_id'=>6,'status'=>1],
            ['title'=>'Kwaebibirem Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Kwahu Afram Plains North District','branch_id'=>6,'status'=>1],
            ['title'=>'Kwahu Afram Plains South District','branch_id'=>6,'status'=>1],
            ['title'=>'Kwahu East District','branch_id'=>6,'status'=>1],
            ['title'=>'Kwahu South District','branch_id'=>6,'status'=>1],
            ['title'=>'Kwahu West Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Lower Manya Krobo Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Upper Manya Krobo District','branch_id'=>6,'status'=>1],
            ['title'=>'Nsawam Adoagyiri Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Okere District','branch_id'=>6,'status'=>1],
            ['title'=>'Suhum Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Upper West Akim District','branch_id'=>6,'status'=>1],
            ['title'=>'West Akim Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Yilo Krobo Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Abuakwa North Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Abuakwa South Municipal','branch_id'=>6,'status'=>1],
            ['title'=>'Achiase District','branch_id'=>6,'status'=>1],

            // ================= CENTRAL (22) =================
            ['title'=>'Cape Coast Metropolitan','branch_id'=>7,'status'=>1],
            ['title'=>'Komenda Edina Eguafo Abirem Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Abura Asebu Kwamankese District','branch_id'=>7,'status'=>1],
            ['title'=>'Agona East District','branch_id'=>7,'status'=>1],
            ['title'=>'Agona West Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Ajumako Enyan Essiam District','branch_id'=>7,'status'=>1],
            ['title'=>'Asikuma Odoben Brakwa District','branch_id'=>7,'status'=>1],
            ['title'=>'Assin Central Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Assin North District','branch_id'=>7,'status'=>1],
            ['title'=>'Assin South District','branch_id'=>7,'status'=>1],
            ['title'=>'Awutu Senya East Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Awutu Senya West District','branch_id'=>7,'status'=>1],
            ['title'=>'Effutu Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Ekumfi District','branch_id'=>7,'status'=>1],
            ['title'=>'Gomoa East District','branch_id'=>7,'status'=>1],
            ['title'=>'Gomoa West District','branch_id'=>7,'status'=>1],
            ['title'=>'Mfantseman Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Twifo Atti Morkwa District','branch_id'=>7,'status'=>1],
            ['title'=>'Twifo Hemang Lower Denkyira District','branch_id'=>7,'status'=>1],
            ['title'=>'Upper Denkyira East Municipal','branch_id'=>7,'status'=>1],
            ['title'=>'Upper Denkyira West District','branch_id'=>7,'status'=>1],
            ['title'=>'Gomoa Central District','branch_id'=>7,'status'=>1],




            // ================= WESTERN (14) =================
            ['title'=>'Sekondi Takoradi Metropolitan','branch_id'=>8,'status'=>1],
            ['title'=>'Shama District','branch_id'=>8,'status'=>1],
            ['title'=>'Wassa East District','branch_id'=>8,'status'=>1],
            ['title'=>'Ahanta West Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Nzema East Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Ellembelle District','branch_id'=>8,'status'=>1],
            ['title'=>'Jomoro Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Tarkwa Nsuaem Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Prestea Huni Valley Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Wassa Amenfi East Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Wassa Amenfi Central District','branch_id'=>8,'status'=>1],
            ['title'=>'Wassa Amenfi West Municipal','branch_id'=>8,'status'=>1],
            ['title'=>'Mpohor District','branch_id'=>8,'status'=>1],
            ['title'=>'Effia Kwesimintsim Municipal','branch_id'=>8,'status'=>1],




            // ================= WESTERN NORTH (9) =================
            ['title'=>'Sefwi Wiawso Municipal','branch_id'=>9,'status'=>1],
            ['title'=>'Bibiani Anhwiaso Bekwai Municipal','branch_id'=>9,'status'=>1],
            ['title'=>'Bodi District','branch_id'=>9,'status'=>1],
            ['title'=>'Juaboso District','branch_id'=>9,'status'=>1],
            ['title'=>'Akontombra District','branch_id'=>9,'status'=>1],
            ['title'=>'Aowin Municipal','branch_id'=>9,'status'=>1],
            ['title'=>'Suaman District','branch_id'=>9,'status'=>1],
            ['title'=>'Bia East District','branch_id'=>9,'status'=>1],
            ['title'=>'Bia West District','branch_id'=>9,'status'=>1],



            // ================= VOLTA (18) =================
            ['title'=>'Ho Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Ho West District','branch_id'=>10,'status'=>1],
            ['title'=>'Adaklu District','branch_id'=>10,'status'=>1],
            ['title'=>'Agotime Ziope District','branch_id'=>10,'status'=>1],
            ['title'=>'Akatsi South Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Akatsi North District','branch_id'=>10,'status'=>1],
            ['title'=>'Anloga District','branch_id'=>10,'status'=>1],
            ['title'=>'Keta Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Ketu North Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Ketu South Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Central Tongu District','branch_id'=>10,'status'=>1],
            ['title'=>'North Tongu District','branch_id'=>10,'status'=>1],
            ['title'=>'South Tongu District','branch_id'=>10,'status'=>1],
            ['title'=>'Afadzato South District','branch_id'=>10,'status'=>1],
            ['title'=>'Hohoe Municipal','branch_id'=>10,'status'=>1],
            ['title'=>'Jasikan District','branch_id'=>10,'status'=>1],
            ['title'=>'Kadjebi District','branch_id'=>10,'status'=>1],
            ['title'=>'Kpando Municipal','branch_id'=>10,'status'=>1],



            // ================= OTI (9) =================
            ['title'=>'Dambai Municipal','branch_id'=>11,'status'=>1],
            ['title'=>'Biakoye District','branch_id'=>11,'status'=>1],
            ['title'=>'Jasikan District','branch_id'=>11,'status'=>1],
            ['title'=>'Kadjebi District','branch_id'=>11,'status'=>1],
            ['title'=>'Krachi East Municipal','branch_id'=>11,'status'=>1],
            ['title'=>'Krachi Nchumuru District','branch_id'=>11,'status'=>1],
            ['title'=>'Krachi West District','branch_id'=>11,'status'=>1],
            ['title'=>'Nkwanta North District','branch_id'=>11,'status'=>1],
            ['title'=>'Nkwanta South Municipal','branch_id'=>11,'status'=>1],




            // ================= NORTHERN (16) =================
            ['title'=>'Tamale Metropolitan','branch_id'=>12,'status'=>1],
            ['title'=>'Sagnarigu Municipal','branch_id'=>12,'status'=>1],
            ['title'=>'Yendi Municipal','branch_id'=>12,'status'=>1],
            ['title'=>'Mion District','branch_id'=>12,'status'=>1],
            ['title'=>'Gushegu Municipal','branch_id'=>12,'status'=>1],
            ['title'=>'Karaga District','branch_id'=>12,'status'=>1],
            ['title'=>'Kpandai District','branch_id'=>12,'status'=>1],
            ['title'=>'Kumbungu District','branch_id'=>12,'status'=>1],
            ['title'=>'Nanumba North Municipal','branch_id'=>12,'status'=>1],
            ['title'=>'Nanumba South District','branch_id'=>12,'status'=>1],
            ['title'=>'Saboba District','branch_id'=>12,'status'=>1],
            ['title'=>'Savelugu Municipal','branch_id'=>12,'status'=>1],
            ['title'=>'Tatale Sanguli District','branch_id'=>12,'status'=>1],
            ['title'=>'Tolon District','branch_id'=>12,'status'=>1],
            ['title'=>'Zabzugu District','branch_id'=>12,'status'=>1],
            ['title'=>'Nanton District','branch_id'=>12,'status'=>1],



        // ================= North East Region =================
            ['title' => 'Bunkpurugu-Nyankpanduri', 'branch_id' => 13,'status'=>1],
            ['title' => 'Chereponi', 'branch_id' => 13,'status'=>1],
            ['title' => 'East Mamprusi', 'branch_id' => 13,'status'=>1],
            ['title' => 'Mamprugu-Moagduri', 'branch_id' => 13,'status'=>1],
            ['title' => 'West Mamprusi', 'branch_id' => 13,'status'=>1],
            ['title' => 'Yunyoo-Nasuan', 'branch_id' => 13,'status'=>1],



        // ================= Savannah Region =================
            ['title' => 'Bole', 'branch_id' => 14,'status'=>1],
            ['title' => 'Central Gonja', 'branch_id' => 14,'status'=>1],
            ['title' => 'East Gonja', 'branch_id' => 14,'status'=>1],
            ['title' => 'North Gonja', 'branch_id' => 14,'status'=>1],
            ['title' => 'North East Gonja', 'branch_id' => 14,'status'=>1],
            ['title' => 'Sawla-Tuna-Kalba', 'branch_id' => 14,'status'=>1],
            ['title' => 'West Gonja', 'branch_id' => 14,'status'=>1],



        // ================= Upper West Region =================
            ['title' => 'Daffiama-Bussie-Issa', 'branch_id' => 16,'status'=>1],
            ['title' => 'Jirapa', 'branch_id' => 16,'status'=>1],
            ['title' => 'Lambussie Karni', 'branch_id' => 16,'status'=>1],
            ['title' => 'Lawra', 'branch_id' => 16,'status'=>1],
            ['title' => 'Nadowli-Kaleo', 'branch_id' => 16,'status'=>1],
            ['title' => 'Nandom', 'branch_id' => 16,'status'=>1],
            ['title' => 'Sissala East', 'branch_id' => 16,'status'=>1],
            ['title' => 'Sissala West', 'branch_id' => 16,'status'=>1],
            ['title' => 'Wa East', 'branch_id' => 16,'status'=>1],
            ['title' => 'Wa Municipal', 'branch_id' => 16,'status'=>1],
            ['title' => 'Wa West', 'branch_id' => 16,'status'=>1],




        // ================= Bono East Region =================
            ['title' => 'Atebubu-Amantin Municipal', 'branch_id' => 16,'status'=>1],
            ['title' => 'Kintampo North Municipal', 'branch_id' => 16,'status'=>1],
            ['title' => 'Kintampo South', 'branch_id' => 16,'status'=>1],
            ['title' => 'Nkoranza North', 'branch_id' => 16,'status'=>1],
            ['title' => 'Nkoranza South Municipal', 'branch_id' => 16,'status'=>1],
            ['title' => 'Pru East', 'branch_id' => 16,'status'=>1],
            ['title' => 'Pru West', 'branch_id' => 16,'status'=>1],
            ['title' => 'Sene East', 'branch_id' => 16,'status'=>1],
            ['title' => 'Sene West', 'branch_id' => 16,'status'=>1],
            ['title' => 'Techiman Municipal', 'branch_id' => 16,'status'=>1],
            ['title' => 'Techiman North', 'branch_id' => 16,'status'=>1],



        // ================= Ahafo Region =================
            ['title' => 'Asunafo North Municipal', 'branch_id' => 17,'status'=>1],
            ['title' => 'Asunafo South', 'branch_id' => 17,'status'=>1],
            ['title' => 'Asutifi North', 'branch_id' => 17,'status'=>1],
            ['title' => 'Asutifi South', 'branch_id' => 17,'status'=>1],
            ['title' => 'Tano North Municipal', 'branch_id' => 17,'status'=>1],
            ['title' => 'Tano South Municipal', 'branch_id' => 17,'status'=>1],

        ]);
    }
}
