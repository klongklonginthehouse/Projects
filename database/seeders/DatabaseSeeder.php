<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Report;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $depts = collect(['Engineering', 'Computer Science', 'Sciences', 'Business', 'Arts and Letters', 'Education'])
            ->mapWithKeys(fn ($n) => [$n => Department::firstOrCreate(['name' => $n])->id]);

        // Demo accounts. Password for all: password123
        $mk = fn ($name, $email, $role, $credits = 10) => User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password123', 'role' => $role, 'credits' => $credits],
        );
        $student = $mk('Ana Reyes', 'student@iskolar.test', 'student');
        $author = $mk('Miguel Santos', 'author@iskolar.test', 'author');
        $mod = $mk('Bea Lim', 'moderator@iskolar.test', 'moderator');
        $faculty = $mk('Prof. Dela Cruz', 'faculty@iskolar.test', 'faculty');

        $rows = [
            [$author, 'Computer Science', 'Stacks, queues and linked lists: lecture summary', 'CS 12', 'Prof. Dela Cruz', 'lecture_notes', 'verified', false, 41],
            [$author, 'Computer Science', 'Midterm exam 1st sem 2024 with worked solutions', 'CS 12', 'Prof. Dela Cruz', 'past_exam', 'verified', false, 58],
            [$mod, 'Sciences', 'Calculus II reviewer: series and convergence tests', 'MATH 22', 'Prof. Villanueva', 'reviewer', 'verified', false, 33],
            [$faculty, 'Engineering', 'Official course syllabus: Engineering Economics', 'EE 101', 'Engr. Macaraeg', 'syllabus', 'verified', true, 12],
            [$author, 'Business', 'Principles of Marketing: chapters 1 to 6', 'BA 110', 'Prof. Torres', 'lecture_notes', 'verified', false, 19],
            [$author, 'Sciences', 'Organic Chemistry final exam 2023', 'CHEM 31', 'Dr. Ocampo', 'past_exam', 'pending', false, 0],
        ];

        foreach ($rows as $i => [$by, $dept, $title, $code, $inst, $type, $status, $official, $votes]) {
            $path = "resources/seed-{$i}.txt";
            Storage::disk('local')->put($path, "{$title}\n{$code} | {$inst}\n\nSample file from the seeder. Replace with real uploads.");

            $r = Resource::firstOrCreate(['title' => $title], [
                'user_id' => $by->id, 'department_id' => $depts[$dept], 'course_code' => $code,
                'instructor' => $inst, 'type' => $type, 'description' => 'Seeded demo resource.',
                'file_path' => $path, 'original_name' => "seed-{$i}.txt", 'status' => $status,
                'is_official' => $official, 'votes_count' => $votes,
                'verified_at' => $status === 'verified' ? now() : null,
            ]);

            // One open copyright report so the review queue has something in it.
            if ($i === 4) {
                Report::firstOrCreate(['user_id' => $student->id, 'resource_id' => $r->id], ['reason' => 'copyright']);
            }
        }
    }
}
