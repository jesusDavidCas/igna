<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'slug' => 'jesus-david-castaneda',
                'name' => 'Jesús David Castañeda',
                'role' => __('site.team_jesus_role'),
                'short_description' => __('site.team_jesus_summary'),
                'bio' => [
                    __('site.team_jesus_bio_1'),
                    __('site.team_jesus_bio_2'),
                ],
                'expertise' => [
                    __('site.team_jesus_expertise_1'),
                    __('site.team_jesus_expertise_2'),
                    __('site.team_jesus_expertise_3'),
                    __('site.team_jesus_expertise_4'),
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => 'roberto-castaneda-pardo',
                'name' => 'Roberto Castañeda Pardo',
                'role' => __('site.team_roberto_role'),
                'short_description' => __('site.team_roberto_summary'),
                'bio' => [
                    __('site.team_roberto_bio_1'),
                    __('site.team_roberto_bio_2'),
                ],
                'expertise' => [
                    __('site.team_roberto_expertise_1'),
                    __('site.team_roberto_expertise_2'),
                    __('site.team_roberto_expertise_3'),
                    __('site.team_roberto_expertise_4'),
                ],
                'sort_order' => 2,
            ],
        ];

        foreach ($members as $member) {
            TeamMember::query()->updateOrCreate(
                ['slug' => $member['slug']],
                [
                    ...$member,
                    'is_active' => true,
                ],
            );
        }
    }
}
