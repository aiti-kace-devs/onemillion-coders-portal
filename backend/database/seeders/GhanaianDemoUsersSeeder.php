<?php

namespace Database\Seeders;

use App\Models\Oex_category;
use App\Models\Oex_exam_master;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic Ghanaian student users for local / QA databases.
 *
 * Requirements (from migrations / schema):
 * - users.email, users.userId, users.mobile_no, users.student_id, users.ghcard must be unique when set
 * - users.exam is a foreign key to oex_exam_masters (required on many installs)
 *
 * Run after CategorySeeder (oex_categories). Safe to re-run: matches on email.
 *
 *   php artisan db:seed --class=GhanaianDemoUsersSeeder
 *
 * Or set in .env: SEED_GHANAIAN_DEMO_USERS=true then php artisan db:seed
 */
class GhanaianDemoUsersSeeder extends Seeder
{
    /**
     * Shared bcrypt hash for every seeded user (plain password is whatever was used to generate this hash).
     */
    private const PASSWORD_HASH = '$2y$10$h5CBf1pj5TRKyF6xi.wgzOzmH.i/Kx7R7TCtDR3GLPiSpne3eRhFy';

    public function run(): void
    {
        $examId = $this->resolveOrCreateDemoExamId();
        if ($examId === null) {
            $this->command?->error('GhanaianDemoUsersSeeder: could not resolve oex_exam_masters.id (need at least one oex_categories row). Run CategorySeeder first.');

            return;
        }

        $rows = $this->demoPeople();
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $email = $row['email'];
            // Stable public id for JWT / bookings; unique and reproducible on re-seed.
            $userId = 'gh-'.substr(bin2hex(hash('sha256', $email, true)), 0, 24);
            $mobile = sprintf('0244%07d', 30000 + $i);
            $studentId = sprintf('OMCP26-%05d', $i);
            $ghSuffix = str_pad((string) (900000000 + $i), 9, '0', STR_PAD_LEFT);
            $ghcard = 'GHA-'.$ghSuffix.'-'.((int) $i % 10);

            $first = $row['first_name'];
            $middle = $row['middle_name'] ?? null;
            $last = $row['last_name'];
            $fullName = trim(implode(' ', array_filter([$first, $middle, $last])));

            User::updateOrCreate(
                ['email' => $email],
                [
                    'userId' => $userId,
                    'name' => $fullName,
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'password' => self::PASSWORD_HASH,
                    'mobile_no' => $mobile,
                    'age' => (string) (18 + ($i % 15)),
                    'gender' => $row['gender'],
                    'exam' => $examId,
                    'registered_course' => null,
                    'shortlist' => false,
                    'status' => true,
                    'pwd' => false,
                    'support' => false,
                    'student_id' => $studentId,
                    'ghcard' => $ghcard,
                    'card_type' => 'Ghana Card',
                    'network_type' => $row['network'],
                    'student_level' => $row['level'] ?? 'Entry',
                    'is_verification_blocked' => false,
                    'is_nia_syncing' => false,
                ]
            );
        }

