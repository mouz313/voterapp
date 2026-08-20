<?php

namespace Database\Seeders;

use App\Models\BlockCode;
use App\Models\District;
use App\Models\PollingStation;
use App\Models\Tehsil;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Real-ish Lahore structure:
     * District(Lahore) -> 10 tehsils -> 433 UCs -> ECP-style 9-digit block codes + polling stations -> sample voters.
     */
    public function run(): void
    {
        // Admin (users table is not truncated)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        // Clean slate for idempotent seeding (handles partial/failed runs)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['voters', 'block_codes', 'polling_stations', 'ucs', 'tehsils', 'districts'] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');


        $district = District::firstOrCreate(['name' => 'لاہور']);

        // Urdu name pools (dev data only; real data comes via CSV import)
        $maleNames = ['محمد', 'احمد', 'علی', 'عثمان', 'عمران', 'حسن', 'حسین', 'بلال', 'فہد', 'کامران', 'طارق', 'جاوید', 'نوید', 'ساجد', 'ارشد', 'وحید', 'رشید', 'عرفان', 'آصف', 'یاسر', 'فیصل', 'وقاص', 'زاہد', 'رضوان', 'شاہد'];
        $femaleNames = ['عائشہ', 'فاطمہ', 'مریم', 'ثنا', 'امنا', 'رابعہ', 'صائمہ', 'زینب', 'بشریٰ', 'طاہرہ', 'شازیہ', 'نبیلہ', 'کلثوم', 'پروین', 'نازیہ', 'یاسمین', 'رخسار', 'سعدیہ', 'حنا', 'عابدہ', 'منزہ', 'فوزیہ', 'گلشن', 'نورین'];
        $surnames = ['خان', 'بھٹی', 'شیخ', 'ملک', 'چوہدری', 'سید', 'رانا', 'اسلم', 'اقبال', 'محمود', 'جاوید', 'طارق', 'اعوان', 'مغل', 'گجر', 'لودھی', 'رٹھڑ', 'چیمہ', 'سیال'];

        // Lahore locality / colony name pool for UCs (user will later replace with real 433 names)
        $localities = [
            'مصطفی آباد', 'والٹن', 'سکیم مور', 'بھٹو کالونی', 'گرین ٹاؤن', 'سبزہ زار',
            'مغلپورہ', 'گڑھی شاہو', 'اچھرہ', 'کوٹ لکھپت', 'ماڈل ٹاؤن', 'گلبرگ',
            'فیصل ٹاؤن', 'ٹاؤن شپ', 'کینٹ', 'ہربنس پورہ', 'بارکی', 'بیدیاں', 'مناواں',
            'سمن آباد', 'نواب ٹاؤن', 'واپڈا ٹاؤن', 'جوہر ٹاؤن', 'ڈیفنس', 'گارڈن ٹاؤن',
            'علامہ اقبال ٹاؤن', 'رحمان پورہ', 'شادمان', 'مغل آباد', 'دروغے والا', 'سندا',
            'باغبان پورہ', 'بیگم کوٹ', 'کہنہ', 'بھولا', 'رائے ونڈ', 'کوٹ عبدالمالک', 'شاہدرہ',
            'بڑامی باغ', 'مومن پورہ', 'چاہ میراں', 'سکندر پور', 'عزیز آباد', 'پنجاب سوسائٹی',
            'رحمت پورہ', 'غازی آباد', 'اسلامیہ کالونی', 'کینال ویو', 'کشمیر کالونی', 'منصور آباد',
        ];

        $randName = function () use ($maleNames, $femaleNames, $surnames) {
            $genderPool = fake()->randomElement([$maleNames, $femaleNames]);
            return fake()->randomElement($genderPool) . ' ' . fake()->randomElement($surnames);
        };
        $randFather = function () use ($maleNames, $surnames) {
            return fake()->randomElement($maleNames) . ' ' . fake()->randomElement($surnames);
        };

        // 10 real Lahore tehsils (Urdu) with 2-digit ECP tehsil codes
        $tehsilDefs = [
            'لاہور سٹی'        => '01',
            'لاہور کینٹ'        => '02',
            'ماڈل ٹاؤن'         => '03',
            'شالیمار'           => '04',
            'راوی'              => '05',
            'واگہ'              => '06',
            'نشتر'              => '07',
            'اقبال ٹاؤن'        => '08',
            'صدر'               => '09',
            'گلبرگ'             => '10',
        ];

        $tehsils = [];
        foreach ($tehsilDefs as $name => $code) {
            $tehsils[$name] = Tehsil::firstOrCreate(
                ['district_id' => $district->id, 'name' => $name],
                ['district_id' => $district->id, 'name' => $name]
            );
        }

        // 433 UCs distributed round-robin across the 10 tehsils
        $ucNames = array_values($tehsilDefs);
        $tehsilSerial = []; // per-tehsil block serial counter
        foreach ($tehsils as $t) {
            $tehsilSerial[$t->id] = 101; // start block serials at 101
        }

        $voterRows = [];
        $cnicCounter = 1;
        $now = now();

        for ($i = 1; $i <= 433; $i++) {
            $tehsil = $tehsils[array_keys($tehsilDefs)[($i - 1) % 10]];
            $tehsilCode = $tehsilDefs[$tehsil->name];

            $locality = $localities[($i - 1) % count($localities)];
            $locGroup = intdiv($i - 1, count($localities)) + 1;
            $ucName = $locality . ' ' . $locGroup;

            $uc = UC::create([
                'tehsil_id' => $tehsil->id,
                'name' => $ucName,
            ]);

            // Block codes: 2-3 per UC, 9-digit ECP pattern 18 55 TT BBB
            $blockCount = rand(2, 3);
            $blocks = [];
            for ($b = 0; $b < $blockCount; $b++) {
                $serial = $tehsilSerial[$tehsil->id]++;
                $code = '18' . '55' . $tehsilCode . str_pad($serial, 3, '0', STR_PAD_LEFT);
                $blocks[] = BlockCode::create([
                    'uc_id' => $uc->id,
                    'code' => $code,
                ]);
            }

            // Polling stations: 1-2 per UC
            $stationCount = rand(1, 2);
            $stations = [];
            for ($s = 1; $s <= $stationCount; $s++) {
                $stations[] = PollingStation::create([
                    'uc_id' => $uc->id,
                    'name' => 'گورنمنٹ ' . fake()->randomElement(['پرائمری', 'ہائی', 'ایلمنٹری']) . ' سکول ' . $ucName . ($stationCount > 1 ? ' (' . $s . ')' : ''),
                    'address' => $ucName . '، ' . $tehsil->name . '، لاہور',
                ]);
            }

            // Voters: 2-4 families per UC, 2-4 members each (~5-12 voters/UC)
            $familyCount = rand(2, 4);
            for ($f = 1; $f <= $familyCount; $f++) {
                $gharana = (string) $f;
                $memberCount = rand(2, 4);
                for ($m = 1; $m <= $memberCount; $m++) {
                    $cnic = '352' . str_pad($cnicCounter++, 10, '0', STR_PAD_LEFT); // 13 digits, unique
                    $voterRows[] = [
                        'uc_id' => $uc->id,
                        'block_code_id' => $blocks[array_rand($blocks)]->id,
                        'polling_station_id' => $stations[array_rand($stations)]->id,
                        'name' => $randName(),
                        'father_name' => $randFather(),
                        'age' => rand(18, 95),
                        'cnic' => $cnic,
                        'silsala_no' => (string) $m,
                        'gharana_no' => $gharana,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Bulk insert voters in chunks for performance
        foreach (array_chunk($voterRows, 500) as $chunk) {
            Voter::insert($chunk);
        }
    }
}