        $this->command?->info('GhanaianDemoUsersSeeder: upserted '.count($rows).' users (shared password hash).');
    }

    private function resolveOrCreateDemoExamId(): ?int
    {
        $existing = Oex_exam_master::withoutGlobalScopes()->orderBy('id')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $categoryId = Oex_category::query()->orderBy('id')->value('id');
        if (! $categoryId) {
            return null;
        }

        $exam = Oex_exam_master::withoutGlobalScopes()->firstOrCreate(
            ['title' => 'OMCP Demo Entrance (seed)'],
            [
                'category' => (int) $categoryId,
                'passmark' => 50,
                'exam_date' => now()->addYear(),
                'exam_duration' => 60,
                'number_of_questions' => 20,
                'status' => true,
            ]
        );

        return (int) $exam->id;
    }

    /**
     * @return list<array{first_name: string, middle_name?: string|null, last_name: string, gender: 'male'|'female', email: string, network: 'mtn'|'telecel'|'airteltigo', level?: string}>
     */
    private function demoPeople(): array
    {
        return [
            ['first_name' => 'Kwame', 'last_name' => 'Mensah', 'gender' => 'male', 'email' => 'kw.m@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Akosua', 'middle_name' => 'Frema', 'last_name' => 'Boateng', 'gender' => 'female', 'email' => 'ak.b@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Kofi', 'last_name' => 'Amoako', 'gender' => 'male', 'email' => 'kf.a@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Abena', 'last_name' => 'Osei', 'gender' => 'female', 'email' => 'ab.o@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Yaw', 'last_name' => 'Asante', 'gender' => 'male', 'email' => 'yw.a@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Ama', 'middle_name' => 'Serwaa', 'last_name' => 'Duah', 'gender' => 'female', 'email' => 'am.d@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Kwesi', 'last_name' => 'Adjei', 'gender' => 'male', 'email' => 'ks.ad@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Efua', 'last_name' => 'Dadzie', 'gender' => 'female', 'email' => 'ef.d@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Nana', 'middle_name' => 'Kwame', 'last_name' => 'Agyemang', 'gender' => 'male', 'email' => 'nn.ag@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Adjoa', 'last_name' => 'Tetteh', 'gender' => 'female', 'email' => 'ad.t@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Kwabena', 'last_name' => 'Ofori', 'gender' => 'male', 'email' => 'kb.o@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Leticia', 'last_name' => 'Acheampong', 'gender' => 'female', 'email' => 'lt.ac@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Selassie', 'last_name' => 'Teye', 'gender' => 'male', 'email' => 'se.t@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Maame', 'middle_name' => 'Efua', 'last_name' => 'Bonsu', 'gender' => 'female', 'email' => 'mm.b@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Ibrahim', 'last_name' => 'Sulemana', 'gender' => 'male', 'email' => 'ib.s@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Fuseini', 'last_name' => 'Alhassan', 'gender' => 'male', 'email' => 'fu.a@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Rabiatu', 'last_name' => 'Osman', 'gender' => 'female', 'email' => 'rb.o@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Akua', 'last_name' => 'Twumasi', 'gender' => 'female', 'email' => 'ak.tw@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Danso', 'last_name' => 'Wiredu', 'gender' => 'male', 'email' => 'dn.w@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Afua', 'last_name' => 'Bediako', 'gender' => 'female', 'email' => 'af.bd@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Gifty', 'last_name' => 'Narh', 'gender' => 'female', 'email' => 'gf.n@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Theophilus', 'last_name' => 'Lamptey', 'gender' => 'male', 'email' => 'th.l@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Ernestina', 'last_name' => 'Quaye', 'gender' => 'female', 'email' => 'er.q@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Patrick', 'last_name' => 'Okyere', 'gender' => 'male', 'email' => 'pt.ok@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Araba', 'last_name' => 'Andoh', 'gender' => 'female', 'email' => 'ar.an@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Kojo', 'middle_name' => 'Poku', 'last_name' => 'Sarpong', 'gender' => 'male', 'email' => 'kj.sp@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Esi', 'last_name' => 'Owusu', 'gender' => 'female', 'email' => 'es.o@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Yaw', 'middle_name' => 'Boakye', 'last_name' => 'Frimpong', 'gender' => 'male', 'email' => 'yb.f@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Adwoa', 'last_name' => 'Konadu', 'gender' => 'female', 'email' => 'ad.k@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Samuel', 'last_name' => 'Appiah', 'gender' => 'male', 'email' => 'sm.ap@om.gh', 'network' => 'telecel'],
            ['first_name' => 'Comfort', 'last_name' => 'Yeboah', 'gender' => 'female', 'email' => 'cm.y@om.gh', 'network' => 'mtn'],
            ['first_name' => 'Richmond', 'last_name' => 'Atta', 'gender' => 'male', 'email' => 'rc.a@om.gh', 'network' => 'airteltigo'],
            ['first_name' => 'Portia', 'middle_name' => 'Aba', 'last_name' => 'Mensima', 'gender' => 'female', 'email' => 'pr.m@om.gh', 'network' => 'telecel'],
        ];
    }
}
